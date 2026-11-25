# Fridge IoT Controller — Quick Setup

## Files

- `controller.py` — starts the program and receives ESP32 readings.
- `config.py` — reads settings from `.env`.
- `database.py` — saves readings and reads thresholds/fan state.
- `email_handler.py` — sends alert emails and checks replies.
- `fan_control.py` — controls the fan and synchronizes it with the dashboard.
- `esp32/fridge_sensor.ino` — ESP32 code; open this in Arduino IDE, not Thonny.

## 1. Install Python packages

Run this in a terminal on the Raspberry Pi, from this folder:

```bash
python3 -m pip install -r requirements.txt
```

If your Raspberry Pi blocks system-wide pip installs, use a virtual environment:

```bash
python3 -m venv .venv
source .venv/bin/activate
pip install -r requirements.txt
```

## 2. Create `.env`

Create a file named `.env` in this folder. Add and update these settings:

```dotenv
MQTT_HOST=127.0.0.1
MQTT_PORT=1883
MQTT_CLIENT_ID=fridge-pi-controller

DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=iot
DB_USER=YOUR_DATABASE_USER
DB_PASSWORD=YOUR_DATABASE_PASSWORD

SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
IMAP_HOST=imap.gmail.com
IMAP_PORT=993
EMAIL_USER=YOUR_GMAIL_ADDRESS
EMAIL_PASSWORD=YOUR_GMAIL_APP_PASSWORD
ALERT_TO=YOUR_ALERT_RECIPIENT
ALLOWED_REPLY_FROM=YOUR_ALERT_RECIPIENT

MOTOR_ENABLE_PIN=22
MOTOR_IN1_PIN=27
MOTOR_IN2_PIN=17
SIMULATE_MOTOR=1
ALERT_COOLDOWN_SECONDS=900
EMAIL_POLL_SECONDS=20
```

Use a Gmail app password, not your regular Gmail password. Keep `.env` private.

## 3. Prepare the database and services

Make sure MySQL/MariaDB and Mosquitto are running. The database named `iot` must contain `sensor_readings`, `bluetooth_sensor_readings`, `thresholds`, and `fan_state`. The DHT readings are loaded from `sensor_readings`; Bluetooth readings are loaded from `bluetooth_sensor_readings`.

The fridge tables are defined in `data/iot.sql`. For an existing `iot` database without `bluetooth_sensor_readings`, run that table's `CREATE TABLE` statement from `data/iot.sql` before starting `bluetooth_sensor.py`; it stores the Bluetooth temperature, humidity, and timestamp for the dashboard.

## 4. Upload the ESP32 code

Open `esp32/fridge_sensor.ino` in Arduino IDE. Install the `PubSubClient`, `ArduinoJson`, `DHT sensor library`, and `Adafruit Unified Sensor` libraries. Set the Wi-Fi credentials and Raspberry Pi IP address in the sketch, select the ESP32 board and port, then upload it.

## 5. Run the Python controller

From this folder, run:

```bash
python3 controller.py
```

You can also open `controller.py` in Thonny and press **Run**. Keep `SIMULATE_MOTOR=1` until the motor driver and GPIO wiring have been checked.

The controller receives readings on MQTT topics `Frig1` and `Frig2`, saves them to MySQL, and publishes dashboard data to `store/Frig1/data`, `store/Frig2/data`, and `store/fan/status`.
