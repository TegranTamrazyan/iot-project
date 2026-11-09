"""Optional CF600 virtual-serial ASCII-output mode -> PHP EPC batch bridge.

Only use if the reader has been configured to output *line-delimited ASCII EPCs*.
It intentionally does not guess the proprietary binary SDK/USB commands.

Dependencies: pip install pyserial
Example: python3 cf600_serial_bridge.py /dev/ttyUSB0 --baud 115200
"""
import argparse
import re
import time

from scan_client import send



def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('device', help='e.g. /dev/ttyUSB0, actual Pi device')
    parser.add_argument('--baud', type=int, default=115200, help='use actual reader configuration')
    parser.add_argument('--batch-seconds', type=float, default=0.7)
    args = parser.parse_args()
    try:
        import serial
    except ImportError as exc:
        raise SystemExit('pyserial missing: python3 -m pip install pyserial') from exc

    with serial.Serial(args.device, args.baud, timeout=0.2) as reader:
        print('Reading ASCII EPC lines. Ctrl+C to stop.')
        pending = set()
        started = time.monotonic()
        while True:
            raw = reader.readline()
            if raw:
                line = raw.decode('ascii', errors='ignore').strip().upper()
                # The reader MUST output a plain EPC line. Do not treat unknown
                # device data as a valid EPC. No binary commands are sent here.
                if re.fullmatch(r'[0-9A-F]{1,24}', line):
                    pending.add(line)
                else:
                    print('Skipped non-EPC serial output:', repr(line[:80]))
            if time.monotonic() - started >= args.batch_seconds:
                if pending:
                    send({'epcs': sorted(pending)})
                    pending.clear()
                started = time.monotonic()


if __name__ == '__main__':
    main()
