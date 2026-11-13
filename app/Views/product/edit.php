<?php

use App\Helpers\ViewHelper;
use App\Helpers\Core\FlashMessage;

ViewHelper::loadHeader('Edit Product');
$h = static fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$p = $product ?? [];
$csrf = bin2hex(random_bytes(32));
$_SESSION['product_csrf'] = $csrf;
?>

<div class="container my-4" style="max-width: 700px;">
    <h2 class="mb-2">Edit Product</h2>
    <p class="text-muted">Change product details and save</p>

    <?= FlashMessage::render() ?>

    <div class="card store-card">
        <div class="card-body p-4">
            <form method="post" action="<?= BASE_URL ?>product/edit/<?= (int) ($p['id'] ?? 0) ?>">
                <input type="hidden" name="csrf" value="<?= $h($csrf) ?>">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Name</label>
                        <input type="text" class="form-control" name="name" value="<?= $h($p['name'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Category</label>
                        <input type="text" class="form-control" name="category" value="<?= $h($p['category'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Price</label>
                        <input type="number" step="0.01" class="form-control" name="price" value="<?= $h($p['price'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">UPC</label>
                        <input type="text" class="form-control" name="upc" maxlength="13" pattern="[0-9]{1,13}" value="<?= $h($p['upc'] ?? '') ?>" required>
                    </div>
                </div>
                <div class="row g-3 mt-3">
                    <div class="col-md-4">
                        <label class="form-label">Producer</label>
                        <input type="text" class="form-control" name="producer_company" value="<?= $h($p['producer_company'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="active" value="1" <?= (!empty($p['active']) ? 'checked' : '') ?>>
                            <label class="form-check-label">Active</label>
                        </div>
                    </div>
                </div>
                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">Update product</button>
                    <a href="<?= BASE_URL ?>product" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
ViewHelper::loadJsScripts();
ViewHelper::loadFooter();