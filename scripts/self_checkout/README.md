# Scanner integration (Raspberry Pi / Linux)

## UPC barcode input

USB scanners often emulate a keyboard. Open `/checkout`, pause refresh, focus the UPC field and scan. If the device sends Enter, the HTML form may submit; otherwise press Add.

## Multiple EPCs in one request

The Python bridge sends an array of EPCs to `/checkout/api/scan`; backend de-duplicates repeated reads of the same EPC. To simulate:

```bash
export CHECKOUT_SCAN_KEY='CHANGE_TO_A_LONG_RANDOM_SCANNER_SECRET'
python3 scripts/self_checkout/scan_client.py epcs 000001 000002
```

The PHP server must be started with the same `CHECKOUT_SCAN_KEY`, and the EPCs must already be in `checkout_rfid_tags`.

## Physical Chafon CF600 reader

Chafon documents **USB virtual serial output, keyboard emulation and USB SDK read/write** modes. These are not interchangeable. See https://www.chafontech.com/productinfo/1111081.html and manufacturer SDK at https://www.chafon.com/download.

For a CF600 configured to send **plain line-oriented hexadecimal EPC text via a USB virtual serial port** (not the binary command protocol):

```bash
python3 -m pip install pyserial
ls /dev/ttyUSB* /dev/ttyACM* 2>/dev/null
export CHECKOUT_SCAN_KEY='CHANGE_TO_A_LONG_RANDOM_SCANNER_SECRET'
python3 scripts/self_checkout/cf600_serial_bridge.py /dev/ttyUSB0 --baud 115200
```

Use the **actual baud rate and device node** from your reader's configuration. The script batches unique lines from the device; this is conditional on the reader's output format. It **does not** initialize/command a proprietary reader or verify real simultaneous multi-tag detection. If it outputs binary data, use your teacher's SDK to implement `chafon_reader.py` rather than guessing a protocol.

Set `CHECKOUT_URL` if your PHP server doesn't run at `http://127.0.0.1:8080/checkout/api/scan`.

## Important

UPC identifies the product type, EPC identifies the individual tagged physical unit. Do not scan both the barcode and EPC of the *same* physical unit in the same checkout because they cannot be reconciled without a shared item identity.
