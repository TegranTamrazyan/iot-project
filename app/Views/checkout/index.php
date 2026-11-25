<?php

use App\Helpers\Core\FlashMessage;
use App\Helpers\ViewHelper;
use App\Helpers\CheckoutSecurity;

ViewHelper::loadHeader('Self Checkout');

$csrf = CheckoutSecurity::csrfToken();
$h = static fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');

// Keep the physical RFID scanner in checkout mode while this page is active.
$_SESSION['checkout_scanner_token'] ??= bin2hex(random_bytes(16));

// Only replace the shopping-list HTML when its data actually changes.
$cartVersion = hash('sha256', json_encode(
    [$cart, $removed, $subtotal, $tax, $total],
    JSON_THROW_ON_ERROR
));

?>

<div class="container py-4">

    <section class="store-hero p-4 p-md-5 mb-4 d-flex justify-content-between flex-wrap gap-3">
        <div>
            <h1 class="fw-bold display-6 mb-0">Self-checkout</h1>
        </div>
    </section>

    <?= FlashMessage::render() ?>

    <?php if ($error !== ''): ?>
        <div class="alert alert-danger" role="alert"><?= $h($error) ?></div>
    <?php endif; ?>

    <div class="row g-4">

        <div class="col-lg-4">

            <section class="card store-card mb-4">
                <div class="card-body p-4">

                    <h2 class="h5 fw-bold">Barcode / UPC</h2>

                    <form method="post" action="<?= BASE_URL ?>checkout/add">

                        <input
                            type="hidden"
                            name="csrf"
                            value="<?= $h($csrf) ?>"
                        >

                        <label for="upc" class="form-label">
                            Product UPC
                        </label>

                        <input
                            id="upc"
                            name="upc"
                            class="form-control mb-3"
                            inputmode="numeric"
                            pattern="[0-9]{1,13}"
                            maxlength="13"
                            placeholder="001"
                            required
                        >

                        <button class="btn btn-primary w-100">
                            Add scanned UPC
                        </button>

                    </form>

                </div>
            </section>

            <section class="card store-card">
                <div class="card-body p-4">

                    <h2 class="h5 fw-bold">RFID / EPC</h2>

                    <form method="post" action="<?= BASE_URL ?>checkout/add">

                        <input
                            type="hidden"
                            name="csrf"
                            value="<?= $h($csrf) ?>"
                        >

                        <label for="epcs" class="form-label">
                            Scan EPCs
                        </label>

                        <textarea
                            class="form-control mb-3"
                            id="epcs"
                            name="epcs"
                            rows="4"
                            placeholder="000001&#10;000002"
                            required
                        ></textarea>

                        <button class="btn btn-outline-primary w-100">
                            Add RFID items
                        </button>

                    </form>

                </div>
            </section>

        </div>

        <div class="col-lg-8">

            <!-- Only this area is updated automatically; input forms stay untouched. -->
            <div id="checkout-cart-content" data-cart-version="<?= $h($cartVersion) ?>">

            <section class="card store-card">
                <div class="card-body p-4">

                    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">

                        <h2 class="h4 mb-0 fw-bold">
                            Shopping list
                        </h2>

                        <small class="text-muted">Updates automatically</small>

                    </div>

                    <div class="table-responsive">

                        <table class="table align-middle">

                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th class="text-center">Qty</th>
                                    <th class="text-end">Total</th>
                                </tr>
                            </thead>

                            <tbody>

                                <?php foreach ($groups as $g): ?>
                                    <tr>
                                        <td>

                                            <div class="fw-semibold">
                                                <?= $h($g['name']) ?>
                                            </div>

                                            <div class="text-muted small">
                                                UPC <?= $h($g['upc']) ?>
                                                · $<?= number_format($g['price'], 2) ?> / unit
                                            </div>

                                            <?php foreach ($g['units'] as $unit): ?>

                                                <div class="d-flex align-items-center flex-wrap gap-2 mt-1">

                                                    <span class="unit-chip">
                                                        <?= $unit['epc'] ? 'EPC ' . $h($unit['epc']) : 'Barcode scan' ?>
                                                    </span>

                                                    <form action="<?= BASE_URL ?>checkout/remove" method="post">

                                                        <input
                                                            type="hidden"
                                                            name="csrf"
                                                            value="<?= $h($csrf) ?>"
                                                        >

                                                        <input
                                                            type="hidden"
                                                            name="id"
                                                            value="<?= (int)$unit['id'] ?>"
                                                        >

                                                        <button
                                                            type="submit"
                                                            class="btn btn-link btn-sm text-danger p-0"
                                                        >
                                                            Remove unit
                                                        </button>

                                                    </form>

                                                </div>

                                            <?php endforeach; ?>

                                        </td>

                                        <td class="text-center fw-bold">
                                            <?= (int)$g['quantity'] ?>
                                        </td>

                                        <td class="text-end money">
                                            $<?= number_format($g['line_total'], 2) ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>

                                <?php if (!$groups): ?>
                                    <tr>
                                        <td colspan="3" class="text-muted text-center py-5">
                                            Your cart is empty. Scan a product to start.
                                        </td>
                                    </tr>
                                <?php endif; ?>

                            </tbody>

                        </table>

                    </div>

                    <div class="border-top pt-3">

                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Subtotal</span>
                            <strong>$<?= number_format($subtotal, 2) ?></strong>
                        </div>

                        <div class="d-flex justify-content-between mt-2">
                            <span class="text-muted">Estimated tax (14.975%)</span>
                            <span>$<?= number_format($tax, 2) ?></span>
                        </div>

                        <div class="d-flex justify-content-between fs-4 fw-bold mt-3">
                            <span>Total</span>
                            <span>$<?= number_format($total, 2) ?></span>
                        </div>

                    </div>

                    <div class="d-flex flex-wrap justify-content-end gap-2 mt-4">

                        <form action="<?= BASE_URL ?>checkout/clear" method="post">

                            <input
                                type="hidden"
                                name="csrf"
                                value="<?= $h($csrf) ?>"
                            >

                            <button
                                class="btn btn-outline-danger"
                                <?= (!$cart && !$removed) ? 'disabled' : '' ?>
                            >
                                Empty cart
                            </button>

                        </form>

                        <a
                            href="<?= BASE_URL ?>checkout/confirm"
                            class="btn btn-success <?= !$cart ? 'disabled' : '' ?>"
                        >
                            Review &amp; payment
                        </a>

                    </div>

                </div>
            </section>

            <?php if ($removed): ?>

                <section class="card store-card mt-4">
                    <div class="card-body p-4">

                        <h3 class="h6">Removed EPCs</h3>

                        <?php foreach ($removed as $item): ?>

                            <form
                                method="post"
                                action="<?= BASE_URL ?>checkout/restore-tag"
                                class="d-flex justify-content-between gap-2 align-items-center mb-2"
                            >

                                <input
                                    type="hidden"
                                    name="csrf"
                                    value="<?= $h($csrf) ?>"
                                >

                                <input
                                    type="hidden"
                                    name="epc"
                                    value="<?= $h($item['epc']) ?>"
                                >

                                <code>
                                    <?= $h($item['epc']) ?>
                                </code>

                                <button class="btn btn-sm btn-outline-primary">
                                    Restore
                                </button>

                            </form>

                        <?php endforeach; ?>

                    </div>
                </section>

            <?php endif; ?>

            </div><!-- /checkout-cart-content -->

        </div>

    </div>

