"""Train the SafeRail four-class Decision Tree model from Node-RED JSONL data.

Expected labels:
    NORMAL_PASS, BLOCKED, NOISE, UNKNOWN

SENSOR_FAULT is intentionally excluded because it remains a deterministic
fail-safe rule in Node-RED rather than a learned class.
"""

from __future__ import annotations

import json
import sys
from pathlib import Path

import joblib
import pandas as pd
from sklearn.compose import ColumnTransformer
from sklearn.metrics import (
    accuracy_score,
    classification_report,
    confusion_matrix,
)
from sklearn.model_selection import StratifiedKFold, cross_val_score, train_test_split
from sklearn.pipeline import Pipeline
from sklearn.preprocessing import OneHotEncoder
from sklearn.tree import DecisionTreeClassifier, export_text


DATA_PATH = Path.home() / "NodeREDData" / "train_gate_training_final.jsonl"
OUTPUT_DIR = Path.home() / "NodeREDData" / "decision_tree_output"

TARGET_LABELS = ["NORMAL_PASS", "BLOCKED", "NOISE", "UNKNOWN"]

CATEGORICAL_FEATURES = [
    "direction",
    "arduino_state",
]

NUMERIC_FEATURES = [
    "gap_ms",
    "duration_A_ms",
    "duration_B_ms",
    "error_count_A",
    "error_count_B",
    "distance_A_cm",
    "distance_B_cm",
]

FEATURES = CATEGORICAL_FEATURES + NUMERIC_FEATURES


def load_jsonl(path: Path) -> pd.DataFrame:
    if not path.exists():
        raise FileNotFoundError(f"Training data file not found: {path}")

    records: list[dict] = []
    with path.open("r", encoding="utf-8-sig") as data_file:
        for line_number, line in enumerate(data_file, start=1):
            line = line.strip()
            if not line:
                continue

            try:
                value = json.loads(line)
            except json.JSONDecodeError as exc:
                raise ValueError(
                    f"Invalid JSON on line {line_number}: {exc}"
                ) from exc

            if not isinstance(value, dict):
                raise ValueError(f"Line {line_number} is not a JSON object.")

            records.append(value)

    if not records:
        raise ValueError("The training data file is empty.")

    return pd.DataFrame(records)


def validate_and_prepare(data: pd.DataFrame) -> tuple[pd.DataFrame, pd.Series]:
    required = set(FEATURES + ["label"])
    missing = sorted(required.difference(data.columns))
    if missing:
        raise ValueError(f"Missing required fields: {', '.join(missing)}")

    # SENSOR_FAULT and any accidental labels are not learned by this model.
    filtered = data[data["label"].isin(TARGET_LABELS)].copy()

    counts = filtered["label"].value_counts().reindex(TARGET_LABELS, fill_value=0)
    print("\nSamples per label")
    print(counts.to_string())

    missing_labels = counts[counts == 0].index.tolist()
    if missing_labels:
        raise ValueError(
            "No training samples for: " + ", ".join(missing_labels)
        )

    if counts.min() < 4:
        raise ValueError(
            "Each label needs at least 4 samples for a stratified train/test split."
        )

    if counts.min() < 20:
        print(
            "\nWARNING: Fewer than 20 samples exist for at least one label. "
            "This is acceptable for a prototype, but the measured accuracy "
            "will not be a strong estimate of real-world performance."
        )

    features = filtered[FEATURES].copy()

    for column in CATEGORICAL_FEATURES:
        features[column] = features[column].fillna("NONE").astype(str)

    for column in NUMERIC_FEATURES:
        features[column] = pd.to_numeric(features[column], errors="coerce").fillna(-1)

    labels = filtered["label"].astype(str)
    return features, labels


