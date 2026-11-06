import RPi.GPIO as GPIO
from time import sleep

GPIO.setwarnings(False)
GPIO.setmode(GPIO.BCM)

OUTPUT_PIN = 17 
GPIO.setup(OUTPUT_PIN, GPIO.OUT)

GPIO.output(OUTPUT_PIN, GPIO.HIGH) 
sleep(5)
GPIO.output(OUTPUT_PIN, GPIO.LOW)   

#i did this in case they both want to be on and off together without pulsing
#the instructions never specified so i added this just in case

GPIO.cleanup()