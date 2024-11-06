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
#define DEVICE_ID "GS2"

// Email settings
#define emailSenderAccount "leaksense.fanshawe@gmail.com"
#define emailSenderPassword "wpej wzfg bihi vhjn"
#define smtpServer "smtp.gmail.com"
#define smtpServerPort 465
#define emailSubject "ALERT! Gas Leak Detected GS2"

// Pin definitions
#define LED_PIN 27
#define BUZZER_PIN 26
#define GAS_SENSOR_PIN 35

// Variables for thresholds
float SMOKE_THRESHOLD = 1.8;
float CO_THRESHOLD = 2.0;
float LPG_THRESHOLD = 2.5;

// Store fetched recipients
std::vector<String> emailRecipients;

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
bool fetchEmailRecipients();
bool sendGasData(float gasValue, String gasType, int smokeStatus, int coStatus, int lpgStatus);
String detectGasType(float gasValue);
void handleGasDetection(bool gasDetected);
void displayGasLevel(float gasValue, String gasType);
bool sendEmailNotification(float gasValue, String gasType);
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
  fetchEmailRecipients(); // Initial email recipient fetch
  
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
        fetchThresholds();
        lastFetchTime = millis();
    }

    int rawValue = analogRead(GAS_SENSOR_PIN);
    float gasValue = (rawValue / 1023.0) * 1.0; // Scale to 5V range
    String gasType = detectGasType(gasValue);

    displayGasLevel(gasValue, gasType);
    Serial.printf("Gas Level: %.2f - Type: %s\n", gasValue, gasType.c_str());

    int smokeStatus = (gasType == "Smoke") ? 1 : 0;
    int coStatus = (gasType == "CO") ? 1 : 0;
    int lpgStatus = (gasType == "LPG") ? 1 : 0;

    bool gasDetected = (gasType != "No Gas Detected");
    handleGasDetection(gasDetected);

    // Send gas data every loop
    if (sendGasData(gasValue, gasType, smokeStatus, coStatus, lpgStatus)) {
        Serial.println("Gas data sent successfully.");
    } else {
        Serial.println("Failed to send gas data.");
    }

    // Check if thresholds are exceeded for email alert
    if (((gasType == "Smoke" && gasValue > SMOKE_THRESHOLD) ||
         (gasType == "CO" && gasValue > CO_THRESHOLD) ||
         (gasType == "LPG" && gasValue > LPG_THRESHOLD)) && !emailSent) {
        
        Serial.println("Threshold exceeded, attempting to send email...");
        if (sendEmailNotification(gasValue, gasType)) {
            emailSent = true;
        } else {
            Serial.println("Email sending failed. Retrying...");
        }
    } else if (gasValue < min(SMOKE_THRESHOLD, min(CO_THRESHOLD, LPG_THRESHOLD)) && emailSent) {
        Serial.println("Gas level below all thresholds, reset emailSent flag.");
        emailSent = false;
    }

    delay(3000); // Delay for 3 seconds before the next loop iteration
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

// Fetch updated gas thresholds from the server
bool fetchThresholds() {
  if (WiFi.status() == WL_CONNECTED) {
    HTTPClient http;
    http.begin(getThresholdsUrl + "?device_id=" + DEVICE_ID);
    
    int httpResponseCode = http.GET();
    if (httpResponseCode > 0) {
      String payload = http.getString();
      Serial.println("Threshold JSON response:");
      Serial.println(payload);
      
      DynamicJsonDocument doc(512);
      deserializeJson(doc, payload);

      if (doc.containsKey("thresholds")) {
        JsonObject threshold = doc["thresholds"][0];
        SMOKE_THRESHOLD = threshold["smoke_threshold"].as<float>();
        CO_THRESHOLD = threshold["co_threshold"].as<float>();
        LPG_THRESHOLD = threshold["lpg_threshold"].as<float>();
        Serial.printf("Updated thresholds - Smoke: %.2f, CO: %.2f, LPG: %.2f\n", SMOKE_THRESHOLD, CO_THRESHOLD, LPG_THRESHOLD);
        http.end();
        return true;
      } else {
        Serial.println("Error: No thresholds found for this device ID.");
      }
    } else {
      Serial.printf("Error fetching thresholds: %d\n", httpResponseCode);
    }

    http.end();
  }
  return false;
}

// OTA setup function
void startOTA() {
  ArduinoOTA.setHostname("LeakSense-GS2");
  ArduinoOTA.begin();
}

