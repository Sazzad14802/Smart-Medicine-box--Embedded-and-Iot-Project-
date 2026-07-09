// ============================================================
//  Smart Medicine Box — ESP32 HTTP Server
//  Pins: IR(15,2,4,16,17,5)  LED(18,19,21,25,33,32)
//        Reed=22  Buzzer=23
// ============================================================

#include <WiFi.h>
#include <WebServer.h>
#include <ArduinoJson.h>
#include <time.h>

// ============================================================
//  CONFIG — edit only this block before flashing
// ============================================================

const char* WIFI_SSID     = "LSH_515";
const char* WIFI_PASSWORD = "87827446";

IPAddress STATIC_IP(192, 168, 0, 200);
IPAddress GATEWAY  (192, 168, 0,   1);
IPAddress SUBNET   (255, 255, 255,  0);

const long TZ_OFFSET_SEC = 6 * 3600;   // UTC+6 Bangladesh

const int IR_PINS[6]  = {15,  2,  4, 16, 17,  5};
const int LED_PINS[6] = {18, 19, 21, 25, 33, 32};
const int REED_PIN    = 22;
const int BUZZER_PIN  = 23;

// HIGH = lid open (magnet away). Flip to LOW if your reed is wired the other way.
const int REED_TAKEN_STATE = HIGH;

// ============================================================
//  Global state
// ============================================================

WebServer server(80);

String g_mode    = "dose_mode";
int    g_timeout = 10;
String g_status  = "Ready";

bool          g_reminderActive  = false;
unsigned long g_reminderStartMs = 0;
String        g_lastMinute      = "";

bool g_pendingComps[6] = {};   // true = compartment still waiting for pickup
bool g_boxOpened       = false;
int  g_irBaseline[6]   = {};   // IR snapshot taken at the moment lid opens

// ── Schedule tables ──────────────────────────────────────────

struct DoseSlot { int number; String time; bool enabled; };
struct MedSlot  { String name; int comps[6]; int compCount; String time; bool enabled; };

DoseSlot g_dose[6];   int g_doseCount = 0;
MedSlot  g_med[20];   int g_medCount  = 0;

// ============================================================
//  Helpers
// ============================================================

void jsonResponse(int code, JsonDocument& doc) {
  server.sendHeader("Access-Control-Allow-Origin", "*");
  String out;
  serializeJson(doc, out);
  server.send(code, "application/json", out);
}

void jsonError(int code, const char* msg) {
  JsonDocument doc;
  doc["success"] = false;
  doc["error"]   = msg;
  jsonResponse(code, doc);
}

bool parseBody(JsonDocument& doc) {
  String body = server.arg("plain");
  if (body.isEmpty()) {
    Serial.println("[WARN] Empty POST body");
    return false;
  }
  DeserializationError err = deserializeJson(doc, body);
  if (err) {
    Serial.printf("[ERROR] JSON parse failed: %s\n", err.c_str());
    return false;
  }
  return true;
}

String getHHMM() {
  struct tm t;
  if (!getLocalTime(&t, 0)) return "";
  char buf[6];
  strftime(buf, sizeof(buf), "%H:%M", &t);
  return String(buf);
}

// ============================================================
//  Reminder helpers
// ============================================================

void stopReminder() {
  noTone(BUZZER_PIN);
  for (int i = 0; i < 6; i++) {
    digitalWrite(LED_PINS[i], LOW);
    g_pendingComps[i] = false;
  }
  g_reminderActive = false;
  g_boxOpened      = false;
}

// compIndices is 0-indexed
void startReminder(int* compIndices, int count) {
  memset(g_pendingComps, 0, sizeof(g_pendingComps));
  for (int i = 0; i < count; i++) {
    int idx = compIndices[i];
    if (idx < 0 || idx >= 6) continue;
    digitalWrite(LED_PINS[idx], HIGH);
    g_pendingComps[idx] = true;
  }
  g_status          = "Medicine Time";
  g_reminderActive  = true;
  g_boxOpened       = false;
  g_reminderStartMs = millis();
}

// ============================================================
//  HTTP handlers
// ============================================================

void handlePing() {
  JsonDocument doc;
  doc["pong"] = true;
  jsonResponse(200, doc);
}

void handleStatus() {
  JsonDocument doc;
  doc["connected"]           = true;
  doc["mode"]                = g_mode;
  doc["status"]              = g_status;
  doc["missed_dose_timeout"] = g_timeout;
  jsonResponse(200, doc);
}

void handleSetMode() {
  JsonDocument req;
  if (!parseBody(req)) { jsonError(400, "Invalid JSON"); return; }
  g_mode   = req["mode"].as<String>();
  g_status = "Ready";
  JsonDocument res;
  res["success"] = true;
  res["mode"]    = g_mode;
  jsonResponse(200, res);
}

