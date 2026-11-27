
<?php
use App\Helpers\CheckoutSecurity;
use App\Helpers\Core\FlashMessage;
use App\Helpers\ViewHelper;

ViewHelper::loadHeader('Payment Complete');
?>

<div class="container py-5" style="max-width:630px">
    <?= FlashMessage::render() ?>
    <div class="card store-card">
        <div class="card-body p-5">
            <div class="text-center">
                <div class="display-4 mb-3 text-success">✓</div>
                <h1 class="fw-bold">Payment Complete!</h1>
                <p class="text-muted">Transaction successful</p>
            </div>
            <hr>
            <h5 class="fw-bold">Receipt #<?= (int)$order['id'] ?></h5>
            <p class="text-muted mb-4">
                Date and Time:
                <?= htmlspecialchars(
                    date('Y-m-d H:i:s', strtotime($order['created_at']))
                ) ?>
            </p>
            <h5 class="fw-bold">Purchased Items</h5>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th class="text-center">Qty</th>
                            <th class="text-end">Price</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($items as $item): ?>
                            <tr>
                                <td>
                                    <?= htmlspecialchars($item['product_name']) ?>
                                </td>
                                <td class="text-center">
                                    <?= (int)$item['quantity'] ?>
                                </td>
                                <td class="text-end">
                                    $<?= number_format(
                                        (float)$item['subtotal'], 2
                                    ) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <hr>
            <div class="d-flex justify-content-between mb-2">
                <span class="fw-bold">Total Amount</span>

                <span class="fs-4 fw-bold">
                    $<?= number_format((float)$order['total'], 2) ?>
                </span>
            </div>
            <div class="d-flex justify-content-between">
                <span>Loyalty Points Earned</span>
                <span class="fw-bold">
                    <?= (int)$order['points_earned'] ?> points
                </span>
            </div>
            <hr>
            <div class="d-flex flex-wrap justify-content-center gap-2 mt-4">
                <a href="<?= BASE_URL ?>checkout?live=0"
                   class="btn btn-primary">
                    New checkout
                </a>
                <?php if (!empty($_SESSION['customer_id'])): ?>
                    <a href="<?= BASE_URL ?>account"
                       class="btn btn-outline-secondary">
                        My account
                    </a>
                <?php endif; ?>
            </div>

            <h5 class="fw-bold mt-4">Email a Copy of Your Receipt</h5>

        <form method="POST"
            action="<?= BASE_URL ?>checkout/email-receipt">

            <input type="hidden"
                name="csrf"
                value="<?= htmlspecialchars(CheckoutSecurity::csrfToken(), ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden"
                name="order_id"
                value="<?= (int)$order['id'] ?>">

            <div class="mb-3">
                <label for="receipt_email" class="form-label">
                    Email Address
                </label>

                <input type="email"
                    class="form-control"
                    id="receipt_email"
                    name="receipt_email"
                    placeholder="example@email.com"
                    value="<?= htmlspecialchars($customer['email_address'] ?? '') ?>"
                    required>
            </div>

            <button type="submit" class="btn btn-outline-primary">
                Email Receipt
            </button>
        </form>
        </div>
    </div>
</div>

<?php require APP_VIEWS_PATH.'/checkout/no_js_footer.php'; ?>
