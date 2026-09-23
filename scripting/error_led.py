import RPi.GPIO as GPIO
import time
GPIO.setwarnings(False)
GPIO.setmode(GPIO.BCM)
LED = 17
GPIO.setup(LED, GPIO.OUT)
BUZZER = 12
GPIO.setup(BUZZER, GPIO.OUT)
pulse = GPIO.PWM(BUZZER, 100)  # 100Hz frequency
pulse.start(0)
GPIO.output(LED, GPIO.HIGH)
# Beep 3 times
for x in range(3):
    pulse.ChangeDutyCycle(50)  # 50% duty cycle = buzzer on
    time.sleep(0.5)
    pulse.ChangeDutyCycle(0)   # 0% duty cycle = buzzer off
    time.sleep(0.3)
time.sleep(1)
GPIO.output(LED, GPIO.LOW)
pulse.stop()
GPIO.cleanup()
