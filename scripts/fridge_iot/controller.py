"""Main program: receives ESP32 readings and connects the other components."""

import json
import subprocess
import threading
import time

import paho.mqtt.client as mqtt

import config
import database
from email_handler import EmailHandler
from fan_control import FanController


# Remember when each fridge last sent an alert, to avoid spamming emails.
last_alert_time = {"Frig1": 0, "Frig2": 0}
was_too_warm = {"Frig1": False, "Frig2": False}
latest_temperatures = {"Frig1": None, "Frig2": None}


def notify(title, message):
    """Print a notification and show a desktop popup when available."""
    print(f"NOTIFICATION: {title}: {message}")

    try:
        subprocess.run(
            ["notify-send", title, message],
            timeout=3,
            check=False,
            stdout=subprocess.DEVNULL,
            stderr=subprocess.DEVNULL,
        )
    except (FileNotFoundError, subprocess.TimeoutExpired):
        # Desktop popups are optional; terminal messages still work.
        pass


# Create the MQTT client first, then give it to the fan controller.
client = mqtt.Client(
    mqtt.CallbackAPIVersion.VERSION2,
    client_id=config.MQTT_CLIENT_ID,
)
fan = FanController(client)
email_handler = EmailHandler(fan.set_state, notify)


def on_connect(mqtt_client, userdata, flags, reason_code, properties):
    """Subscribe to both fridge topics whenever MQTT connects/reconnects."""
    print("Connected to MQTT:", reason_code)

    for fridge in latest_temperatures:
        latest_temperatures[fridge] = None

    mqtt_client.subscribe("Frig1")
    mqtt_client.subscribe("Frig2")

    # Send the saved fan state to MQTT after each connection.
    try:
        fan.publish_database_state()
    except Exception as error:
        print("Could not publish saved fan state:", error)

def turn_off_if_both_ok():
    """Turn the fan off when both fridges' latest temperatures are normal."""
    if any(value is None for value in latest_temperatures.values()):
        return

    both_ok = all(
        temperature <= database.get_threshold(fridge)
        for fridge, temperature in latest_temperatures.items()
    )

    if both_ok and database.get_fan_state() == "ON":
        success = fan.set_state(False, source="temperatures normal")

        if success:
            notify(
                "Fan turned off",
                "Both fridges are back within their temperature limits.",
            )


def on_message(mqtt_client, userdata, message):
    """Handle one temperature/humidity message from an ESP32 fridge sensor."""
    fridge = message.topic

    if fridge not in ("Frig1", "Frig2"):
        return

    # Convert the incoming JSON into normal Python numbers.
    try:
        reading = json.loads(message.payload.decode("utf-8"))
        temperature = float(reading["temperature"])
        humidity = float(reading["humidity"])

        # Ignore impossible sensor values instead of saving bad data.
        if not (-40 <= temperature <= 80):
            raise ValueError("Temperature is outside the expected range.")
        if not (0 <= humidity <= 100):
            raise ValueError("Humidity is outside the expected range.")

    except (UnicodeDecodeError, json.JSONDecodeError, KeyError, TypeError, ValueError) as error:
        print(f"Invalid sensor message from {fridge}:", error)
        return

    try:
        # Save the reading and publish the latest values for the dashboard.
        database.save_reading(fridge, temperature, humidity)
        latest_temperatures[fridge] = temperature
        mqtt_client.publish(
            f"store/{fridge}/data",
            json.dumps({"temperature": temperature, "humidity": humidity}),
            retain=True,
        )

        # Check whether this fridge is warmer than its configured limit.
        threshold = database.get_threshold(fridge)
        too_warm = temperature > threshold
        now = time.time()

        # Alert when the temperature first goes too high, or when the cooldown ends.
        alert_due = too_warm and (
            not was_too_warm[fridge]
            or now - last_alert_time[fridge] >= config.ALERT_COOLDOWN_SECONDS
        )

        if alert_due:
            email_handler.send_alert(fridge, temperature, threshold)
            notify(
                f"{fridge} temperature alert",
                f"Temperature: {temperature:.1f} C; limit: {threshold:.1f} C.",
            )
            last_alert_time[fridge] = now

        was_too_warm[fridge] = too_warm

        turn_off_if_both_ok()

        print(
            f"{fridge}: temperature={temperature:.2f} C, "
            f"humidity={humidity:.2f}%, limit={threshold:.2f} C"
        )

    except Exception as error:
        print("Could not process sensor reading:", type(error).__name__, str(error))


# Tell the MQTT client which functions to call for connections and messages.
client.on_connect = on_connect
client.on_message = on_message


def main():
    """Start the background tasks and keep MQTT running."""
    print("Starting fridge monitoring system...")
    print(f"MQTT broker: {config.MQTT_HOST}:{config.MQTT_PORT}")
    print(f"Motor simulation: {'ON' if config.SIMULATE_MOTOR else 'OFF'}")

    # The dashboard fan sync and email checker run in the background.
    threading.Thread(target=fan.sync_with_database, daemon=True).start()
    threading.Thread(target=email_handler.check_replies, daemon=True).start()

    try:
        client.connect(config.MQTT_HOST, config.MQTT_PORT, 60)
        # Keep receiving messages; Paho will attempt to reconnect if disconnected.
        client.loop_forever()
    except KeyboardInterrupt:
        print("Stopping fridge monitoring system...")
    except Exception as error:
        print("Controller stopped because of an error:", type(error).__name__, str(error))
    finally:
        fan.shutdown()
        try:
            client.disconnect()
        except Exception:
            pass
        print("Controller stopped.")


if __name__ == "__main__":
    main()