void handleSetDoseSchedules() {
  Serial.println("[HIT] /set-dose-schedules");
  JsonDocument req;
  if (!parseBody(req)) { jsonError(400, "Invalid JSON"); return; }
  JsonArray arr = req["schedules"].as<JsonArray>();
  g_doseCount = 0;
  for (JsonObject s : arr) {
    if (g_doseCount >= 6) break;
    g_dose[g_doseCount].number  = s["compartment_number"].as<int>();
    g_dose[g_doseCount].time    = s["reminder_time"].as<String>();
    g_dose[g_doseCount].enabled = s["is_enabled"].as<bool>();
    g_doseCount++;
  }
  Serial.printf("[OK] %d dose slots loaded\n", g_doseCount);
  JsonDocument res;
  res["success"]  = true;
  res["received"] = g_doseCount;
  jsonResponse(200, res);
}

void handleSetMedicineSchedules() {
  Serial.println("[HIT] /set-medicine-schedules");
  JsonDocument req;
  if (!parseBody(req)) { jsonError(400, "Invalid JSON"); return; }
  JsonArray arr = req["schedules"].as<JsonArray>();
  g_medCount = 0;
  for (JsonObject m : arr) {
    if (g_medCount >= 20) break;
    g_med[g_medCount].name    = m["name"].as<String>();
    g_med[g_medCount].time    = m["reminder_time"].as<String>();
    g_med[g_medCount].enabled = m["is_enabled"].as<bool>();
    JsonArray comps = m["compartments"].as<JsonArray>();
    g_med[g_medCount].compCount = 0;
    for (int c : comps) {
      if (g_med[g_medCount].compCount < 6)
        g_med[g_medCount].comps[g_med[g_medCount].compCount++] = c;
    }
    g_medCount++;
  }
  Serial.printf("[OK] %d medicine slots loaded\n", g_medCount);
  JsonDocument res;
  res["success"]  = true;
  res["received"] = g_medCount;
  jsonResponse(200, res);
}

void handleSetTimeout() {
  JsonDocument req;
  if (!parseBody(req)) { jsonError(400, "Invalid JSON"); return; }
  g_timeout = req["timeout_minutes"].as<int>();
  JsonDocument res;
  res["success"]         = true;
  res["timeout_minutes"] = g_timeout;
  jsonResponse(200, res);
}

void handleSyncTime() {
  JsonDocument req;
  if (!parseBody(req)) { jsonError(400, "Invalid JSON"); return; }
  time_t ts  = (time_t)req["timestamp"].as<long long>();
  timeval tv = { ts, 0 };
  settimeofday(&tv, nullptr);
  JsonDocument res;
  res["success"]   = true;
  res["synced_at"] = req["datetime"].as<String>();
  jsonResponse(200, res);
}

void handleRestart() {
  JsonDocument res;
  res["success"] = true;
  res["message"] = "Restarting";
  jsonResponse(200, res);
  delay(300);
  ESP.restart();
}

// ============================================================
//  Business logic
// ============================================================

// 1. Fires once per clock minute; starts a reminder when a schedule matches.
void checkSchedules() {
  String hhmm = getHHMM();

  if (hhmm.isEmpty()) {
    static unsigned long lastWarnMs = 0;
    if (millis() - lastWarnMs > 10000) {
      Serial.println("[WARN] Time not synced — use Device Controls → Sync Time");
      lastWarnMs = millis();
    }
    return;
  }

  if (hhmm == g_lastMinute || g_reminderActive) return;
  g_lastMinute = hhmm;

  Serial.printf("[CHECK] Time=%s | Mode=%s | DoseSlots=%d | MedSlots=%d\n",
                hhmm.c_str(), g_mode.c_str(), g_doseCount, g_medCount);

  if (g_mode == "dose_mode") {
    for (int i = 0; i < g_doseCount; i++) {
      Serial.printf("  slot %d: time=%s enabled=%d\n",
                    g_dose[i].number, g_dose[i].time.c_str(), g_dose[i].enabled);
      if (g_dose[i].enabled && g_dose[i].time == hhmm) {
        Serial.printf("[FIRE] Dose compartment %d\n", g_dose[i].number);
        int idx[1] = { g_dose[i].number - 1 };
        startReminder(idx, 1);
        return;
      }
    }
  } else {
    for (int i = 0; i < g_medCount; i++) {
      Serial.printf("  med '%s': time=%s enabled=%d\n",
                    g_med[i].name.c_str(), g_med[i].time.c_str(), g_med[i].enabled);
      if (g_med[i].enabled && g_med[i].time == hhmm) {
        Serial.printf("[FIRE] Medicine '%s'\n", g_med[i].name.c_str());
        int comps[6];
        for (int j = 0; j < g_med[i].compCount; j++)
          comps[j] = g_med[i].comps[j] - 1;
        startReminder(comps, g_med[i].compCount);
        return;
      }
    }
  }
}

