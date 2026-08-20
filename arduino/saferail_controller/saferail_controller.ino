#include <Servo.h>

// --- PIN MAP ---
const int redPin = 8, yellowPin = 9, greenPin = 10;
const int buzzerPin = 2;
const int servoPin = 3;

// Two sonars, one on each side of the crossing. Neither side has a fixed
// role anymore -- whichever one sees a train first becomes the "entry"
// side for that crossing, and the other becomes the "exit" side. This is
// what lets a single pair of sensors handle trains from either direction.
const int SIDE_A = 0, SIDE_B = 1;
const int trigPin[2] = { 11, 6 };
const int echoPin[2] = { 12, 7 };

Servo gateServo;

const int openAngle = 105;
const int closedAngle = 15;

// PROVISIONAL -- recalibrate both of these on the physical board.
const int dangerDistance = 15;
const unsigned long warningDelayMs = 100; // yellow shown before gate closes

// A raw reading must repeat this many loops in a row (~50ms each) before
// it's trusted to change state. Kills single-frame sonar glitches/crosstalk.
const int debounceCount = 3;

// The servo yanks enough current on movement to sag the shared 5V rail,
// which times out BOTH sonars for a few hundred ms right as it moves.
// Ignore sensor readings for this long after every gate movement.
const unsigned long servoSettleMs = 500;

enum State { SAFE, WARNING, DANGER };
State state = SAFE;
unsigned long warningStartTime = 0;
unsigned long settleUntil = 0; // millis() timestamp; readings ignored until then

int entrySide = -1;          // SIDE_A or SIDE_B -- whichever side the train came in from this crossing
bool exitWasTriggered = false; // latch: exit-side sonar must see train THEN clear before we reopen

int trigCount[2] = { 0, 0 };  // consecutive loops each side read as triggered
int clearCount[2] = { 0, 0 }; // consecutive loops each side read as genuinely clear (not -1, not close)

long readDistance(int trig, int echo) {
  digitalWrite(trig, LOW);
  delayMicroseconds(2);
  digitalWrite(trig, HIGH);
  delayMicroseconds(10);
  digitalWrite(trig, LOW);

  long duration = pulseIn(echo, HIGH, 30000); // 30ms timeout
  if (duration == 0) return -1; // sensor gave no reading at all
  return duration * 0.034 / 2;
}

// FAIL-SAFE: a sensor timeout (-1) counts as a detection -- never treat
// "no reading" as safe.
bool isTriggered(long dist) {
  return (dist == -1) || (dist <= dangerDistance);
}

// Only a VALID far reading counts as "clear" -- a -1 timeout means "no
// idea", not "gone", so it must never by itself reopen the gate.
bool isClear(long dist) {
  return (dist != -1) && (dist > dangerDistance);
}

// Non-blocking beep toggle (~2.5 beeps/sec). Simplified from the earlier
// double-beep pattern because loop() can no longer pause with delay() --
// it has to keep reading both sonars every cycle.
void updateBeep() {
  static unsigned long lastToggle = 0;
  static bool beepOn = false;
  if (millis() - lastToggle > 200) {
    lastToggle = millis();
    beepOn = !beepOn;
    if (beepOn) tone(buzzerPin, 1800, 150);
  }
}

void setLights(char color) { // 'G', 'Y', or 'R'
  digitalWrite(greenPin, color == 'G' ? HIGH : LOW);
  digitalWrite(yellowPin, color == 'Y' ? HIGH : LOW);
  digitalWrite(redPin, color == 'R' ? HIGH : LOW);
}

void setup() {
  Serial.begin(9600);

  pinMode(redPin, OUTPUT); pinMode(yellowPin, OUTPUT); pinMode(greenPin, OUTPUT);
  pinMode(buzzerPin, OUTPUT);
  pinMode(trigPin[SIDE_A], OUTPUT); pinMode(echoPin[SIDE_A], INPUT);
  pinMode(trigPin[SIDE_B], OUTPUT); pinMode(echoPin[SIDE_B], INPUT);

  gateServo.attach(servoPin);
  setLights('G');
  gateServo.write(openAngle);
}

