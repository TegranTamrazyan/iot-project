<?php
use App\Helpers\ViewHelper;
use App\Helpers\CheckoutSecurity;
use App\Helpers\Core\FlashMessage;
ViewHelper::loadHeader('My Account');
$h=static fn($v)=>htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');
?>
<div class="container py-5">
 <?= FlashMessage::render() ?>
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
   <div class="py-3 border-top">
        <div class="d-flex flex-wrap justify-content-between gap-2">
        <div><strong>Purchase #<?= (int)$order['id'] ?></strong><div class="text-muted small"><?= $h($order['created_at']) ?> · Simulated payment</div></div>
        <div class="text-end"><strong>$<?= number_format((float)$order['total'],2) ?></strong><div class="small text-success">+<?= (int)$order['points_earned'] ?> points</div></div>
    </div><div class="mt-2 small">
        <?php foreach($purchaseItems[(int)$order['id']]??[] as $item):?>
        <div class="text-muted"><?= $h($item['product_name']) ?> × <?= (int)$item['quantity'] ?> ($<?= number_format((float)$item['unit_price'],2) ?> each)</div>
        <?php endforeach;?>
        <form action="<?= BASE_URL ?>account/resend-receipt" method="post" class="mt-3">
            <input type="hidden" name="csrf" value="<?= $h(CheckoutSecurity::csrfToken()) ?>">

            <input type="hidden"
                name="order_id"
                value="<?= (int)$order['id'] ?>">
            <div class="row g-2 align-items-end">
                <div class="col-md-7">
                    <label class="form-label small"
                        for="receipt_email_<?= (int)$order['id'] ?>">
                        Email address
                    </label>
                    <input type="email"
                        class="form-control"
                        id="receipt_email_<?= (int)$order['id'] ?>"
                        name="receipt_email"
                        value="<?= $h($customer['email_address']) ?>"
                        required>
                </div>
                <div class="col-md-5">
                    <button type="submit"
                            class="btn btn-outline-primary w-100">
                        Resend Receipt
                    </button>
                </div>
            </div>
        </form>        
    </div>
    </div>
  <?php endforeach;?>
 </div></section>
</div>
<?php require APP_VIEWS_PATH.'/checkout/no_js_footer.php'; ?>
