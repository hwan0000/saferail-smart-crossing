"""Local prediction API for the SafeRail Decision Tree model.

Run on the same Windows PC as Node-RED. The server listens only on localhost
(127.0.0.1) and exposes:

    GET  /health
    POST /predict
"""

from __future__ import annotations

import sys
from pathlib import Path

import joblib
import pandas as pd
from flask import Flask, jsonify, request


MODEL_PATH = (
    Path.home()
    / "NodeREDData"
    / "decision_tree_output"
    / "train_gate_decision_tree.joblib"
)

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
NORMAL_LABEL = "NORMAL_PASS"
MINIMUM_OPEN_CONFIDENCE = 0.80


if not MODEL_PATH.exists():
    print(f"ERROR: Model file not found: {MODEL_PATH}", file=sys.stderr)
    raise SystemExit(1)

try:
    model = joblib.load(MODEL_PATH)
except Exception as error:
    print(f"ERROR: Could not load model: {error}", file=sys.stderr)
    raise SystemExit(1)


app = Flask(__name__)


def prepare_features(payload: dict) -> pd.DataFrame:
    missing = [feature for feature in FEATURES if feature not in payload]
    if missing:
        raise ValueError("Missing fields: " + ", ".join(missing))

    row: dict[str, object] = {}

    for feature in CATEGORICAL_FEATURES:
        value = payload.get(feature)
        row[feature] = "NONE" if value is None else str(value)

    for feature in NUMERIC_FEATURES:
        value = payload.get(feature)
        try:
            row[feature] = float(value)
        except (TypeError, ValueError) as error:
            raise ValueError(f"{feature} must be numeric, received {value!r}") from error

    return pd.DataFrame([row], columns=FEATURES)


@app.get("/health")
def health():
    classes = [str(value) for value in model.named_steps["classifier"].classes_]
    return jsonify(
        {
            "status": "ok",
            "model": str(MODEL_PATH),
            "classes": classes,
        }
    )


@app.post("/predict")
def predict():
    payload = request.get_json(silent=True)
    if not isinstance(payload, dict):
        return jsonify({"error": "Request body must be a JSON object."}), 400

    try:
        features = prepare_features(payload)
        prediction = str(model.predict(features)[0])
        probabilities = model.predict_proba(features)[0]
        classes = [str(value) for value in model.named_steps["classifier"].classes_]
        probability_map = {
            label: round(float(probability), 6)
            for label, probability in zip(classes, probabilities)
        }
        confidence = max(probability_map.values())
    except ValueError as error:
        return jsonify({"error": str(error)}), 400
    except Exception as error:
        return jsonify({"error": f"Prediction failed: {error}"}), 500

    # This is advisory only. Do not connect it to the gate until live testing
    # is complete. Any low-confidence result remains fail-safe.
    open_candidate = (
        prediction == NORMAL_LABEL
        and confidence >= MINIMUM_OPEN_CONFIDENCE
    )

    return jsonify(
        {
            "prediction": prediction,
            "confidence": round(confidence, 6),
            "probabilities": probability_map,
            "open_candidate": open_candidate,
            "action": "OPEN_CANDIDATE" if open_candidate else "HOLD",
        }
    )


if __name__ == "__main__":
    print(f"Loaded model: {MODEL_PATH}")
    print("SafeRail prediction API: http://127.0.0.1:5050")
    print("Press Ctrl+C to stop.")
    app.run(host="127.0.0.1", port=5050, debug=False, threaded=True)