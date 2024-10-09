#include <WiFi.h>
#include <HTTPClient.h>
#include <Wire.h>
#include <LiquidCrystal_I2C.h>
#include <ESP32_MailClient.h>
#include <ArduinoJson.h> // Include ArduinoJson for parsing JSON

// Wi-Fi credentials
const char* ssid = "leaksense";
const char* password = "Zxczxc3#";

// Server API endpoint for saving readings
const char* serverName = "http://192.168.137.1/api/save_readings.php"; // Change to your server URL

// LCD setup (I2C address 0x27, 16 columns, 2 rows)
LiquidCrystal_I2C lcd(0x27, 16, 2);

// Device ID for the ESP32
#define DEVICE_ID "GS1" // Change to "GS2" for the second ESP32

// Gas detection thresholds (Calibrated values)
#define SMOKE_THRESHOLD 1.8  // Example threshold for smoke detection
#define CO_THRESHOLD 2.0     // Example threshold for CO detection
#define LPG_THRESHOLD 2.5    // Example threshold for LPG detection

// Email settings
#define emailSenderAccount    "leaksense.fanshawe@gmail.com" // Sender email address
#define emailSenderPassword   "wpej wzfg bihi vhjn" // App password or actual password if less secure apps are enabled
#define smtpServer            "smtp.gmail.com"
#define smtpServerPort        465  // Switch to SSL with port 465
#define emailSubject          "ALERT! Gas Leak Detected (Gas-Sensor 1 Area)" // Email subject

// Pin definitions
#define LED_PIN 27       // LED pin
#define BUZZER_PIN 26    // Buzzer pin
#define GAS_SENSOR_PIN 35 // MQ2 sensor analog pin

// Function prototypes
void connectToWiFi();
String sendGasDataAndGetRecipients(float gasValue, String gasType, int smokeStatus, int coStatus, int lpgStatus);
String detectGasType(float gasValue);
void handleGasDetection(bool gasDetected);
void displayGasLevel(float gasValue, String gasType);
bool sendEmailNotification(float gasValue, String gasType, String recipientResponse);

// Flag variable to keep track if email notification was sent or not
bool emailSent = false;

void setup() {
  // Initialize Serial Monitor
  Serial.begin(115200);

  // Initialize LCD
  lcd.init();
  lcd.backlight();

  // Initialize LED and Buzzer
  pinMode(LED_PIN, OUTPUT);
  pinMode(BUZZER_PIN, OUTPUT);
  digitalWrite(LED_PIN, LOW);
  digitalWrite(BUZZER_PIN, LOW);

  // Connect to Wi-Fi
  connectToWiFi();
}

void loop() {
  // Read gas sensor value and scale it to appropriate range (0-5V)
  int rawValue = analogRead(GAS_SENSOR_PIN);
  float gasValue = (rawValue / 1023.0) * 1.0; // Adjusted to scale from 0 to 5V

  // Determine the type of gas detected
  String gasType = detectGasType(gasValue);

  // Display gas level and type on LCD
  displayGasLevel(gasValue, gasType);

  // Print gas level and type to Serial Monitor
  Serial.print("Gas Level: ");
  Serial.print(gasValue, 2);
  Serial.print(" - Type: ");
  Serial.println(gasType);

  // Determine status flags based on the gas type and threshold
  int smokeStatus = (gasType == "Smoke") ? 1 : 0;
  int coStatus = (gasType == "CO") ? 1 : 0;
  int lpgStatus = (gasType == "LPG") ? 1 : 0;

  // Send data to the server and get recipient data from the response
  String recipientResponse = sendGasDataAndGetRecipients(gasValue, gasType, smokeStatus, coStatus, lpgStatus);

  // Check if any gas is detected (gasType is not "No Gas Detected")
  bool gasDetected = (gasType != "No Gas Detected");
  
  // Handle gas detection (blink LED, sound buzzer)
  handleGasDetection(gasDetected);

  // Send email alert if gas level exceeds LPG threshold
  if (gasValue > SMOKE_THRESHOLD && !emailSent) {
    if (sendEmailNotification(gasValue, gasType, recipientResponse)) {
      emailSent = true; // Set flag to avoid sending multiple emails
    }
  } else if (gasValue < SMOKE_THRESHOLD && emailSent) {
    emailSent = false; // Reset flag if gas level is back to safe
  }

  // Wait for 3 seconds before the next reading
  delay(3000);
}

