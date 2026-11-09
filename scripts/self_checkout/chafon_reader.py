"""Chafon USB RFID adapter placeholder.

The exact model, USB mode and protocol are unknown. This script deliberately
DOES NOT invent reader commands. Once the Linux reader SDK/protocol is known,
implement read_inventory_epcs() and call scan_client.send({'epcs': epcs}).

For now, test multiple EPCs with: python3 scan_client.py epcs EPC1 EPC2
"""
from scan_client import send

def read_inventory_epcs():
    raise NotImplementedError('Need exact Chafon model and Linux SDK/protocol to read tags.')

if __name__ == '__main__':
    send({'epcs': read_inventory_epcs()})
