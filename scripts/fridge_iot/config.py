"""Settings for the fridge monitoring controller.

Edit the .env file in this folder to change these values.
"""

import os
from pathlib import Path

from dotenv import load_dotenv

# Load .env from this file's folder, even if Python is started from Thonny
# with a different working directory.
load_dotenv(Path(__file__).with_name(".env"))

# MQTT connects the ESP32 to the Raspberry Pi controller.
MQTT_HOST = os.getenv("MQTT_HOST", "127.0.0.1")
MQTT_PORT = int(os.getenv("MQTT_PORT", "1883"))
MQTT_CLIENT_ID = os.getenv("MQTT_CLIENT_ID", "fridge-pi-controller")

# MySQL/MariaDB connection details.
DB_CONFIG = {
    "host": os.getenv("DB_HOST", "127.0.0.1"),
    "port": int(os.getenv("DB_PORT", "3306")),
    "database": os.getenv("DB_NAME", "iot"),
    "user": os.getenv("DB_USER", "fridge_app"),
    "password": os.getenv("DB_PASSWORD", ""),
}

# Email settings. Gmail users should use an app password.
SMTP_HOST = os.getenv("SMTP_HOST", "smtp.gmail.com")
SMTP_PORT = int(os.getenv("SMTP_PORT", "587"))
IMAP_HOST = os.getenv("IMAP_HOST", "imap.gmail.com")
IMAP_PORT = int(os.getenv("IMAP_PORT", "993"))
EMAIL_USER = os.getenv("EMAIL_USER", "")
EMAIL_PASSWORD = os.getenv("EMAIL_PASSWORD", "")
ALERT_TO = os.getenv("ALERT_TO", "")
ALLOWED_REPLY_FROM = os.getenv("ALLOWED_REPLY_FROM", ALERT_TO).strip().lower()

# Email alert timing.
ALERT_COOLDOWN_SECONDS = int(os.getenv("ALERT_COOLDOWN_SECONDS", "900"))
EMAIL_POLL_SECONDS = int(os.getenv("EMAIL_POLL_SECONDS", "20"))

# Raspberry Pi GPIO pins used by the motor driver.
MOTOR_ENABLE_PIN = int(os.getenv("MOTOR_ENABLE_PIN", "22"))
MOTOR_IN1_PIN = int(os.getenv("MOTOR_IN1_PIN", "27"))
MOTOR_IN2_PIN = int(os.getenv("MOTOR_IN2_PIN", "17"))

# Keep 1 while testing: the motor will not physically run.
SIMULATE_MOTOR = os.getenv("SIMULATE_MOTOR", "1").strip() == "1"