</div>

<script>
// The Chafon reader keeps running; the visible page selects its destination.
const checkoutScannerUrl = <?= json_encode(BASE_URL . 'checkout/scanner/activate', JSON_UNESCAPED_SLASHES) ?>;
const checkoutCartUrl = <?= json_encode(BASE_URL . 'checkout', JSON_UNESCAPED_SLASHES) ?>;
const checkoutScannerToken = <?= json_encode($_SESSION['checkout_scanner_token']) ?>;

let scannerActivating = false;
let cartRefreshing = false;

async function activateCheckoutScanner() {
    // Don't compete with the Product Edit tab when checkout is hidden.
    if (document.hidden || scannerActivating) return;

    scannerActivating = true;
    try {
        const response = await fetch(checkoutScannerUrl, {
            method: 'POST',
            credentials: 'same-origin',
            cache: 'no-store',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({ token: checkoutScannerToken })
        });

        if (!response.ok) {
            console.error('Checkout RFID activation failed: HTTP ' + response.status);
        }
    } catch (error) {
        console.error('Checkout RFID activation failed:', error);
    } finally {
        scannerActivating = false;
    }
}

async function refreshCheckoutCart() {
    if (document.hidden || cartRefreshing) return;

    cartRefreshing = true;
    try {
        // Fetch the latest server-rendered checkout HTML, without navigating.
        const response = await fetch(checkoutCartUrl, {
            credentials: 'same-origin',
            cache: 'no-store'
        });

        if (!response.ok) {
            throw new Error('Checkout refresh failed: HTTP ' + response.status);
        }

        const html = await response.text();
        const updatedDocument = new DOMParser().parseFromString(html, 'text/html');
        const updated = updatedDocument.getElementById('checkout-cart-content');
        const current = document.getElementById('checkout-cart-content');

        if (!updated || !current) {
            throw new Error('Shopping-list section not found.');
        }

        // Avoid flicker and interruption of button clicks if nothing changed.
        if (updated.dataset.cartVersion !== current.dataset.cartVersion) {
            current.replaceWith(document.importNode(updated, true));
        }
    } catch (error) {
        console.error('Could not update shopping list:', error);
    } finally {
        cartRefreshing = false;
    }
}

activateCheckoutScanner();
setInterval(activateCheckoutScanner, 2000);
setInterval(refreshCheckoutCart, 1000);

window.addEventListener('focus', () => {
    activateCheckoutScanner();
    refreshCheckoutCart();
});

document.addEventListener('visibilitychange', () => {
    if (!document.hidden) {
        activateCheckoutScanner();
        refreshCheckoutCart();
    }
});
</script>

<?php require APP_VIEWS_PATH . '/checkout/no_js_footer.php'; ?>