<?php
use App\Helpers\ViewHelper;
use App\Helpers\CheckoutSecurity;
ViewHelper::loadHeader('Customer Login');
$csrf=CheckoutSecurity::csrfToken();
$h=static fn($v)=>htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');
?>
<div class="container py-5" style="max-width:510px"><div class="card store-card"><div class="card-body p-4 p-md-5">
 <h1 class="h2 fw-bold mb-4">Welcome back</h1>
 <?php if($error):?><div class="alert alert-danger"><?= $h($error) ?></div><?php endif;?>
 <form method="post" action="<?= BASE_URL ?>login">
  <input type="hidden" name="csrf" value="<?= $h($csrf) ?>">
  <label class="form-label" for="email">Email</label><input class="form-control mb-3" id="email" type="email" name="email_address" maxlength="100" required autocomplete="email">
  <label class="form-label" for="password">Password</label><input class="form-control mb-4" id="password" type="password" name="password" required autocomplete="current-password">
  <button class="btn btn-primary w-100 py-2">Log in</button>
 </form><p class="text-center text-muted mt-4 mb-0">New customer? <a href="<?= BASE_URL ?>adding">Create an account</a></p>
</div></div></div>
<?php require APP_VIEWS_PATH.'/checkout/no_js_footer.php'; ?>
