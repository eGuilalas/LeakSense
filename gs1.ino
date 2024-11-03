#include <WiFi.h>
#include <HTTPClient.h>
#include <Wire.h>
#include <LiquidCrystal_I2C.h>
#include <ESP32_MailClient.h>
#include <ArduinoJson.h>
#include <WebServer.h>
#include <ArduinoOTA.h>

// Wi-Fi credentials
const char* ssid = "leaksense";
const char* password = "Zxczxc3#";

// Firmware version
const char* firmwareVersion = "1.0.1"; 

// API Endpoints
String saveReadingsUrl = "http://192.168.137.1/leaksense/api/save_readings.php";
String getRecipientsUrl = "http://192.168.137.1/leaksense/api/get_recipients.php";
String getThresholdsUrl = "http://192.168.137.1/leaksense/api/get_thresholds.php";

// LCD setup (I2C address 0x27, 16 columns, 2 rows)
LiquidCrystal_I2C lcd(0x27, 16, 2);

// Device ID
#define DEVICE_ID "GS1"

// Email settings
#define emailSenderAccount "leaksense.fanshawe@gmail.com"
#define emailSenderPassword "wpej wzfg bihi vhjn"
#define smtpServer "smtp.gmail.com"
#define smtpServerPort 465
#define emailSubject "ALERT! Gas Leak Detected GS1"

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
const unsigned long fetchInterval = 3000;

// Flag for email
bool emailSent = false;

// Web server on port 80
WebServer server(80);

// Function prototypes
void connectToWiFi();
bool fetchThresholds();
String sendGasDataAndGetRecipients(float gasValue, String gasType, int smokeStatus, int coStatus, int lpgStatus);
String detectGasType(float gasValue);
void handleGasDetection(bool gasDetected);
void displayGasLevel(float gasValue, String gasType);
bool sendEmailNotification(float gasValue, String gasType, String recipientResponse);
void handleRoot();
void startOTA();

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
  
  // Setup OTA updates
  startOTA();

  // Setup web server routes
  server.on("/", handleRoot);
  server.begin();
  Serial.println("Web server started, ready for requests.");
}

void loop() {
  server.handleClient(); // Handle web server requests
  ArduinoOTA.handle();   // Handle OTA updates

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
    } else {
      Serial.println("Email sending failed. Retrying...");
      emailSent = sendEmailNotification(gasValue, gasType, recipientResponse);
    }
  } else if (gasValue < LPG_THRESHOLD && emailSent) {
    emailSent = false;
  }

  delay(3000);
}

// Connect to Wi-Fi and display IP on LCD
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

// Function to handle the root webpage, displaying firmware info
void handleRoot() {
  String html = "<html><body>";
  html += "<h1>LeakSense System GS1-(ESP32-1)</h1>";
  html += "<p>Firmware Version: ";
  html += firmwareVersion;
  html += "</p>";
  html += "<p>Device IP: ";
  html += WiFi.localIP().toString();
  html += "</p>";
  html += "</body></html>";
  
  server.send(200, "text/html", html);
}

// Setup OTA update
void startOTA() {
  ArduinoOTA.setHostname("LeakSense-GS1");
  ArduinoOTA.onStart([]() {
    String type = (ArduinoOTA.getCommand() == U_FLASH) ? "sketch" : "filesystem";
    Serial.println("Start updating " + type);
  });
  ArduinoOTA.onEnd([]() {
    Serial.println("\nEnd");
  });
  ArduinoOTA.onError([](ota_error_t error) {
    Serial.printf("Error[%u]: ", error);
    if (error == OTA_AUTH_ERROR) Serial.println("Auth Failed");
    else if (error == OTA_BEGIN_ERROR) Serial.println("Begin Failed");
    else if (error == OTA_CONNECT_ERROR) Serial.println("Connect Failed");
    else if (error == OTA_RECEIVE_ERROR) Serial.println("Receive Failed");
    else if (error == OTA_END_ERROR) Serial.println("End Failed");
  });
  ArduinoOTA.begin();
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

      Serial.printf("Updated thresholds - Smoke: %.2f, CO: %.2f, LPG: %.2f\n", SMOKE_THRESHOLD, CO_THRESHOLD, LPG_THRESHOLD);
      http.end();
      return true;
    } else {
      Serial.printf("Error fetching thresholds: %d\n", httpResponseCode);
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
    Serial.println("Attempting to send email to: " + email);
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
