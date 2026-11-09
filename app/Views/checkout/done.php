<?php
use App\Helpers\ViewHelper;
ViewHelper::loadHeader('Payment Complete');
?>
<div class="container py-5" style="max-width:630px"><div class="card store-card"><div class="card-body p-5 text-center">
 <div class="display-4 mb-3">✓</div><h1 class="fw-bold">Payment simulated</h1>
 <p class="text-muted">Transaction complete</p>
 <div class="fs-4 fw-bold">$<?= number_format((float)$order['total'],2) ?></div>
 <?php if((int)$order['points_earned']>0):?><p class="mt-2">+<?= (int)$order['points_earned'] ?> loyalty points added</p><?php endif;?>
 <div class="d-flex flex-wrap justify-content-center gap-2 mt-4">
  <a href="<?= BASE_URL ?>checkout?live=0" class="btn btn-primary">New checkout</a>
  <?php if(!empty($_SESSION['customer_id'])):?><a href="<?= BASE_URL ?>account" class="btn btn-outline-secondary">My account</a><?php endif;?>
 </div>
</div></div></div>
<?php require APP_VIEWS_PATH.'/checkout/no_js_footer.php'; ?>