// Fetch email recipients and store in emailRecipients array
bool fetchEmailRecipients() {
  if (WiFi.status() == WL_CONNECTED) {
    HTTPClient http;
    http.begin(getRecipientsUrl);
    
    int httpResponseCode = http.GET();
    if (httpResponseCode > 0) {
      String payload = http.getString();
      Serial.println("Recipient JSON response:");
      Serial.println(payload);
      
      DynamicJsonDocument doc(512);
      DeserializationError error = deserializeJson(doc, payload);

      if (error) {
        Serial.print("JSON deserialization failed for recipients: ");
        Serial.println(error.c_str());
        return false;
      }

      emailRecipients.clear();
      JsonArray recipients = doc["recipients"];
      for (JsonVariant recipient : recipients) {
        emailRecipients.push_back(recipient.as<String>());
      }
      Serial.println("Fetched and stored email recipients successfully.");
      http.end();
      return true;
    } else {
      Serial.printf("Error fetching recipients: %d\n", httpResponseCode);
      http.end();
    }
  }
  return false;
}

// Determine gas type based on sensor value
String detectGasType(float gasValue) {
  if (gasValue >= LPG_THRESHOLD) return "LPG";
  else if (gasValue >= SMOKE_THRESHOLD) return "Smoke";
  else if (gasValue >= CO_THRESHOLD) return "CO";
  return "No Gas Detected";
}

// Display gas level on LCD
void displayGasLevel(float gasValue, String gasType) {
  lcd.clear();
  lcd.setCursor(0, 0);
  lcd.print("Gas Level: ");
  lcd.print(gasValue, 2);
  lcd.setCursor(0, 1);
  lcd.print(gasType);
}

// Trigger LED and buzzer if gas is detected
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

// Web server root page for status
void handleRoot() {
  String html = "<html><body>";
  html += "<h1>LeakSense System GS2-(ESP32-1)</h1>";
  html += "<p>Firmware Version: ";
  html += firmwareVersion;
  html += "</p>";
  html += "<p>Device IP: ";
  html += WiFi.localIP().toString();
  html += "</p>";
  html += "</body></html>";
  
  server.send(200, "text/html", html);
}

// Send gas data to the server
bool sendGasData(float gasValue, String gasType, int smokeStatus, int coStatus, int lpgStatus) {
    if (WiFi.status() == WL_CONNECTED) {
        HTTPClient http;
        http.begin(saveReadingsUrl);

        // Prepare the POST data
        String postData = "device_id=" + String(DEVICE_ID) +
                          "&gas_level=" + String(gasValue) +
                          "&gas_type=" + gasType +
                          "&smoke_status=" + String(smokeStatus) +
                          "&co_status=" + String(coStatus) +
                          "&lpg_status=" + String(lpgStatus);

        http.addHeader("Content-Type", "application/x-www-form-urlencoded"); // Set content type to URL encoded
        int httpResponseCode = http.POST(postData); // Send POST request

        // Check the response code
        if (httpResponseCode > 0) {
            String payload = http.getString();
            Serial.printf("HTTP Response code: %d\n", httpResponseCode);
            Serial.println("Response from server: " + payload);
            http.end();
            return true; // Data sent successfully
        } else {
            Serial.printf("Error sending data: %s\n", http.errorToString(httpResponseCode).c_str());
        }
        http.end();
    } else {
        Serial.println("WiFi not connected.");
    }
    return false; // Failed to send data
}

// Send email notification with recipients
bool sendEmailNotification(float gasValue, String gasType) {
  if (WiFi.status() != WL_CONNECTED) {
    Serial.println("WiFi not connected. Cannot send email.");
    return false;
  }

  if (emailRecipients.empty()) {
    Serial.println("No recipients to send email to.");
    return false;
  }

  String emailMessage = "Gas Leak Alert!\nLevel: " + String(gasValue, 2) + " (" + gasType + ")";
  SMTPData smtpData;

  smtpData.setLogin(smtpServer, smtpServerPort, emailSenderAccount, emailSenderPassword);
  smtpData.setSender("LeakSense System", emailSenderAccount);
  smtpData.setPriority("High");
  smtpData.setSubject(emailSubject);
  smtpData.setMessage(emailMessage, false);

  for (String &recipient : emailRecipients) {
    smtpData.addRecipient(recipient);
  }
  
// for email sending debug
  // smtpData.setDebug(1);

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