// Function to connect to Wi-Fi
void connectToWiFi() {
  lcd.setCursor(0, 0);
  lcd.print("Connecting...");
  WiFi.begin(ssid, password);

  // Wait until connected
  while (WiFi.status() != WL_CONNECTED) {
    delay(500);
    Serial.print(".");
    lcd.setCursor(0, 1);
    lcd.print("...");
  }

  // Display connection success
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

// Function to detect gas type based on calibrated thresholds
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

// Function to send gas data to the server and return the recipients from the response
String sendGasDataAndGetRecipients(float gasValue, String gasType, int smokeStatus, int coStatus, int lpgStatus) {
  String recipientResponse = "";
  
  if (WiFi.status() == WL_CONNECTED) {
    HTTPClient http;
    http.begin(serverName);
    http.addHeader("Content-Type", "application/x-www-form-urlencoded");

    // Prepare the POST data
    String postData = "device_id=" + String(DEVICE_ID) +
                      "&gas_level=" + String(gasValue, 2) +
                      "&gas_type=" + gasType +
                      "&smoke_status=" + String(smokeStatus) +
                      "&co_status=" + String(coStatus) +
                      "&lpg_status=" + String(lpgStatus);

    // Send the POST request
    int httpResponseCode = http.POST(postData);

    // Check response code
    if (httpResponseCode > 0) {
      recipientResponse = http.getString();  // Get the response with recipients
      Serial.println("POST Response: " + recipientResponse);
    } else {
      Serial.println("Error in sending POST: " + String(httpResponseCode));
    }

    http.end(); // End HTTP connection
  } else {
    Serial.println("WiFi disconnected, unable to send data");
  }

  return recipientResponse; // Return the response containing recipients
}

// Function to handle gas detection (LED, buzzer)
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

// Function to display gas level and type on the LCD
void displayGasLevel(float gasValue, String gasType) {
  lcd.clear();
  lcd.setCursor(0, 0);
  lcd.print("Gas Level: ");
  lcd.print(gasValue, 2); // Display gas value with 2 decimal places
  lcd.setCursor(0, 1);
  lcd.print(gasType);
}

// Function to send email notification with dynamic recipients from the POST response
bool sendEmailNotification(float gasValue, String gasType, String recipientResponse) {
  // Check if Wi-Fi is connected
  if (WiFi.status() != WL_CONNECTED) {
    Serial.println("WiFi not connected. Cannot send email.");
    return false;
  }

  // Simplified email message
  String emailMessage = "Gas Leak Detected!\nLevel: " + String(gasValue, 2) + " (" + gasType + ")";

  // Set the SMTP Server Email host, port, account, and password
  SMTPData smtpData;
  smtpData.setLogin(smtpServer, smtpServerPort, emailSenderAccount, emailSenderPassword);
  smtpData.setSender("LeakSense System", emailSenderAccount);
  smtpData.setPriority("High");
  smtpData.setSubject(emailSubject);
  
  // Send as plain text to minimize size
  smtpData.setMessage(emailMessage, false);  // Set 'false' to send plain text instead of HTML

  // Parse recipients from recipientResponse JSON
  DynamicJsonDocument doc(512);
  deserializeJson(doc, recipientResponse);
  JsonArray recipients = doc["recipients"];

  // Add each recipient from the POST response
  for (JsonVariant recipient : recipients) {
    smtpData.addRecipient(recipient.as<String>());
  }

  // Try sending the email
  if (MailClient.sendMail(smtpData)) {
    Serial.println("Email sent successfully!");
    smtpData.empty();
    return true;
  } else {
    Serial.print("Error sending Email: ");
    Serial.println(MailClient.smtpErrorReason());
    smtpData.empty();
    return false;
  }
}