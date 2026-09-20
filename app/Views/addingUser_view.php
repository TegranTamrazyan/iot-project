<?php

use App\Helpers\ViewHelper;

// Load header layout
$page_title = "Add User";
ViewHelper::loadHeader($page_title);

?>

<div class="container" style="max-width:500px; margin:60px auto;">

    <h3 class="text-center mb-4">
        Add a customer!
    </h3>

    <form method="POST" action="/adding">

        <!-- Full Name -->
        <div class="form-floating mb-3">
            <input
                class="form-control rounded-pill"
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
                class="form-control rounded-pill"
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
                class="form-control rounded-pill"
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
                class="form-control rounded-pill"
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
            class="btn btn-primary w-100 rounded-pill py-2"
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