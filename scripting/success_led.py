import RPi.GPIO as GPIO # Import Raspberry Pi GPIO library
from time import sleep # Import the sleep function from the time module
GPIO.setwarnings(False) # Ignore warning for now
GPIO.setmode(GPIO.BCM) # Use BCM Addressing pin numbering
LED=18
GPIO.setup(LED, GPIO.OUT) # Set pin 17 to be an output pin
GPIO.output(LED, GPIO.HIGH) # Turn on
sleep(5) # Sleep for 1 second
GPIO.output(LED, GPIO.LOW) # Turn off
GPIO.cleanup()
