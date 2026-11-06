#include <WiFi.h>
#include <PubSubClient.h>
#include <DHT.h>
#include <ArduinoJson.h>

const char* WIFI_SSID = ""; //replace with wifi
const char* WIFI_PASSWORD = "";  //replace with wifi pass

// Set this to the Raspberry Pi's address on the lab Wi-Fi.
const char* MQTT_SERVER = "192.168.2.119";  //replace with your mqtt server address
const int MQTT_PORT = 1883;
const char* MQTT_CLIENT_ID = "ESP32-Both-Fridges";

#define DHTPIN_FRIG1 14
#define DHTPIN_FRIG2 27
#define DHTTYPE DHT11 

DHT fridge1Sensor(DHTPIN_FRIG1, DHTTYPE);
DHT fridge2Sensor(DHTPIN_FRIG2, DHTTYPE);
WiFiClient wifiClient;
PubSubClient mqtt(wifiClient);

unsigned long lastReadMs = 0;
const unsigned long READ_INTERVAL_MS = 5000;

void connectWiFi() {
  if (WiFi.status() == WL_CONNECTED) return;

  Serial.print("Connecting to Wi-Fi");
  WiFi.begin(WIFI_SSID, WIFI_PASSWORD);
  while (WiFi.status() != WL_CONNECTED) {
    delay(500);
    Serial.print(".");
  }
  Serial.println();
  Serial.print("Wi-Fi connected. IP: ");
  Serial.println(WiFi.localIP());
}

void connectMQTT() {
  while (!mqtt.connected()) {
    Serial.print("Connecting to MQTT... ");
    if (mqtt.connect(MQTT_CLIENT_ID)) {
      Serial.println("connected");
    } else {
      Serial.print("failed, state=");
      Serial.print(mqtt.state());
      Serial.println("; retrying in 3 seconds");
      delay(3000);
    }
  }
}

void publishReading(const char* topic, DHT& sensor) {
  float humidity = sensor.readHumidity();
  float temperature = sensor.readTemperature(); // Celsius

  if (isnan(temperature) || isnan(humidity)) {
    Serial.print("Sensor read failed for ");
    Serial.println(topic);
    return;
  }

  StaticJsonDocument<96> doc;
  doc["temperature"] = temperature;
  doc["humidity"] = humidity;
  char payload[96];
  serializeJson(doc, payload, sizeof(payload));

  bool published = mqtt.publish(topic, payload, true);
  Serial.print(topic);
  Serial.print(" -> ");
  Serial.print(payload);
  Serial.print(" | published=");
  Serial.println(published ? "yes" : "no");
}

void setup() {
  Serial.begin(115200);
  fridge1Sensor.begin();
  fridge2Sensor.begin();
  WiFi.mode(WIFI_STA);
  connectWiFi();
  mqtt.setServer(MQTT_SERVER, MQTT_PORT);
}

void loop() {
  if (WiFi.status() != WL_CONNECTED) connectWiFi();
  if (!mqtt.connected()) connectMQTT();
  mqtt.loop();

  if (millis() - lastReadMs < READ_INTERVAL_MS) return;
  lastReadMs = millis();

  // One ESP32 reads both sensors and publishes each fridge independently.
  publishReading("Frig1", fridge1Sensor);
  publishReading("Frig2", fridge2Sensor);
}