void loop() {
  long dist[2];
  dist[SIDE_A] = readDistance(trigPin[SIDE_A], echoPin[SIDE_A]);
  dist[SIDE_B] = readDistance(trigPin[SIDE_B], echoPin[SIDE_B]);

  bool triggered[2] = { isTriggered(dist[SIDE_A]), isTriggered(dist[SIDE_B]) };
  bool clear[2]      = { isClear(dist[SIDE_A]),     isClear(dist[SIDE_B]) };

  bool settling = millis() < settleUntil;
  for (int s = 0; s < 2; s++) {
    if (settling) {
      // Servo brownout window -- don't let glitches here build up debounce count.
      trigCount[s] = 0;
      clearCount[s] = 0;
    } else {
      trigCount[s] = triggered[s] ? trigCount[s] + 1 : 0;
      clearCount[s] = clear[s] ? clearCount[s] + 1 : 0;
    }
  }
  bool trigConfirmed[2]  = { trigCount[SIDE_A] >= debounceCount, trigCount[SIDE_B] >= debounceCount };
  bool clearConfirmed[2] = { clearCount[SIDE_A] >= debounceCount, clearCount[SIDE_B] >= debounceCount };

  const char* stateName = state == SAFE ? "SAFE" : state == WARNING ? "WARNING" : "DANGER";
  Serial.print("[state=");
  Serial.print(stateName);
  if (entrySide != -1) Serial.print(entrySide == SIDE_A ? " entry=A" : " entry=B");
  Serial.print("] A=");
  Serial.print(dist[SIDE_A]);
  Serial.print("cm (trig=");
  Serial.print(triggered[SIDE_A] ? "YES" : "no");
  Serial.print(")  B=");
  Serial.print(dist[SIDE_B]);
  Serial.print("cm (trig=");
  Serial.print(triggered[SIDE_B] ? "YES" : "no");
  Serial.print(")");
  if (settling) Serial.print("  [settling]");
  Serial.println();

  switch (state) {

    case SAFE:
      setLights('G');
      gateServo.write(openAngle);
      noTone(buzzerPin);
      // Whichever side sees a train first becomes the entry side; the
      // other side is what we'll watch for the train to clear.
      if (trigConfirmed[SIDE_A] || trigConfirmed[SIDE_B]) {
        entrySide = trigConfirmed[SIDE_A] ? SIDE_A : SIDE_B;
        state = WARNING;
        warningStartTime = millis();
        trigCount[1 - entrySide] = 0;
        clearCount[1 - entrySide] = 0;
        Serial.print("==> SAFE -> WARNING (entry=");
        Serial.print(entrySide == SIDE_A ? "A, " : "B, ");
        Serial.print(dist[entrySide]);
        Serial.println("cm)");
      }
      break;

    case WARNING:
      setLights('Y');
      // gate stays OPEN during this whole phase, on purpose
      updateBeep();
      if (millis() - warningStartTime >= warningDelayMs) {
        state = DANGER;
        exitWasTriggered = false; // fresh latch for this crossing
        int exitSide = 1 - entrySide;
        trigCount[exitSide] = 0;
        clearCount[exitSide] = 0;
        settleUntil = millis() + servoSettleMs; // gate is about to swing closed
        Serial.println("==> WARNING -> DANGER (closing gate now)");
      }
      break;

    case DANGER: {
      setLights('R');
      gateServo.write(closedAngle);
      updateBeep();
      int exitSide = 1 - entrySide;
      // Train must be SEEN at the exit side first, THEN give a confirmed
      // (debounced, non -1) far reading, before we reopen -- a single -1
      // timeout or a single stray close reading must never flip the gate.
      if (trigConfirmed[exitSide]) {
        exitWasTriggered = true;
      }
      if (exitWasTriggered && clearConfirmed[exitSide]) {
        state = SAFE;
        settleUntil = millis() + servoSettleMs; // gate is about to swing open
        Serial.print("==> DANGER -> SAFE (exit ");
        Serial.print(exitSide == SIDE_A ? "A" : "B");
        Serial.print(" cleared at ");
        Serial.print(dist[exitSide]);
        Serial.println("cm, train passed, opening gate)");
        entrySide = -1;
      }
      break;
    }
  }

  delay(50); // small pacing, keeps sonar readings stable
}
