
<?php

use App\Helpers\ViewHelper;
use App\Helpers\CheckoutSecurity;

ViewHelper::loadHeader('Confirm Payment');

$csrf = CheckoutSecurity::csrfToken();
$h = static fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');

?>

<div class="container py-5" style="max-width:900px">

    <a href="<?= BASE_URL ?>checkout?live=0" class="text-decoration-none">
        ← Back to cart
    </a>

    <h1 class="fw-bold mt-3 mb-1">Review your checkout</h1>

    <?php if ($error): ?>
        <div class="alert alert-danger">
            <?= $h($error) ?>
        </div>
    <?php endif; ?>

    <div class="row g-4">

        <div class="col-lg-7">
            <section class="card store-card">
                <div class="card-body p-4">

                    <h2 class="h5 mb-3">Your shopping list</h2>

                    <?php foreach ($groups as $g): ?>
                        <div class="d-flex justify-content-between gap-2 border-bottom py-2">
                            <span>
                                <?= $h($g['name']) ?>
                                <span class="text-muted">
                                    × <?= (int)$g['quantity'] ?>
                                </span>
                            </span>

                            <strong>
                                $<?= number_format($g['line_total'], 2) ?>
                            </strong>
                        </div>
                    <?php endforeach; ?>

                    <div class="d-flex justify-content-between mt-3">
                        <span>Subtotal</span>
                        <span>$<?= number_format($subtotal, 2) ?></span>
                    </div>

                    <div class="d-flex justify-content-between mt-2">
                        <span>Estimated tax</span>
                        <span>$<?= number_format($tax, 2) ?></span>
                    </div>

                    <div class="d-flex justify-content-between mt-3 pt-3 border-top fs-4 fw-bold">
                        <span>Pay</span>
                        <span>$<?= number_format($total, 2) ?></span>
                    </div>

                </div>
            </section>
        </div>

        <div class="col-lg-5">
            <section class="card store-card">
                <div class="card-body p-4">

                    <h2 class="h5 mb-3">Membership card</h2>

                    <form action="<?= BASE_URL ?>checkout/pay" method="post">

                        <input
                            type="hidden"
                            name="csrf"
                            value="<?= $h($csrf) ?>"
                        >

                        <?php if ($customer): ?>
                            <div class="form-check mb-3">
                                <input
                                    class="form-check-input"
                                    type="radio"
                                    name="membership_choice"
                                    value="account"
                                    id="member_account"
                                    checked
                                >

                                <label class="form-check-label" for="member_account">
                                    My account
                                    <strong>
                                        #<?= $h($customer['membership_code']) ?>
                                    </strong>
                                </label>
                            </div>
                        <?php endif; ?>

                        <div class="form-check mb-2">
                            <input
                                class="form-check-input"
                                type="radio"
                                name="membership_choice"
                                value="card"
                                id="member_card"
                                <?= $customer ? '' : 'checked' ?>
                            >

                            <label class="form-check-label" for="member_card">
                                Use a membership card number
                            </label>
                        </div>

                        <label class="form-label small" for="membership_code">
                            6-digit membership number (if using card)
                        </label>

                        <input
                            class="form-control mb-3"
                            id="membership_code"
                            name="membership_code"
                            maxlength="6"
                            pattern="[0-9]{6}"
                            inputmode="numeric"
                            placeholder="123456"
                        >

                        <div class="form-check mb-4">
                            <input
                                class="form-check-input"
                                type="radio"
                                name="membership_choice"
                                value="guest"
                                id="member_guest"
                            >

                            <label class="form-check-label" for="member_guest">
                                Continue as guest (no points)
                            </label>
                        </div>

                        <button class="btn btn-success w-100 py-3 fw-bold">
                            Simulate payment
                        </button>

                    </form>

                </div>
            </section>
        </div>

    </div>
</div>

<?php require APP_VIEWS_PATH . '/checkout/no_js_footer.php'; ?>

