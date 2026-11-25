
import json
import time

import mysql.connector
import paho.mqtt.client as mqtt

from config import DB_CONFIG, MQTT_HOST, MQTT_PORT

# Your BLE sensor information
device_id = "c30000455da7"
device_id_type = 3
last_saved_time = 0

# Connect to MySQL
db = mysql.connector.connect(**DB_CONFIG)

cursor = db.cursor()

print("Connected to MySQL!")

# Called when connected
def on_connect(client, userdata, flags, rc):
    if rc == 0:
        print("Connected to MQTT broker!")

        # Listen to Pareto Anywhere device messages
        client.subscribe("paretoanywhere/devices/#")

        print("Waiting for your device's information...")

    else:
        print("Connection failed:", rc)


# Called when a message is received
def on_message(client, userdata, message):
    global last_saved_time
    try:
        # Convert message into JSON
        data = json.loads(message.payload.decode("utf-8"))

        # Check if this message belongs to your device
        if (str(data.get("deviceId", "")).lower() == device_id.lower()
                and data.get("deviceIdType") == device_id_type):

            
            temperature = data.get("temperature")
            humidity = data.get("relativeHumidity")

            if temperature is None or humidity is None:
                return

            temperature = round(temperature, 2)
            humidity = round(humidity, 2)

            print(json.dumps(data, indent=4))

            current_time = time.time()

            if current_time - last_saved_time >= 10:

                temperature = round(temperature, 2)
                humidity = round(humidity, 2)

                sql = """
                INSERT INTO bluetooth_sensor_readings
                (fridge, temperature, humidity)
                VALUES (%s, %s, %s)
                """

                # Your existing MySQL INSERT code
                cursor.execute(sql, (
                    "BLE Sensor",
                    temperature,
                    humidity
                ))

                db.commit()

                last_saved_time = current_time

                print("Sensor data saved to MySQL!")          
            
            
    except json.JSONDecodeError:
        print("Received a message that is not JSON:", message.topic)


# Create MQTT client
client = mqtt.Client()

client.on_connect = on_connect
client.on_message = on_message

# Connect to Mosquitto
client.connect(MQTT_HOST, MQTT_PORT, 60)

# Listen continuously
client.loop_forever()

