"""Send USB scanner output to the PHP cart. Python 3 standard library only.

Examples:
  python3 scan_client.py upc 012345678905
  python3 scan_client.py epcs 300833B2DDD9014000000001 300833B2DDD9014000000002
  python3 scan_client.py keyboard  # barcode scanner acting as keyboard: scan then Enter

Set CHECKOUT_URL and CHECKOUT_SCAN_KEY before running.
"""
import json
import os
import sys
from urllib.request import Request, urlopen
from urllib.error import HTTPError

URL = os.environ.get('CHECKOUT_URL', 'http://127.0.0.1:8080/checkout/api/scan')
KEY = os.environ.get('CHECKOUT_SCAN_KEY', '')

def send(payload):
    if not KEY:
        raise SystemExit('Set CHECKOUT_SCAN_KEY first.')
    req = Request(URL, data=json.dumps(payload).encode(), headers={
        'Content-Type': 'application/json', 'X-Checkout-Key': KEY}, method='POST')
    try:
        with urlopen(req, timeout=10) as response:
            print(response.read().decode())
    except HTTPError as error:
        print(f'HTTP {error.code}: {error.read().decode()}')

if __name__ == '__main__':
    if len(sys.argv) < 2:
        raise SystemExit('Usage: scan_client.py upc CODE | epcs EPC1 EPC2 | keyboard')
    mode = sys.argv[1]
    if mode == 'upc' and len(sys.argv) == 3:
        send({'upc': sys.argv[2]})
    elif mode == 'epcs' and len(sys.argv) >= 3:
        send({'epcs': sys.argv[2:]})
    elif mode == 'keyboard':
        print('Ready for keyboard-emulating USB barcode scans. Ctrl+C to exit.')
        while True:
            value = input('Scan UPC: ').strip()
            if value:
                send({'upc': value})
    else:
        raise SystemExit('Usage: scan_client.py upc CODE | epcs EPC1 EPC2 | keyboard')
