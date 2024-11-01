#include <WiFi.h>
#include <HTTPClient.h>
#include <Wire.h>
#include <LiquidCrystal_I2C.h>
#include <ESP32_MailClient.h>
#include <ArduinoJson.h>

// Wi-Fi credentials
const char* ssid = "leaksense";
const char* password = "Zxczxc3#";

// API Endpoints
const char* saveReadingsUrl = "http://192.168.137.1/api/save_readings.php";
const char* getRecipientsUrl = "http://192.168.137.1/api/get_recipients.php";
const char* getThresholdsUrl = "http://192.168.137.1/api/get_thresholds.php";

// LCD setup (I2C address 0x27, 16 columns, 2 rows)
LiquidCrystal_I2C lcd(0x27, 16, 2);

// Device ID
#define DEVICE_ID "GS1"

// Email settings
#define emailSenderAccount "leaksense.fanshawe@gmail.com"
#define emailSenderPassword "wpej wzfg bihi vhjn"
#define smtpServer "smtp.gmail.com"
#define smtpServerPort 465
#define emailSubject "ALERT! Gas Leak Detected"

// Pin definitions
#define LED_PIN 27
#define BUZZER_PIN 26
#define GAS_SENSOR_PIN 35

// Variables for thresholds
float SMOKE_THRESHOLD = 1.8;
float CO_THRESHOLD = 2.0;
float LPG_THRESHOLD = 2.5;

// Timer variables for periodic threshold fetching
unsigned long lastFetchTime = 0;
const unsigned long fetchInterval = 3000; // Fetch every 3 seconds (adjust as needed)

// Flag for email
bool emailSent = false;

// Function prototypes
void connectToWiFi();
bool fetchThresholds();
String sendGasDataAndGetRecipients(float gasValue, String gasType, int smokeStatus, int coStatus, int lpgStatus);
String detectGasType(float gasValue);
void handleGasDetection(bool gasDetected);
void displayGasLevel(float gasValue, String gasType);
bool sendEmailNotification(float gasValue, String gasType, String recipientResponse);

void setup() {
  Serial.begin(115200);
  lcd.init();
  lcd.backlight();

  pinMode(LED_PIN, OUTPUT);
  pinMode(BUZZER_PIN, OUTPUT);
  digitalWrite(LED_PIN, LOW);
  digitalWrite(BUZZER_PIN, LOW);

  connectToWiFi();
  fetchThresholds(); // Initial threshold fetch on startup
}

void loop() {
  // Check if it's time to fetch updated thresholds
  if (millis() - lastFetchTime >= fetchInterval) {
    if (fetchThresholds()) {
      Serial.printf("Updated thresholds - Smoke: %.2f, CO: %.2f, LPG: %.2f\n", SMOKE_THRESHOLD, CO_THRESHOLD, LPG_THRESHOLD);
    }
    lastFetchTime = millis();
  }

  int rawValue = analogRead(GAS_SENSOR_PIN);
  float gasValue = (rawValue / 1023.0) * 1.0;
  String gasType = detectGasType(gasValue);

  displayGasLevel(gasValue, gasType);
  Serial.printf("Gas Level: %.2f - Type: %s\n", gasValue, gasType.c_str());

  int smokeStatus = (gasType == "Smoke") ? 1 : 0;
  int coStatus = (gasType == "CO") ? 1 : 0;
  int lpgStatus = (gasType == "LPG") ? 1 : 0;

  String recipientResponse = sendGasDataAndGetRecipients(gasValue, gasType, smokeStatus, coStatus, lpgStatus);
  bool gasDetected = (gasType != "No Gas Detected");
  handleGasDetection(gasDetected);

  if (gasValue > LPG_THRESHOLD && !emailSent) {
    if (sendEmailNotification(gasValue, gasType, recipientResponse)) {
      emailSent = true;
    }
  } else if (gasValue < LPG_THRESHOLD && emailSent) {
    emailSent = false;
  }

  delay(3000);
}

void connectToWiFi() {
  lcd.setCursor(0, 0);
  lcd.print("Connecting...");
  WiFi.begin(ssid, password);

  while (WiFi.status() != WL_CONNECTED) {
    delay(500);
    lcd.setCursor(0, 1);
    lcd.print("...");
  }

  lcd.clear();
  lcd.setCursor(0, 0);
  lcd.print("WiFi connected");
  lcd.setCursor(0, 1);
  lcd.print(WiFi.localIP());
  delay(2000);
  lcd.clear();

  Serial.println("WiFi connected, IP address: ");
  Serial.println(WiFi.localIP());
}

