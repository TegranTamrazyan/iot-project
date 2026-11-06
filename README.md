# IOT Smart Store

PHP / Slim MVC IoT store running on Raspberry Pi with Bootstrap.

The included functionality is **self-checkout**, barcode and batch RFID input, a shopping cart, simulated payment, customer registration and password login, membership numbers, loyalty points and purchase history. The existing **fridge dashboard** remains available.

**Not included:** Product management, inventory management interface, receipts and receipt emailing, notification module, or language translation. Those features are assigned separately.

## Setup

See [PHASE3_SETUP_README.md](PHASE3_SETUP_README.md) for installation, file locations, database changes and scanner configuration.

Quick start:

```bash
cd /home/tico1/Music/iot-project-main
composer install
export CHECKOUT_SCAN_KEY='YOUR_LONG_RANDOM_SCANNER_SECRET'
php -S localhost:8080 -t public
```

Visit `http://localhost:8080/checkout`.

The source code uses root-based web routes with no `/iot-project-main` URL prefix. Keep your existing private `config/env.php` and `scripts/fridge_iot/.env` after updating your project. Neither file is included in the ZIP.

## Project pages

| Route | Page |
|---|---|
| `/checkout` | Self-checkout cart and scanners |
| `/checkout/confirm` | Review, membership and simulated payment |
| `/adding` | Customer registration |
| `/login` | Customer login |
| `/account` | Membership number, points and purchases |
| `/fridge-dashboard` | Existing fridge monitor |

The database retains stock and product records for checkout validations; the teammate owns the interfaces to manage those records.
