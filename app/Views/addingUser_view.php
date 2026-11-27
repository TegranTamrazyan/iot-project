
<?php

use App\Helpers\ViewHelper;
use App\Helpers\CheckoutSecurity;

ViewHelper::loadHeader('Create Account');

$csrf = CheckoutSecurity::csrfToken();
$h = static fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');

?>

<div class="container py-5" style="max-width:670px">
    <div class="card store-card">
        <div class="card-body p-4 p-md-5">

            <h1 class="h2 fw-bold mb-2">Create your account</h1>

            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <?= $h($error) ?>
                </div>
            <?php endif; ?>

            <form method="post" action="<?= BASE_URL ?>adding">

                <input
                    type="hidden"
                    name="csrf"
                    value="<?= $h($csrf) ?>"
                >

                <label for="customer_name" class="form-label">
                    Full name
                </label>

                <input
                    id="customer_name"
                    name="customer_name"
                    maxlength="100"
                    class="form-control mb-3"
                    required
                >

                <label for="email_address" class="form-label">
                    Email
                </label>

                <input
                    id="email_address"
                    name="email_address"
                    maxlength="100"
                    type="email"
                    class="form-control mb-3"
                    required
                >

                <div class="row g-3 mb-3">

                    <div class="col-sm-6">
                        <label for="password" class="form-label">
                            Password (8+ characters)
                        </label>

                        <input
                            id="password"
                            name="password"
                            type="password"
                            minlength="8"
                            class="form-control"
                            autocomplete="new-password"
                            required
                        >
                    </div>

                    <div class="col-sm-6">
                        <label for="password_confirm" class="form-label">
                            Confirm password
                        </label>

                        <input
                            id="password_confirm"
                            name="password_confirm"
                            type="password"
                            minlength="8"
                            class="form-control"
                            autocomplete="new-password"
                            required
                        >
                    </div>

                </div>

                <div class="row g-3 mb-3">

                    <div class="col-sm-6">
                        <label for="telephone_number" class="form-label">
                            Phone (10 digits)
                        </label>

                        <input
                            id="telephone_number"
                            name="telephone_number"
                            maxlength="10"
                            pattern="[0-9]{10}"
                            inputmode="numeric"
                            class="form-control"
                            required
                        >
                    </div>

                    <div class="col-sm-6">
                        <label for="address" class="form-label">
                            Address
                        </label>

                        <input
                            id="address"
                            name="address"
                            maxlength="100"
                            class="form-control"
                            required
                        >
                    </div>

                </div>

                <button class="btn btn-primary w-100 py-2 mt-2">
                    Create account &amp; membership card
                </button>

            </form>

            <p class="text-muted text-center mt-4 mb-0">
                Already registered?
                <a href="<?= BASE_URL ?>login">Log in</a>
            </p>

        </div>
    </div>
</div>

<?php require APP_VIEWS_PATH . '/checkout/no_js_footer.php'; ?>
