# Start IOT Smart Store on the Raspberry Pi

Full instructions: `PHASE3_SETUP_README.md`.

```bash
cd /home/tico1/Music/iot-project-main
composer install
export CHECKOUT_SCAN_KEY='YOUR_LONG_RANDOM_SCANNER_SECRET'
php -S localhost:8080 -t public
```

Pages: `/checkout`, `/checkout/confirm`, `/adding`, `/login`, `/account`, `/fridge-dashboard`.

**Product and inventory management are teammate-owned and absent from this version.**

For an existing checkout database, back up `iot` before importing `database/self_checkout_upgrade.sql` (one-time migration only). For a fresh checkout schema, import `database/self_checkout.sql`. Test data is in `database/self_checkout_samples.sql`.

Retain your own `config/env.php` (PHP configuration), and `scripts/fridge_iot/.env` (Python settings); neither private file is in this archive. No extra `/iot-project-main` prefix is used in the URLs.
