
<?php

use App\Helpers\ViewHelper;
use App\Helpers\Core\FlashMessage;

ViewHelper::loadHeader('Product Management');

$h = static fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');

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

?>

<div class="container my-4" style="max-width: 1200px;">

    <section class="store-hero p-4 p-md-5 mb-4">
        <h1 class="fw-bold display-6 mb-0">Product Management</h1>
    </section>

    <p class="text-muted">
        Add, edit and delete products and view stock receptions.
    </p>

    <?= FlashMessage::render() ?>

    <!-- Add Product -->
    <div class="card store-card mb-4">
        <div class="card-body p-4">

            <h5 class="card-title fw-bold mb-3">Add New Product</h5>

            <form method="post" action="<?= BASE_URL ?>product/create">

                <input type="hidden" name="csrf" value="<?= $h($csrf) ?>">

                <div class="row g-3">

                    <div class="col-md-3">
                        <label class="form-label small">Name</label>
                        <input
                            type="text"
                            class="form-control"
                            name="name"
                            placeholder="Coca-Cola 500ml"
                            required
                        >
                    </div>

                    <div class="col-md-2">
                        <label class="form-label small">Category</label>
                        <select class="form-select" name="category" required>
                            <option value="" selected disabled>Select category</option>

                            <?php foreach ($categories as $category): ?>
                                <option value="<?= $h($category) ?>">
                                    <?= $h($category) ?>
                                </option>
                            <?php endforeach; ?>

                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label small">Price</label>
                        <input
                            type="number"
                            step="0.01"
                            min="0"
                            class="form-control"
                            name="price"
                            placeholder="0.00"
                            required
                        >
                    </div>

                    <div class="col-md-2">
                        <label class="form-label small">UPC (max 13 digits)</label>
                        <input
                            type="text"
                            class="form-control"
                            name="upc"
                            maxlength="13"
                            pattern="[0-9]{1,13}"
                            placeholder="5012345678901"
                            required
                        >
                    </div>

                    <div class="col-md-3">
                        <label class="form-label small">Producer</label>
                        <input
                            type="text"
                            class="form-control"
                            name="producer_company"
                            placeholder="Coca-Cola Company"
                            required
                        >
                    </div>

                    <div class="col-md-3">
                        <label class="form-label small">Initial Stock</label>
                        <input
                            type="number"
                            class="form-control"
                            name="stock_quantity"
                            min="0"
                            value="0"
                        >
                    </div>

                    <div class="col-md-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary">
                            Add Product
                        </button>
                    </div>

                </div>

            </form>

        </div>
    </div>

    <!-- Products Table -->
    <div class="card store-card mb-4">
        <div class="card-body p-4">

            <h5 class="card-title fw-bold mb-3">Products</h5>

            <form method="post" action="<?= BASE_URL ?>product/delete">

                <input type="hidden" name="csrf" value="<?= $h($csrf) ?>">

                <div class="d-flex justify-content-between align-items-center mb-3">

                    <button
                        type="submit"
                        class="btn btn-danger btn-sm"
                        onclick="return confirm('Delete selected products?')"
                    >
                        Delete Selected
                    </button>

                    <a href="<?= BASE_URL ?>product"
                       class="btn btn-outline-secondary btn-sm">
                        Refresh
                    </a>

                </div>

                <div class="table-responsive">

                    <table class="table table-striped align-middle">

                        <thead>
                            <tr>
                                <th>
                                    <input type="checkbox" id="selectAll">
                                </th>
                                <th>Name</th>
                                <th>Category</th>
                                <th>Price</th>
                                <th>UPC</th>
                                <th>EPC</th>
                                <th>Producer</th>
                                <th>In Stock</th>
                                <th>Active</th>
                                <th>Actions</th>
                            </tr>
                        </thead>

                        <tbody>

                            <?php foreach ($products as $p): ?>

                                <tr>

                                    <td>
                                        <input
                                            type="checkbox"
                                            name="selected[]"
                                            value="<?= (int) $p['id'] ?>"
                                        >
                                    </td>

                                    <td><?= $h($p['name']) ?></td>

                                    <td><?= $h($p['category']) ?></td>

                                    <td>
                                        $<?= number_format((float) $p['price'], 2) ?>
                                    </td>

                                    <td><?= $h($p['upc']) ?></td>

                                    <td>
                                        <div style="max-width: 220px; overflow-wrap: anywhere;">
                                            <?= $h($p['epcs'] ?? '-') ?>
                                        </div>
                                    </td>

                                    <td><?= $h($p['producer_company']) ?></td>

                                    <td><?= (int) $p['in_stock'] ?></td>

                                    <td><?= !empty($p['active']) ? 'Yes' : 'No' ?></td>

                                    <td>
                                        <div class="d-flex flex-wrap gap-1">

                                            <a
                                                href="<?= BASE_URL ?>product/edit/<?= (int) $p['id'] ?>"
                                                class="btn btn-sm btn-outline-primary"
                                            >
                                                Edit
                                            </a>

                                            <button
                                                type="submit"
                                                class="btn btn-sm btn-outline-danger"
                                                formaction="<?= BASE_URL ?>product/delete/<?= (int) $p['id'] ?>"
                                                formmethod="post"
                                                onclick="return confirm('Delete this product?')"
                                            >
                                                Delete
                                            </button>

                                        </div>
                                    </td>

                                </tr>

                            <?php endforeach; ?>

                            <?php if (!$products): ?>

                                <tr>
                                    <td colspan="10" class="text-muted text-center py-4">
                                        No products found
                                    </td>
                                </tr>

                            <?php endif; ?>

                        </tbody>

                    </table>

                </div>

            </form>

        </div>
    </div>

    <!-- Stock Receptions -->
    <div class="card store-card">
        <div class="card-body p-4">

            <h5 class="card-title fw-bold mb-3">Stock Receptions</h5>

            <div class="table-responsive">

                <table class="table table-striped align-middle">

                    <thead>
                        <tr>
                            <th>Date Received</th>
                            <th>Product</th>
                            <th>Quantity Received</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php foreach ($receptions as $r): ?>

                            <tr>
                                <td><?= $h($r['received_at']) ?></td>
                                <td><?= $h($r['name']) ?></td>
                                <td><?= (int) $r['quantity_received'] ?></td>
                            </tr>

                        <?php endforeach; ?>

                        <?php if (!$receptions): ?>

                            <tr>
                                <td colspan="3" class="text-muted text-center py-4">
                                    No stock receptions found
                                </td>
                            </tr>

                        <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </div>
    </div>

</div>

<script>
document.getElementById('selectAll').addEventListener('change', function () {

    const boxes = document.querySelectorAll('input[name="selected[]"]');

    boxes.forEach(box => {
        box.checked = this.checked;
    });

});
</script>

<?php

ViewHelper::loadJsScripts();
ViewHelper::loadFooter();
