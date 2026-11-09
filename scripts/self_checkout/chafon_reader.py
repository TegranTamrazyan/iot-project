
import argparse
import json
import os
import re
import select
import time
from urllib.request import Request, urlopen
from urllib.error import HTTPError, URLError

URL = os.getenv(
    "CHECKOUT_URL",
    "http://127.0.0.1:8080/checkout/api/scan"
)
SCAN_KEY = os.getenv("CHECKOUT_SCAN_KEY", "")

pending = set()


def add_epc(value):
    epc = value.strip().upper()

    if not re.fullmatch(r"[0-9A-F]{1,24}", epc):
        if epc:
            print("Invalid EPC:", epc)
        return

    pending.add(epc)


def send_scans():
    if not pending:
        return

    epcs = sorted(pending)

    request = Request(
        URL,
        data=json.dumps({"epcs": epcs}).encode("utf-8"),
        headers={
            "Content-Type": "application/json",
            "X-Checkout-Key": SCAN_KEY
        },
        method="POST"
    )

    try:
        with urlopen(request, timeout=5) as response:
            result = json.load(response)

        print("Added:", result.get("added", []))
        print("Unknown:", result.get("unknown", []))
        print("Ignored:", result.get("ignored", []))

        pending.difference_update(epcs)

    except (HTTPError, URLError, TimeoutError) as error:
        print("Could not send scans:", error)


def read_serial(device, baud, batch_seconds):
    import serial

    with serial.Serial(device, baud, timeout=0.1) as reader:
        print("Reading Chafon serial output...")

        last_send = time.monotonic()

        while True:
            raw = reader.readline()

            if raw:
                epc = raw.decode(
                    "ascii", errors="ignore"
                ).strip()
                add_epc(epc)

            if time.monotonic() - last_send >= batch_seconds:
                send_scans()
                last_send = time.monotonic()


def read_keyboard(device, batch_seconds):
    from evdev import InputDevice, ecodes

    reader = InputDevice(device)

    print("Reading Chafon keyboard output...")
    print("Device:", reader.name)

    buffer = ""
    last_key = time.monotonic()
    last_send = time.monotonic()

    while True:
        ready, _, _ = select.select([reader], [], [], 0.1)

        if ready:
            for event in reader.read():
                if event.type != ecodes.EV_KEY:
                    continue

                if event.value != 1:
                    continue

                key = ecodes.KEY.get(event.code, "")

                if key in ("KEY_ENTER", "KEY_KPENTER"):
                    add_epc(buffer)
                    buffer = ""
                    continue

                if key.startswith("KEY_KP"):
                    character = key.replace("KEY_KP", "")
                else:
                    character = key.replace("KEY_", "")

                if len(character) == 1:
                    if character in "0123456789ABCDEF":
                        buffer += character
                        last_key = time.monotonic()

        # Some readers don't send Enter after the EPC.
        if buffer and time.monotonic() - last_key > 0.3:
            add_epc(buffer)
            buffer = ""

        if time.monotonic() - last_send >= batch_seconds:
            send_scans()
            last_send = time.monotonic()


def main():
    parser = argparse.ArgumentParser()

    parser.add_argument(
        "mode",
        choices=["serial", "keyboard"]
    )

    parser.add_argument("device")

    parser.add_argument(
        "--baud",
        type=int,
        default=115200
    )

    parser.add_argument(
        "--batch",
        type=float,
        default=1.0
    )

    args = parser.parse_args()

    if not SCAN_KEY:
        raise SystemExit("Set CHECKOUT_SCAN_KEY first.")

    if args.mode == "serial":
        read_serial(args.device, args.baud, args.batch)
    else:
        read_keyboard(args.device, args.batch)


if __name__ == "__main__":
    try:
        main()
    except KeyboardInterrupt:
        print("\nScanner stopped.")
