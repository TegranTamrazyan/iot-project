
<?php

use App\Helpers\ViewHelper;
use App\Helpers\Core\FlashMessage;

ViewHelper::loadHeader('Edit Product');

$h = static fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');

$p = $product ?? [];
$productId = (int) ($p['id'] ?? 0);

$csrf = bin2hex(random_bytes(32));
$_SESSION['product_csrf'] = $csrf;

$categories = [
    'Produce',
    'Beverages',
    'Dairy',
    'Meat & Seafood',
    'Bakery',
    'Frozen Foods',
    'Snacks',
    'Household',
    'Electronics',
    'Other'
];

$currentCategory = (string) ($p['category'] ?? '');

// Preserve categories previously entered manually.
if ($currentCategory !== '' && !in_array($currentCategory, $categories, true)) {
    array_unshift($categories, $currentCategory);
}

?>

<div class="container my-4" style="max-width: 750px;">

    <h2 class="mb-2">Edit Product</h2>

    <p class="text-muted">
        Change product details and manage RFID tags.
    </p>

    <?= FlashMessage::render() ?>

    <div class="card store-card">
        <div class="card-body p-4">

            <form method="post" action="<?= BASE_URL ?>product/edit/<?= $productId ?>">

                <input type="hidden" name="csrf" value="<?= $h($csrf) ?>">

                <!-- Product Information -->
                <div class="row g-3">

                    <div class="col-md-6">
                        <label class="form-label">Name</label>
                        <input
                            type="text"
                            class="form-control"
                            name="name"
                            value="<?= $h($p['name'] ?? '') ?>"
                            required
                        >
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Category</label>

                        <select class="form-select" name="category" required>

                            <?php if ($currentCategory === ''): ?>
                                <option value="" selected disabled>
                                    Select category
                                </option>
                            <?php endif; ?>

                            <?php foreach ($categories as $category): ?>

                                <option
                                    value="<?= $h($category) ?>"
                                    <?= $currentCategory === $category ? 'selected' : '' ?>
                                >
                                    <?= $h($category) ?>
                                </option>

                            <?php endforeach; ?>

                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Price</label>
                        <input
                            type="number"
                            step="0.01"
                            min="0"
                            class="form-control"
                            name="price"
                            value="<?= $h($p['price'] ?? '') ?>"
                            required
                        >
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">UPC</label>
                        <input
                            type="text"
                            class="form-control"
                            name="upc"
                            maxlength="13"
                            pattern="[0-9]{1,13}"
                            value="<?= $h($p['upc'] ?? '') ?>"
                            required
                        >
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Producer</label>
                        <input
                            type="text"
                            class="form-control"
                            name="producer_company"
                            value="<?= $h($p['producer_company'] ?? '') ?>"
                            required
                        >
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Add Stock Quantity</label>
                        <input
                            type="number"
                            class="form-control"
                            name="stock_quantity"
                            min="0"
                            value="0"
                        >
                    </div>

                    <div class="col-md-4 d-flex align-items-end">
                        <div class="form-check mb-2">

                            <input
                                class="form-check-input"
                                type="checkbox"
                                name="active"
                                value="1"
                                <?= !empty($p['active']) ? 'checked' : '' ?>
                            >

                            <label class="form-check-label">
                                Active
                            </label>

                        </div>
                    </div>

                </div>

                <!-- RFID Registration -->
                <hr class="my-4">

                <div class="d-flex justify-content-between align-items-center mb-2">

                    <h5 class="fw-bold mb-0">RFID Tags</h5>

                    <span class="badge bg-secondary">
                        <span id="rfid-count">
                            <?= (int) ($epcCount ?? 0) ?>
                        </span>
                        Registered
                    </span>

                </div>

                <p class="text-muted small mb-3">
                    Scan RFID tags to automatically register them to this product.
                </p>

                <div class="mb-3">

                    <label class="form-label">Registered EPCs</label>

                    <textarea
                        id="registered-epcs"
                        class="form-control font-monospace"
                        rows="5"
                        readonly
                        placeholder="No RFID tags registered yet"
                    ></textarea>

                </div>

                <!-- Scanner Status and Controls -->
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">

                    <small id="rfid-status" class="text-muted">
                        Connecting to scanner...
                    </small>

                    <div class="d-flex gap-2">

                        <button
                            type="button"
                            id="clear-epcs"
                            class="btn btn-outline-danger btn-sm"
                        >
                            Clear EPCs
                        </button>

                        <button
                            type="button"
                            id="resume-scanner"
                            class="btn btn-outline-primary btn-sm"
                        >
                            Resume Scanning
                        </button>

                    </div>

                </div>

                <!-- Optional Manual EPC Registration -->
                <details class="mb-4">

                    <summary class="text-muted small">
                        Add EPCs manually
                    </summary>

                    <div class="mt-2">

                        <input
                            type="text"
                            class="form-control"
                            name="epcs"
                            placeholder="000001, 000002"
                        >

                        <small class="text-muted">
                            Separate EPCs with commas. Maximum 24 hexadecimal characters each.
                        </small>

                    </div>

                </details>

                <!-- Form Buttons -->
                <div class="d-flex gap-2">

                    <button type="submit" class="btn btn-primary">
                        Update Product
                    </button>

                    <a href="<?= BASE_URL ?>product"
                       class="btn btn-outline-secondary">
                        Cancel
                    </a>

                </div>

            </form>

        </div>
    </div>

