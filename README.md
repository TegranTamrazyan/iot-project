# IOT Smart Store


A lightweight MVC web application starter template built on top of the Slim PHP microframework. Ideal for projects that require Slim's simplicity without sacrificing the benefits of a clean MVC architecture.
PHP / Slim MVC IoT store running on Raspberry Pi with Bootstrap.

The included functionality is **self-checkout**, barcode and batch RFID input, a shopping cart, simulated payment, customer registration and password login, membership numbers, loyalty points and a customer purchase-report portal. Customers can filter receipts and spending by date, search past purchases by item, view each purchase price and compare prices over time. The existing **fridge dashboard** remains available.

**Not included:** Product management, inventory management interface, notification module, or language translation. Those features are assigned separately.

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
| `/account` | Membership number, points, filtered receipts, spending totals and item price history |
| `/fridge-dashboard` | Existing fridge monitor |

**```NOTE:```** You can always clone this repository. However, if you do, you need to remove the ```.git``` ***hidden*** directory before you copy this template over to ```htdocs```

## How Do I Configure My Database Connection?

Follow the outlined instructions in [config/env.example.php](config/env.example.php)

* Change the value of the `database` variable to reflect the name of the database to be used by your slim app.
* You may also want to change the connection credentials in that file.

## How do I Use Composer with Wampoon?

To install or update your project dependencies deployed on Wampoon, use the `composer.bat` script as follows:

| Action                | Command                            | Description                                                                     |
| --------------------- | ---------------------------------- | ------------------------------------------------------------------------------- |
| Install dependencies  | `./composer.bat install`           | Installs packages listed in `composer.json` and creates the `vendor` directory. |
| Update dependencies   | `./composer.bat update`            | Refreshes all packages to the latest versions allowed by `composer.json`.       |
| Add a package         | `./composer.bat require [package]` | Installs a new package and adds it to `composer.json`.                          |
| Regenerate autoloader | `./composer.bat dump-autoload -o`  | Rebuilds the optimized autoloader after adding or removing classes.             |


## On Using Environment Variables

Sensitive information used in app such as your database credentials, API key, etc. MUST not be pushed into your Git repo.

Do not use `.env` files for storing environment specific application settings/configurations. Dotenv [is not meant to be used in production](https://github.com/vlucas/phpdotenv/issues/76#issuecomment-87252126)

Just Google: "DB_PASSWORD" filetype:env
Alternatively, you can visit the following link: [Google env search](https://www.google.ch/search?q=%22DB_PASSWORD%22+filetype:env)

Instead, follow the instructions that are detailed in [config/env.example.php](config/env.example.php)

## Project Structure

Here's how everything is organized:

```plaintext
slim-mvc/
├── app/
│   ├── Controllers/    # Your controllers live here
│   ├── Domain/         # Domain logic and business rules
│   │   ├── Models/     # Data models and entities
│   │   └── Services/   # Business logic services
│   ├── Helpers/        # Utility classes and helpers
│   ├── Middleware/     # Custom middleware
│   ├── Routes/         # Route definitions (web & API)
│   └── Views/          # Your view templates
├── config/             # Configuration files and bootstrap
├── data/               # Database files, uploads, etc.
├── docker/             # Docker configuration files
│   ├── apache.conf     # Apache virtual host config
│   └── init-db/        # Database initialization scripts
├── docs/               # Documentation
├── public/             # Web-accessible files
│   ├── assets/         # Static assets (CSS, JS, images)
│   │   ├── css/        # Stylesheets
│   │   └── js/         # JavaScript files
│   ├── index.php       # Application entry point
│   └── .htaccess       # Apache rewrite rules
├── var/                # Runtime files
│   └── logs/           # Application logs
├── vendor/             # Composer dependencies
├── Dockerfile          # Docker image definition
└── docker-compose.yml  # Docker services configuration
```

## Quick Development Tips

### Adding Routes

Routes are defined in the `app/Routes/` directory. Check out the existing route files to see how it's done.

### Creating Controllers

Controllers go in `app/Controllers/`. They should extend the base controller class and follow PSR-4 autoloading.

### Views and Templates

Templates are stored in `app/Views/`. The template engine is already configured and ready to use.

### Configuration

App configuration lives in `config/`. Modify these files to customize your application settings.

### Logging

Logs are written to the `var/logs/` directory. Use the injected logger in your controllers to track what's happening.

## Need Help?

- Check out the [Slim documentation](https://www.slimframework.com/docs/v4/) for framework-specific questions.
- Look at the example controllers and routes to see how everything fits together.
- The code is pretty well commented, so don't hesitate to explore it well.

## Contributing

Got ideas for improvements? Found a bug? Pull requests are welcome!

- [Issues](https://github.com/frostybee/slim-mvc/issues)

## Acknowledgments

The application's bootstrap process and structure of this starter template is based on [slim4-skeleton](https://github.com/odan/slim4-skeleton) by [@odan](https://github.com/odan).  Many thanks to the original developers for their work!

## License

This project is open-sourced under the MIT License. See the `LICENSE` file for the full details.

---

## Refrigerator IoT dashboard

The refrigerator-monitoring additions are documented in [`docs/fridge_iot_setup.md`](docs/fridge_iot_setup.md).

- Dashboard view: `app/Views/fridge_dashboard/`; dashboard controllers: `app/Controllers/FridgeDashboard/`; database connection/model: `app/Domain/Models/FridgeDashboardModel.php`. Public entry points remain in `public/fridge-dashboard/` (open `/fridge-dashboard/` on your web server).
- Raspberry Pi MQTT/email/motor controller: `scripts/fridge_iot/controller.py`
- ESP32 sketch: `scripts/fridge_iot/esp32/fridge_sensor.ino`
- IoT database tables: `data/fridge_iot_schema.sql` (uses the existing `iot` database and does not modify `customers`)

Configure `scripts/fridge_iot/.env` to match the database credentials in `config/env.php` before starting the Python controller.
The database retains stock and product records for checkout validations; the teammate owns the interfaces to manage those records.
