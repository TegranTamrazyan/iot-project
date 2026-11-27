# IOT Smart Store — database schema

Database: `iot`. No new database is needed. The fridge tables remain unchanged.

The **database** supports products and stock because checkout depends on them, but **this project does not contain product management or inventory management pages, controllers, or routes**. Those are the teammate's responsibility.

| Table / view | Purpose |
|---|---|
| `checkout_products` | A product type, identified by a unique UPC |
| `checkout_rfid_tags` | Many physical units (unique EPCs) assigned to one product type |
| `checkout_stock_receptions` | Quantity and date received; maintained by teammate's inventory module or imports |
| `checkout_cart_items` | Current barcode/RFID shopping cart entries |
| `checkout_removed_tags` | EPCs that the customer removed from this cart |
| `checkout_orders` | Completed simulated purchases, customer association and loyalty points |
| `checkout_order_items` | Purchased items for purchase history and stock calculations |
| `checkout_sold_epcs` | Physical EPCs that have already been sold |
| `customers` | Hashed password, email, unique membership number and points |
| `checkout_product_details` | View combining UPC product details with registered EPCs |
| `checkout_stock_summary` | View supporting checkout stock validation |

**Upgrade:** Back up existing `iot` data and import `self_checkout_upgrade.sql` once for old checkout tables. **Fresh checkout:** import `self_checkout.sql`. Optional test UPCs, EPCs, and received stock are in `self_checkout_samples.sql`.

The UPC identifies the product type. Every physical RFID-tagged instance should use a distinct EPC, so multiple EPCs can map to one UPC. No new `customers` or fridge database is created.
