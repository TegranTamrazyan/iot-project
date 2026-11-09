<?php
use App\Helpers\ViewHelper;
use App\Helpers\CheckoutSecurity;
ViewHelper::loadHeader('Create Account');
$csrf=CheckoutSecurity::csrfToken();
$h=static fn($v)=>htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');
?>

    <div style="position: fixed; bottom: 10%; left: 50%; transform: translateX(-50%); z-index: 9999;">
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <strong>FAILURE!</strong> <?= $_SESSION['error'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    </div>
    
    <?php unset($_SESSION['error']) ?>
    <?php shell_exec('python3 ' . __DIR__ . '/../../scripts/error_led.py'); ?>
    
<?php
}
if (isset($_SESSION['success'])) {
?>
    <div style="position: fixed; bottom: 10%; left: 50%; transform: translateX(-50%); z-index: 9999;">
        <div class="alert alert-success warning alert-dismissible fade show" role="alert">
            <strong>SUCCESS!</strong> <?= $_SESSION['success'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    </div>

    <?php unset($_SESSION['success']) ?>
    <?php shell_exec('python3 ' . __DIR__ . '/../../scripts/success_led.py'); ?>

<?php
}
?>


<div class="container" style="max-width:500px; margin:60px auto;">

    <h3 class="text-center mb-4">
        Add a customer!
    </h3>

    <form method="POST" action="/iot-project/adding">

        <!-- Full Name -->
        <div class="form-floating mb-3">
            <input
                class="form-control rounded"
                placeholder="Name"
                type="text"
                name="customer_name"
                required
            >

            <label>Name</label>
        </div>


        <!-- Email -->
        <div class="form-floating mb-3">
            <input
                class="form-control rounded"
                placeholder="Email"
                type="email"
                name="email_address"
                required
            >

            <label>Email</label>
        </div>


        <!-- Phone Number -->
        <div class="form-floating mb-3">
            <input
                class="form-control rounded"
                placeholder="Phone Number"
                type="tel"
                name="telephone_number"
                required
            >

            <label>Phone Number</label>
        </div>


        <!-- Home Address -->
        <div class="form-floating mb-3">
            <input
                class="form-control rounded"
                placeholder="Address"
                type="text"
                name="address"
                required
            >

            <label>Address</label>
        </div>


        <!-- Submit Button -->
        <button
            type="submit"
            class="btn btn-primary w-100 rounded py-2"
            style="
                padding:10px 20px;
                background:#1b8eda;
                color:white;
                border:none;
                border-radius:5px;
            "
        >
            Insert
        </button>

    </form>

</div>

<?php
    ViewHelper::loadJsScripts();
    ViewHelper::loadFooter();
?>
<div class="container py-5" style="max-width:670px"><div class="card store-card"><div class="card-body p-4 p-md-5">
 <h1 class="h2 fw-bold mb-2">Create your account</h1>
 <?php if($error):?><div class="alert alert-danger"><?= $h($error) ?></div><?php endif;?>
 <form method="post" action="<?= BASE_URL ?>adding">
  <input type="hidden" name="csrf" value="<?= $h($csrf) ?>">
  <label for="customer_name" class="form-label">Full name</label><input id="customer_name" name="customer_name" maxlength="100" class="form-control mb-3" required>
  <label for="email_address" class="form-label">Email</label><input id="email_address" name="email_address" maxlength="100" type="email" class="form-control mb-3" required>
  <div class="row g-3 mb-3"><div class="col-sm-6"><label for="password" class="form-label">Password (8+ characters)</label><input id="password" name="password" type="password" minlength="8" class="form-control" autocomplete="new-password" required></div>
   <div class="col-sm-6"><label for="password_confirm" class="form-label">Confirm password</label><input id="password_confirm" name="password_confirm" type="password" minlength="8" class="form-control" autocomplete="new-password" required></div></div>
  <div class="row g-3 mb-3"><div class="col-sm-6"><label for="telephone_number" class="form-label">Phone (10 digits)</label><input id="telephone_number" name="telephone_number" maxlength="10" pattern="[0-9]{10}" inputmode="numeric" class="form-control" required></div>
   <div class="col-sm-6"><label for="address" class="form-label">Address</label><input id="address" name="address" maxlength="100" class="form-control" required></div></div>
  <button class="btn btn-primary w-100 py-2 mt-2">Create account &amp; membership card</button>
 </form>
 <p class="text-muted text-center mt-4 mb-0">Already registered? <a href="<?= BASE_URL ?>login">Log in</a></p>
</div></div></div>
<?php require APP_VIEWS_PATH.'/checkout/no_js_footer.php'; ?>
