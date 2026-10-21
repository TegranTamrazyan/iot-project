"""Controls the fan and keeps it synchronized with the website dashboard."""

import time

import database
import config


class FanController:
    def __init__(self, mqtt_client):
        self.mqtt_client = mqtt_client
        self.last_applied_state = None
        self.gpio = None

        # Only load Raspberry Pi GPIO when controlling a real motor.
        # Simulation mode also lets this program run on a regular computer.
        if not config.SIMULATE_MOTOR:
            import RPi.GPIO as GPIO

            self.gpio = GPIO
            self.gpio.setmode(self.gpio.BCM)
            self.gpio.setwarnings(False)
            self.gpio.setup(config.MOTOR_ENABLE_PIN, self.gpio.OUT)
            self.gpio.setup(config.MOTOR_IN1_PIN, self.gpio.OUT)
            self.gpio.setup(config.MOTOR_IN2_PIN, self.gpio.OUT)
            self._apply_motor(False)

    def _apply_motor(self, is_on):
        """Send ON/OFF signals to the motor driver (or simulate them)."""
        if config.SIMULATE_MOTOR:
            print(f"[SIMULATION] Fan would turn {'ON' if is_on else 'OFF'}.")
            return

        # Connect the motor through a motor driver, never directly to GPIO.
        self.gpio.output(
            config.MOTOR_IN1_PIN,
            self.gpio.HIGH if is_on else self.gpio.LOW,
        )
        self.gpio.output(config.MOTOR_IN2_PIN, self.gpio.LOW)
        self.gpio.output(
            config.MOTOR_ENABLE_PIN,
            self.gpio.HIGH if is_on else self.gpio.LOW,
        )

    def set_state(self, is_on, source="controller"):
        """Save, apply, and publish the fan state."""
        status = "ON" if is_on else "OFF"

        database.save_fan_state(status)

        try:
            self._apply_motor(is_on)
        except Exception as error:
            print("Motor error:", error)
            return False

        self.last_applied_state = status
        self._publish_state(status)
        print(f"Fan state is {status} (source={source}).")
        return True

    def _publish_state(self, status):
        """Publish retained state so subscribers can see the latest value."""
        self.mqtt_client.publish("store/fan/status", status, retain=True)

    def publish_database_state(self):
        """Publish the current database state after MQTT connects."""
        status = database.get_fan_state()
        if status in ("ON", "OFF"):
            self._publish_state(status)

    def sync_with_database(self):
        """Check every second for ON/OFF changes made in the dashboard."""
        while True:
            try:
                desired_state = database.get_fan_state()

                if desired_state not in ("ON", "OFF"):
                    print("Fan state row is missing. Check the SQL schema.")
                elif desired_state != self.last_applied_state:
                    try:
                        self._apply_motor(desired_state == "ON")
                    except Exception as error:
                        print("Motor error:", error)
                    else:
                        self.last_applied_state = desired_state
                        self._publish_state(desired_state)
                        print(f"Fan synchronized with database: {desired_state}")
            except Exception as error:
                print("Fan synchronization error:", type(error).__name__, str(error))

            time.sleep(1)

    def shutdown(self):
        """Turn the motor off and clean up GPIO when the program stops."""
        if self.gpio is not None:
            try:
                self._apply_motor(False)
                self.gpio.cleanup()
            except Exception as error:
                print("GPIO cleanup error:", error)