// 2. Two-phase reminder monitor.
//    Phase 1: buzzer rings until lid opens (reed sensor).
//    Phase 2: each compartment's LED turns off when its IR clears.
//             All cleared → Medicine Taken. Timeout → Missed Dose.
void checkReminder() {
  if (!g_reminderActive) return;

  // Timeout check applies in both phases
  if (millis() - g_reminderStartMs >= (unsigned long)g_timeout * 60000UL) {
    stopReminder();
    g_status = "Missed Dose";
    Serial.println("Status → Missed Dose");
    return;
  }

  if (!g_boxOpened) {
    // ── Phase 1: waiting for lid to open ──
    if (digitalRead(REED_PIN) == REED_TAKEN_STATE) {
      noTone(BUZZER_PIN);
      g_boxOpened = true;
      for (int i = 0; i < 6; i++)
        g_irBaseline[i] = digitalRead(IR_PINS[i]);
      Serial.println("[REED] Lid opened → buzzer off, watching IR sensors");
    }
    return;   // ← do NOT read IR sensors until box is open
  }

  // ── Phase 2: lid is open, watch each compartment's IR ──
  int stillPending = 0;
  for (int i = 0; i < 6; i++) {
    if (!g_pendingComps[i]) continue;
    if (digitalRead(IR_PINS[i]) != g_irBaseline[i]) {
      digitalWrite(LED_PINS[i], LOW);
      g_pendingComps[i] = false;
      Serial.printf("[IR] Compartment %d taken\n", i + 1);
    } else {
      stillPending++;
    }
  }

  if (stillPending == 0) {
    stopReminder();
    g_status = "Medicine Taken";
    Serial.println("Status → Medicine Taken");
  }
}

// 3. Buzzer: 1 s on / 1 s off during Phase 1 only.
void tickBuzzer() {
  if (!g_reminderActive || g_boxOpened) return;
  unsigned long sec = (millis() - g_reminderStartMs) / 1000UL;
  if (sec % 2 == 0) tone(BUZZER_PIN, 1000);
  else               noTone(BUZZER_PIN);
}

// ============================================================
//  setup & loop
// ============================================================

void setup() {
  Serial.begin(115200);

  pinMode(BUZZER_PIN, OUTPUT);
  noTone(BUZZER_PIN);
  pinMode(REED_PIN, INPUT_PULLUP);
  for (int i = 0; i < 6; i++) {
    pinMode(IR_PINS[i],  INPUT);
    pinMode(LED_PINS[i], OUTPUT);
    digitalWrite(LED_PINS[i], LOW);
  }

  // Startup self-test: all LEDs + short beep
  Serial.println("\n--- Smart Medicine Box booting ---");
  for (int i = 0; i < 6; i++) digitalWrite(LED_PINS[i], HIGH);
  tone(BUZZER_PIN, 1500); delay(200); noTone(BUZZER_PIN);
  delay(500);
  for (int i = 0; i < 6; i++) digitalWrite(LED_PINS[i], LOW);

  WiFi.config(STATIC_IP, GATEWAY, SUBNET);
  WiFi.begin(WIFI_SSID, WIFI_PASSWORD);
  Serial.print("Connecting to WiFi");
  for (int i = 0; i < 40 && WiFi.status() != WL_CONNECTED; i++) {
    delay(500); Serial.print(".");
  }
  if (WiFi.status() == WL_CONNECTED)
    Serial.println("\nConnected. IP: " + WiFi.localIP().toString());
  else
    Serial.println("\nWiFi FAILED");

  configTime(TZ_OFFSET_SEC, 0, "pool.ntp.org", "time.google.com");

  server.on("/ping",                   HTTP_GET,  handlePing);
  server.on("/status",                 HTTP_GET,  handleStatus);
  server.on("/set-mode",               HTTP_POST, handleSetMode);
  server.on("/set-dose-schedules",     HTTP_POST, handleSetDoseSchedules);
  server.on("/set-medicine-schedules", HTTP_POST, handleSetMedicineSchedules);
  server.on("/set-timeout",            HTTP_POST, handleSetTimeout);
  server.on("/sync-time",              HTTP_POST, handleSyncTime);
  server.on("/restart",                HTTP_POST, handleRestart);

  server.begin();
  Serial.println("HTTP server ready on port 80.");
  Serial.printf("Dose schedules in memory: %d\n", g_doseCount);
  Serial.printf("Medicine schedules in memory: %d\n", g_medCount);
  Serial.println("------------------------------------------");
}

void loop() {
  server.handleClient();
  checkSchedules();
  checkReminder();
  tickBuzzer();
}