</div>

<script>

// API URLs
const scannerUrl = <?= json_encode(BASE_URL . 'product/edit/' . $productId . '/scanner/activate') ?>;
const countUrl = <?= json_encode(BASE_URL . 'product/edit/' . $productId . '/scanner/count') ?>;
const clearUrl = <?= json_encode(BASE_URL . 'product/edit/' . $productId . '/epcs/clear') ?>;

const csrf = <?= json_encode($csrf) ?>;

let scannerPaused = false;


// Activate RFID scanning for this product.
async function activateProductScanner() {

    if (scannerPaused || document.hidden || !document.hasFocus()) {
        return;
    }

    try {

        const response = await fetch(scannerUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: new URLSearchParams({ csrf })
        });

        document.getElementById('rfid-status').textContent =
            response.ok
                ? 'Product registration mode active'
                : 'Scanner activation failed: HTTP ' + response.status;

    } catch (error) {

        console.error('Scanner activation error:', error);

        document.getElementById('rfid-status').textContent =
            'Scanner connection failed';

    }

}


// Refresh the registered RFID tag list.
async function refreshRfidCount() {

    try {

        const response = await fetch(countUrl, {
            cache: 'no-store'
        });

        if (!response.ok) {
            throw new Error('HTTP ' + response.status);
        }

        const data = await response.json();

        document.getElementById('rfid-count').textContent =
            data.count;

        if (Array.isArray(data.epcs)) {

            document.getElementById('registered-epcs').value =
                data.epcs.join('\n');

        }

    } catch (error) {

        console.error('Could not refresh RFID tags:', error);

    }

}


// Clear all EPCs for the current product.
document.getElementById('clear-epcs').addEventListener('click', async function () {

    if (!confirm('Delete ALL registered EPCs for this product?')) {
        return;
    }

    scannerPaused = true;
    this.disabled = true;

    try {

        const response = await fetch(clearUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: new URLSearchParams({ csrf })
        });

        if (!response.ok) {
            throw new Error('HTTP ' + response.status);
        }

        await refreshRfidCount();

        document.getElementById('rfid-status').textContent =
            'EPCs cleared. Scanning paused.';

    } catch (error) {

        console.error('Could not clear EPCs:', error);

        alert('Could not clear EPCs.');

        scannerPaused = false;
        await activateProductScanner();

    } finally {

        this.disabled = false;

    }

});


// Resume RFID scanning.
document.getElementById('resume-scanner').addEventListener('click', async function () {

    scannerPaused = false;

    await activateProductScanner();

});


// Activate scanner and load EPCs when the page opens.
activateProductScanner();
refreshRfidCount();


// Switch back to this product when the page is focused.
window.addEventListener('focus', activateProductScanner);

document.addEventListener(
    'visibilitychange',
    activateProductScanner
);


// Automatically refresh scanner status and EPC list.
setInterval(activateProductScanner, 2000);

setInterval(refreshRfidCount, 1500);

</script>

<?php

ViewHelper::loadJsScripts();
ViewHelper::loadFooter();