def main() -> int:
    print(f"Reading: {DATA_PATH}")
    raw_data = load_jsonl(DATA_PATH)
    features, labels = validate_and_prepare(raw_data)

    x_train, x_test, y_train, y_test = train_test_split(
        features,
        labels,
        test_size=0.25,
        random_state=42,
        stratify=labels,
    )

    preprocessing = ColumnTransformer(
        transformers=[
            (
                "category",
                OneHotEncoder(handle_unknown="ignore"),
                CATEGORICAL_FEATURES,
            ),
            ("number", "passthrough", NUMERIC_FEATURES),
        ],
        remainder="drop",
    )

    tree = DecisionTreeClassifier(
        max_depth=4,
        min_samples_leaf=2,
        class_weight="balanced",
        random_state=42,
    )

    model = Pipeline(
        steps=[
            ("preprocess", preprocessing),
            ("classifier", tree),
        ]
    )

    model.fit(x_train, y_train)
    predictions = model.predict(x_test)

    accuracy = accuracy_score(y_test, predictions)
    report = classification_report(
        y_test,
        predictions,
        labels=TARGET_LABELS,
        zero_division=0,
    )
    matrix = confusion_matrix(y_test, predictions, labels=TARGET_LABELS)

    minimum_class_count = int(labels.value_counts().min())
    fold_count = min(5, minimum_class_count)
    cross_validation = StratifiedKFold(
        n_splits=fold_count,
        shuffle=True,
        random_state=42,
    )
    cv_scores = cross_val_score(
        model,
        features,
        labels,
        cv=cross_validation,
        scoring="accuracy",
    )

    OUTPUT_DIR.mkdir(parents=True, exist_ok=True)

    model_path = OUTPUT_DIR / "train_gate_decision_tree.joblib"
    joblib.dump(model, model_path)

    transformed_feature_names = model.named_steps[
        "preprocess"
    ].get_feature_names_out()

    fitted_tree = model.named_steps["classifier"]
    rules = export_text(
        fitted_tree,
        feature_names=list(transformed_feature_names),
    )
    rules_path = OUTPUT_DIR / "decision_tree_rules.txt"
    rules_path.write_text(rules, encoding="utf-8")

    importance = pd.DataFrame(
        {
            "feature": transformed_feature_names,
            "importance": fitted_tree.feature_importances_,
        }
    ).sort_values("importance", ascending=False)
    importance.to_csv(OUTPUT_DIR / "feature_importance.csv", index=False)

    matrix_frame = pd.DataFrame(
        matrix,
        index=[f"actual_{label}" for label in TARGET_LABELS],
        columns=[f"predicted_{label}" for label in TARGET_LABELS],
    )
    matrix_frame.to_csv(OUTPUT_DIR / "confusion_matrix.csv")

    label_counts = labels.value_counts().reindex(TARGET_LABELS, fill_value=0)
    report_text = (
        "SafeRail Decision Tree training report\n"
        "======================================\n\n"
        f"Data file: {DATA_PATH}\n"
        f"Total learned samples: {len(labels)}\n"
        f"Train samples: {len(y_train)}\n"
        f"Test samples: {len(y_test)}\n\n"
        "Samples per label:\n"
        f"{label_counts.to_string()}\n\n"
        f"Holdout accuracy: {accuracy:.3f}\n"
        f"{fold_count}-fold CV scores: {cv_scores.tolist()}\n"
        f"Mean CV accuracy: {cv_scores.mean():.3f}\n\n"
        "Classification report:\n"
        f"{report}\n"
    )
    (OUTPUT_DIR / "model_report.txt").write_text(report_text, encoding="utf-8")

    metadata = {
        "model_type": "DecisionTreeClassifier",
        "labels": TARGET_LABELS,
        "features": FEATURES,
        "sensor_fault_handling": "deterministic Node-RED rule; not learned",
        "holdout_accuracy": float(accuracy),
        "cross_validation_scores": [float(score) for score in cv_scores],
    }
    (OUTPUT_DIR / "model_metadata.json").write_text(
        json.dumps(metadata, indent=2),
        encoding="utf-8",
    )

    print("\nTraining complete")
    print(f"Holdout accuracy: {accuracy:.3f}")
    print(f"Mean CV accuracy: {cv_scores.mean():.3f}")
    print("\nClassification report")
    print(report)
    print("Confusion matrix")
    print(matrix_frame.to_string())
    print(f"\nSaved model: {model_path}")
    print(f"All outputs: {OUTPUT_DIR}")
    return 0


if __name__ == "__main__":
    try:
        raise SystemExit(main())
    except Exception as error:
        print(f"\nERROR: {error}", file=sys.stderr)
        raise SystemExit(1)
