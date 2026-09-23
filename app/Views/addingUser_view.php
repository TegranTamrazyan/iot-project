<?php

use App\Helpers\ViewHelper;

ViewHelper::loadHeader('User Insertion');
ViewHelper::loadJsScripts();

if (isset($_SESSION['error'])) {
?>

    <div style="position: fixed; bottom: 10%; left: 50%; transform: translateX(-50%); z-index: 9999;">
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <strong>FAILURE!</strong> <?= $_SESSION['error'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    </div>
    
    <?php unset($_SESSION['error']) ?>
    <?php shell_exec('python3 ' . __DIR__ . '/../../scripting/error_led.py'); ?>
    
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
    <?php shell_exec('python3 ' . __DIR__ . '/../../scripting/success_led.py'); ?>

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