bool fetchThresholds() {
  if (WiFi.status() == WL_CONNECTED) {
    HTTPClient http;
    http.begin(getThresholdsUrl);
    int httpResponseCode = http.GET();

    if (httpResponseCode > 0) {
      String payload = http.getString();
      DynamicJsonDocument doc(512);
      deserializeJson(doc, payload);

      JsonArray thresholds = doc["thresholds"];
      for (JsonVariant threshold : thresholds) {
        if (String(threshold["device_id"].as<const char*>()) == DEVICE_ID) {
          SMOKE_THRESHOLD = threshold["smoke_threshold"].as<float>();
          CO_THRESHOLD = threshold["co_threshold"].as<float>();
          LPG_THRESHOLD = threshold["lpg_threshold"].as<float>();
          break;
        }
      }

      Serial.printf("Up to dated thresholds - Smoke: %.2f, CO: %.2f, LPG: %.2f\n", SMOKE_THRESHOLD, CO_THRESHOLD, LPG_THRESHOLD);
      http.end();
      return true;
    } else {
      Serial.printf("Error in fetching thresholds: %d\n", httpResponseCode);
    }

    http.end();
  }
  return false;
}

String detectGasType(float gasValue) {
  if (gasValue >= LPG_THRESHOLD) {
    return "LPG";
  } else if (gasValue >= SMOKE_THRESHOLD) {
    return "Smoke";
  } else if (gasValue >= CO_THRESHOLD) {
    return "CO";
  } else {
    return "No Gas Detected";
  }
}

String sendGasDataAndGetRecipients(float gasValue, String gasType, int smokeStatus, int coStatus, int lpgStatus) {
  String recipientResponse = "";

  if (WiFi.status() == WL_CONNECTED) {
    HTTPClient http;
    http.begin(saveReadingsUrl);
    http.addHeader("Content-Type", "application/x-www-form-urlencoded");

    String postData = "device_id=" + String(DEVICE_ID) +
                      "&gas_level=" + String(gasValue, 2) +
                      "&gas_type=" + gasType +
                      "&smoke_status=" + String(smokeStatus) +
                      "&co_status=" + String(coStatus) +
                      "&lpg_status=" + String(lpgStatus);

    int httpResponseCode = http.POST(postData);

    if (httpResponseCode > 0) {
      recipientResponse = http.getString();
      Serial.println("POST Response (Recipients & Thresholds): " + recipientResponse);
    } else {
      Serial.printf("Error in sending POST: %d\n", httpResponseCode);
    }

    http.end();
  } else {
    Serial.println("WiFi disconnected, unable to send data");
  }

  return recipientResponse;
}

void handleGasDetection(bool gasDetected) {
  if (gasDetected) {
    for (int i = 0; i < 5; i++) {
      digitalWrite(LED_PIN, HIGH);
      digitalWrite(BUZZER_PIN, HIGH);
      delay(300);
      digitalWrite(LED_PIN, LOW);
      digitalWrite(BUZZER_PIN, LOW);
      delay(300);
    }
  }
}

void displayGasLevel(float gasValue, String gasType) {
  lcd.clear();
  lcd.setCursor(0, 0);
  lcd.print("Gas Level: ");
  lcd.print(gasValue, 2);
  lcd.setCursor(0, 1);
  lcd.print(gasType);
}

bool sendEmailNotification(float gasValue, String gasType, String recipientResponse) {
  if (WiFi.status() != WL_CONNECTED) {
    Serial.println("WiFi not connected. Cannot send email.");
    return false;
  }

  String emailMessage = "Gas Leak Detected!\nLevel: " + String(gasValue, 2) + " (" + gasType + ")";
  SMTPData smtpData;
  smtpData.setLogin(smtpServer, smtpServerPort, emailSenderAccount, emailSenderPassword);
  smtpData.setSender("LeakSense System", emailSenderAccount);
  smtpData.setPriority("High");
  smtpData.setSubject(emailSubject);
  smtpData.setMessage(emailMessage, false);

  DynamicJsonDocument doc(512);
  deserializeJson(doc, recipientResponse);
  JsonArray recipients = doc["recipients"];

  Serial.println("Sending email to recipients:");
  for (JsonVariant recipient : recipients) {
    String email = recipient.as<String>();
    smtpData.addRecipient(email);
    Serial.println(email);
  }

  if (MailClient.sendMail(smtpData)) {
    Serial.println("Email sent successfully!");
    smtpData.empty();
    return true;
  } else {
    Serial.printf("Error sending Email: %s\n", MailClient.smtpErrorReason().c_str());
    smtpData.empty();
    return false;
  }
}
