# IOT Smart Store — Phase 3

This build contains **self-checkout**, **customer registration and login**, **membership numbers**, **points**, and **purchase history**. The existing **fridge dashboard** is retained.

Product management, stock receiving / inventory management screens, receipts, receipt emails, notifications and translations are **not implemented in this build**. Those modules are assigned to teammates.

## Install / replace project files

1. Back up your current project folder and your MariaDB `iot` database.
2. Unzip and replace your project source with these files. **Keep your existing `config/env.php` and `scripts/fridge_iot/.env`** with their correct local settings. Do not put Python `.env` text into the PHP `config/env.php`.
3. If Composer dependencies are missing, run `composer install` in the project root.
4. On a fresh checkout installation, import `database/self_checkout.sql` into the existing `iot` database. For an earlier checkout installation, import **only** `database/self_checkout_upgrade.sql` (back up first; intended to be run once). Never drop the fridge tables.
5. Optional: `database/self_checkout_samples.sql` inserts test products, EPC tags and sample stock receptions. To use real scanners, register the UPCs/EPCs physically present on your products.

For now, product catalog and stock reception records come from the existing database or from the teammate's module. **The checkout still checks stock** using `checkout_stock_receptions`; that database table is deliberately retained. The absence of the inventory page does not remove those records.

## Run the web server (Linux / Raspberry Pi)

```bash
cd /home/tico1/Music/iot-project-main
export CHECKOUT_SCAN_KEY='REPLACE_WITH_YOUR_PRIVATE_SCANNER_KEY'
php -S localhost:8080 -t public
```

If your project folder lives elsewhere, change the `cd` path. Start PHP from the folder containing `composer.json`.

## Pages

| URL | File |
| --- | --- |
| `/` | Redirects to `/checkout` |
| `/checkout` | `app/Views/checkout/index.php` |
| `/checkout/confirm` | `app/Views/checkout/confirm.php` |
| `/checkout/done` | `app/Views/checkout/done.php` (payment status, not a receipt) |
| `/adding` | `app/Views/addingUser_view.php` |
| `/login` | `app/Views/loginUserView.php` |
| `/account` | `app/Views/account/index.php` |
| `/fridge-dashboard` | Existing fridge monitor |

**No `/inventory`, `/products`, or `/inventory/receive` route is provided.**

## Database and scanners

- A UPC identifies a *product type* (e.g. Banana). Each physical RFID-tagged unit has its own unique EPC, registered in `checkout_rfid_tags` and linked to the same `checkout_products` entry.
- Each distinct EPC is counted once per cart; repeated barcode scans can add more units without EPC information.
- Product stock is calculated from `checkout_stock_receptions` minus completed orders. Keep these tables even though the inventory-management interface is excluded.
- `/checkout/api/scan` accepts scanner input with the `X-Checkout-Key` secret. The optional Python scanner bridge files live in `scripts/self_checkout/`.
- The CF600 physical RFID mode must be verified on the Raspberry Pi; the serial bridge works only when the reader outputs plain text EPCs over serial.
- Purchase history and points are persisted after simulated payment. Passwords are hashed; customers receive a six-digit membership number.

The checkout is JavaScript-free: its optional browser refresh is controlled by `?live=1` and the Pause/Resume button. The fridge dashboard keeps its pre-existing scripts. No real payments are made.

## Main files

- Header navigation and brand: `app/Views/common/header.php`
- Footer (shared): `app/Views/common/footer.php` and `app/Views/checkout/no_js_footer.php`
- Checkout UI: `app/Views/checkout/index.php`
- Checkout routes: `app/Routes/web-routes.php`
- Checkout logic: `app/Controllers/CheckoutController.php`, `app/Domain/Models/CheckoutModel.php`
- Customer accounts: `app/Controllers/UserController.php`, `app/Domain/Models/UserModel.php`
- Bootstrap customization: `public/assets/css/store.css`
- SQL schema/migration: `database/self_checkout.sql`, `database/self_checkout_upgrade.sql`

### Intentional removals in this revision

`app/Controllers/InventoryController.php`, `app/Domain/Models/InventoryModel.php`, `app/Views/inventory/index.php`, and the inventory routes were removed. The checkout stock tables, views, and validation logic **were not deleted**, since the teammate's module and checkout logic still need them.
