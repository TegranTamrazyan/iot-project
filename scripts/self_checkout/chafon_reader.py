
import os
import time
import serial

os.environ["CHECKOUT_URL"] = "http://localhost:8080/rfid/scan"

from scan_client import send

PORT = "/dev/ttyUSB0"
BAUD = 115200

COMMAND = bytes.fromhex("0008220000000022")

# Keep True until we've confirmed the EPCs are correct.
DEBUG_ONLY = False

# Frame structure observed in your CF600 output.
HEADER = bytes.fromhex("0000CF0100011200")
FRAME_LENGTH = 27
EPC_LENGTH = 12


def extract_epcs(buffer):
    epcs = set()

    while True:
        # Find the beginning of a complete reader response.
        start = buffer.find(HEADER)

        if start == -1:
            # Preserve possible partial headers for the next read.
            keep = min(len(buffer), len(HEADER) - 1)
            del buffer[:len(buffer) - keep]
            break

        # Discard anything before the valid header.
        if start > 0:
            del buffer[:start]

        # Wait until the entire record has arrived.
        if len(buffer) < FRAME_LENGTH:
            break

        frame = bytes(buffer[:FRAME_LENGTH])

        # Check the metadata and EPC length observed in your output.
        if frame[10:13] != bytes.fromhex("01000C"):
            del buffer[0]
            continue

        # Extract exactly the 12 EPC bytes, not the surrounding data.
        epc = frame[13:25].hex().upper()

        if len(epc) == EPC_LENGTH * 2 and epc != "0" * 24:
            epcs.add(epc)

        # Remove the processed record.
        del buffer[:FRAME_LENGTH]

    return epcs


def read_rfid_tags(reader, buffer):
    start_time = time.monotonic()
    last_poll = 0
    epcs = set()

    while time.monotonic() - start_time < 2.0:
        now = time.monotonic()

        if now - last_poll >= 0.1:
            reader.write(COMMAND)
            last_poll = now

        if reader.in_waiting > 0:
            buffer.extend(reader.read(reader.in_waiting))

            # Parse complete records immediately.
            epcs.update(extract_epcs(buffer))
        else:
            time.sleep(0.01)

    return sorted(epcs)


def main():
    if not os.getenv("CHECKOUT_SCAN_KEY"):
        print("CHECKOUT_SCAN_KEY is missing.")
        return

    print("Connecting to Chafon CF600...")

    with serial.Serial(PORT, BAUD, timeout=0.2) as reader:
        print("Chafon connected.")
        print("Scanning RFID tags...")

        # Keep incomplete records between scan cycles.
        buffer = bytearray()

        while True:
            epcs = read_rfid_tags(reader, buffer)

            if not epcs:
                continue

            print("RFID tags detected:", epcs)

            # Don't save anything during testing.
            if DEBUG_ONLY:
                continue

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
