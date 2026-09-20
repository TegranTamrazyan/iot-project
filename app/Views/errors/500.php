<?php

use App\Helpers\ViewHelper;

$page_title = '500 - Server Error';
ViewHelper::loadHeader($page_title);
?>

<div style="text-align: center; padding: 50px 20px;">
    <h1 style="font-size: 72px; margin: 0; color: #c0392b;">500</h1>
    <h2 style="margin: 20px 0;">Something went wrong</h2>
    <p style="font-size: 18px; color: #666; margin: 20px 0;">
        Sorry, Our bookstore is currently experiencing a technical issue right now.
        Please try again in a few moments.
    </p>
    <a href="<?= base_url('/') ?>" style="display: inline-block; margin-top: 30px; padding: 12px 30px; background-color: #3498db; color: white; text-decoration: none; border-radius: 5px; font-size: 16px;">
        Go Back Home
    </a>
</div>

<?php
ViewHelper::loadJsScripts();
ViewHelper::loadFooter();
?>
