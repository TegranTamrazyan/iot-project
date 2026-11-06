<?php
use App\Helpers\ViewHelper;
use App\Helpers\CheckoutSecurity;
ViewHelper::loadHeader('My Account');
$h=static fn($v)=>htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');
?>
<div class="container py-5">
 <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-4">
  <div><h1 class="fw-bold mb-1">Hello, <?= $h($customer['customer_name']) ?></h1></div>
  <form action="<?= BASE_URL ?>logout" method="post"><input type="hidden" name="csrf" value="<?= $h(CheckoutSecurity::csrfToken()) ?>"><button class="btn btn-outline-secondary">Log out</button></form>
 </div>
 <div class="row g-4 mb-5">
  <div class="col-lg-7"><div class="membership-card">
   <div class="card-label">IOT Smart Store Membership</div><div class="my-4"><div class="card-label mb-1">Member number</div>
   <div class="member-number"><?= $h($customer['membership_code']) ?></div></div>
   <div class="d-flex justify-content-between gap-3 flex-wrap"><div><div class="card-label">Name</div><strong><?= $h($customer['customer_name']) ?></strong></div>
   <div><div class="card-label">Account</div><strong>Active</strong></div></div>
  </div></div>
  <div class="col-lg-5"><div class="card store-card h-100"><div class="card-body p-4 d-flex flex-column justify-content-center">
   <div class="text-muted fw-semibold mb-2">Total loyalty points</div><div class="metric-number"><?= (int)$customer['total_points'] ?></div>
  </div></div></div>
 </div>
 <section class="card store-card"><div class="card-body p-4">
  <h2 class="h4 fw-bold mb-3">Purchase history</h2>
  <?php if(!$purchases):?><p class="text-muted mb-0">You haven't made a purchase with this membership yet.</p><?php endif;?>
  <?php foreach($purchases as $order):?>
   <div class="py-3 border-top"><div class="d-flex flex-wrap justify-content-between gap-2">
    <div><strong>Purchase #<?= (int)$order['id'] ?></strong><div class="text-muted small"><?= $h($order['created_at']) ?> · Simulated payment</div></div>
    <div class="text-end"><strong>$<?= number_format((float)$order['total'],2) ?></strong><div class="small text-success">+<?= (int)$order['points_earned'] ?> points</div></div>
   </div><div class="mt-2 small">
    <?php foreach($purchaseItems[(int)$order['id']]??[] as $item):?>
     <div class="text-muted"><?= $h($item['product_name']) ?> × <?= (int)$item['quantity'] ?> ($<?= number_format((float)$item['unit_price'],2) ?> each)</div>
    <?php endforeach;?>
   </div></div>
  <?php endforeach;?>
 </div></section>
</div>
<?php require APP_VIEWS_PATH.'/checkout/no_js_footer.php'; ?>
