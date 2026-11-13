<?php

use App\Helpers\ViewHelper;
use App\Helpers\Core\FlashMessage;

ViewHelper::loadHeader('Product Management');
$h = static fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$csrf = bin2hex(random_bytes(32));
$_SESSION['product_csrf'] = $csrf;
?>

<div class="container my-4" style="max-width: 1200px;">
    <div class="container mt-4">
    <section class="store-hero p-4 p-md-5 mb-4 d-flex justify-content-between flex-wrap gap-3">
  <div><h1 class="fw-bold display-6 mb-0 text-center">Product Management</h1></div>
 </section>
    <p class="text-muted">Add edit and delete products and view stock receptions</p>

    <?= FlashMessage::render() ?>

    <!-- add product form -->
    <div class="card store-card mb-4">
        <div class="card-body p-4">
            <h5 class="card-title fw-bold">Add new product</h5>
            <form method="post" action="<?= BASE_URL ?>product/create">
                <input type="hidden" name="csrf" value="<?= $h($csrf) ?>">
                <div class="row g-2">
                    <div class="col-md-3">
                        <label class="form-label small">Name</label>
                        <input type="text" class="form-control" name="name" placeholder="Coca-Cola 500ml" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">Category</label>
                        <input type="text" class="form-control" name="category" placeholder="Beverage" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">Price</label>
                        <input type="number" step="0.01" class="form-control" name="price" placeholder="0.00" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">UPC max 13 digits</label>
                        <input type="text" class="form-control" name="upc" maxlength="13" pattern="[0-9]{1,13}" placeholder="5012345678901" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">Producer</label>
                        <input type="text" class="form-control" name="producer_company" placeholder="Coca-Cola Company" required>
                    </div>
                    <div class="col-md-1 d-flex align-items-end">
                        <button class="btn btn-primary w-100">Add product</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- products table -->
    <div class="card store-card mb-4">
        <div class="card-body p-4">
            <h5 class="card-title fw-bold mb-3">Products</h5>
            <form method="post" action="<?= BASE_URL ?>product/delete">
                <input type="hidden" name="csrf" value="<?= $h($csrf) ?>">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Delete selected products?')">Delete selected</button>
                    <a href="<?= BASE_URL ?>product" class="btn btn-outline-secondary btn-sm">Refresh</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-striped align-middle">
                        <thead>
                            <tr>
                                <th><input type="checkbox" id="selectAll"></th>
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
                                <td><input type="checkbox" name="selected[]" value="<?= (int) $p['id'] ?>"></td>
                                <td><?= $h($p['name']) ?></td>
                                <td><?= $h($p['category']) ?></td>
                                <td>$<?= number_format((float) $p['price'], 2) ?></td>
                                <td><?= $h($p['upc']) ?></td>
                                <td><?= $h($p['epcs'] ?? '-') ?></td>
                                <td><?= $h($p['producer_company']) ?></td>
                                <td><?= (int) $p['in_stock'] ?></td>
                                <td><?= $p['active'] ? 'Yes' : 'No' ?></td>
                                <td>
                                    <a href="<?= BASE_URL ?>product/edit/<?= (int) $p['id'] ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                                    <a href="<?= BASE_URL ?>product/delete/<?= (int) $p['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this product?')">Delete</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (!$products): ?>
                            <tr><td colspan="10" class="text-muted text-center py-4">No products found</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </form>
        </div>
    </div>

    <!-- stock receptions table -->
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
                        <tr><td colspan="3" class="text-muted text-center py-4">No stock receptions found</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('selectAll').addEventListener('change', function () {
    var boxes = document.querySelectorAll('input[name="selected[]"]');
    for (var i = 0; i < boxes.length; i++) {
        boxes[i].checked = this.checked;
    }
});
</script>

<?php
ViewHelper::loadJsScripts();
ViewHelper::loadFooter();