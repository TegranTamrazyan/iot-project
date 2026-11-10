
import os
import re
import time
import serial

os.environ.setdefault(
    "CHECKOUT_URL",
    "http://localhost:8080/checkout/api/scan"
)

from scan_client import send

PORT = "/dev/ttyUSB0"
BAUD = 115200

COMMAND = bytes.fromhex("0008220000000022")


def read_rfid_tags(reader):
    reader.reset_input_buffer()

    start_time = time.monotonic()
    last_poll = 0
    raw_data = bytearray()

    while time.monotonic() - start_time < 2.0:
        now = time.monotonic()

        if now - last_poll >= 0.1:
            reader.write(COMMAND)
            last_poll = now

        if reader.in_waiting > 0:
            raw_data.extend(reader.read(reader.in_waiting))
        else:
            time.sleep(0.01)

    raw_hex = raw_data.hex().upper()

    epcs = re.findall(
        r"(?:A0|E0)[A-F0-9]{22}",
        raw_hex
    )

    if raw_hex and not epcs:
        print("Received data, but no EPCs matched:")
        print(raw_hex[:200])

    return sorted(set(epcs))


def main():
    if not os.getenv("CHECKOUT_SCAN_KEY"):
        print("CHECKOUT_SCAN_KEY is missing.")
        return

    print("Connecting to Chafon CF600...")

    with serial.Serial(PORT, BAUD, timeout=0.2) as reader:
        print("Chafon connected.")
        print("Scanning RFID tags...")

        while True:
            epcs = read_rfid_tags(reader)

            if not epcs:
                continue

            print("RFID tags detected:", epcs)

            try:
                send({"epcs": epcs})
            except Exception as error:
                print("Checkout error:", error)


if __name__ == "__main__":
    try:
        main()
    except KeyboardInterrupt:
        print("\nRFID scanning stopped.")
    except serial.SerialException as error:
        print("Serial connection error:", error)
