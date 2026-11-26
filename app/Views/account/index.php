<?php

use App\Helpers\ViewHelper;
use App\Helpers\CheckoutSecurity;
use App\Helpers\Core\FlashMessage;

ViewHelper::loadHeader('My Account');
$h = static fn($value) => htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
$customer = $customer ?? [];
$purchases = $purchases ?? [];
$purchaseItems = $purchaseItems ?? [];
$totalSpent = $totalSpent ?? 0;
$productSearch = $productSearch ?? '';
$productPurchases = $productPurchases ?? [];
$purchasedProductNames = $purchasedProductNames ?? [];
$reportError = $reportError ?? '';
$fromDate = $fromDate ?? '';
$toDate = $toDate ?? '';
$purchaseCount = count($purchases);
$chartPoints = [];
$uniqueOrders = [];
if ($productPurchases) {
    $prices = array_map(static fn(array $purchase): float => (float)$purchase['unit_price'], $productPurchases);
    $minimum = min($prices);
    $maximum = max($prices);
    $spread = max($maximum - $minimum, 1.0);
    $lastIndex = count($prices) - 1;
    foreach ($prices as $index => $price) {
        $x = $lastIndex > 0 ? 35 + (530 * $index / $lastIndex) : 300;
        $y = 145 - (120 * ($price - $minimum) / $spread);
        $chartPoints[] = ['x' => $x, 'y' => $y, 'price' => $price, 'purchase' => $productPurchases[$index]];
    }
    $uniqueOrders = array_unique(array_column($productPurchases, 'order_id'));
}
?>
<div class="container py-5">
    <?= FlashMessage::render() ?>
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-4">
        <div>
            <div class="text-uppercase small text-primary fw-bold">Customer portal</div>
            <h1 class="fw-bold mb-1">Hello, <?= $h($customer['customer_name']) ?></h1>
            <p class="text-muted mb-0">Your membership and purchase reports in one place.</p>
        </div>
        <form action="<?= BASE_URL ?>logout" method="post">
            <input type="hidden" name="csrf" value="<?= $h(CheckoutSecurity::csrfToken()) ?>">
            <button class="btn btn-outline-secondary">Log out</button>
        </form>
    </div>

    <div class="row g-4 mb-5">
        <div class="col-lg-7">
            <div class="membership-card h-100">
                <div class="card-label">IOT Smart Store Membership</div>
                <div class="my-4">
                    <div class="card-label mb-1">Member number</div>
                    <div class="member-number"><?= $h($customer['membership_code']) ?></div>
                </div>
                <div class="d-flex justify-content-between gap-3 flex-wrap">
                    <div>
                        <div class="card-label">Name</div><strong><?= $h($customer['customer_name']) ?></strong>
                    </div>
                    <div>
                        <div class="card-label">Account</div><strong>Active</strong>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card store-card h-100">
                <div class="card-body p-4 d-flex flex-column justify-content-center">
                    <div class="text-muted fw-semibold mb-2">Total loyalty points</div>
                    <div class="metric-number"><?= (int)$customer['total_points'] ?></div>
                </div>
            </div>
        </div>
    </div>

    <section class="card store-card mb-4">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
                <div>
                    <h2 class="h4 fw-bold mb-1">Purchase report</h2>
                    <p class="text-muted mb-0">Filter receipts and spending by purchase date, then search items in that period.</p>
                </div>
                <div class="text-end">
                    <div class="small text-muted">Total spent in selected period</div>
                    <div class="metric-number money">$<?= number_format((float)$totalSpent, 2) ?></div>
                    <div class="small text-muted"><?= $purchaseCount ?> <?= $purchaseCount === 1 ? 'receipt' : 'receipts' ?></div>
                </div>
            </div>

            <form action="<?= BASE_URL ?>account" method="get" class="row g-3 align-items-end report-filter mb-4">
                <div class="col-sm-6 col-lg-3">
                    <label class="form-label" for="report-from">From</label>
                    <input class="form-control" type="date" id="report-from" name="from" value="<?= $h($fromDate) ?>">
                </div>
                <div class="col-sm-6 col-lg-3">
                    <label class="form-label" for="report-to">To</label>
                    <input class="form-control" type="date" id="report-to" name="to" value="<?= $h($toDate) ?>">
                </div>
                <div class="col-sm-8 col-lg-4">
                    <label class="form-label" for="report-item">Search purchased item</label>
                    <input class="form-control" type="search" id="report-item" name="item" maxlength="100"
                        value="<?= $h($productSearch) ?>" list="purchased-product-names" placeholder="Choose a purchased item">
                    <datalist id="purchased-product-names">
                        <?php foreach ($purchasedProductNames as $product): ?>
                            <option value="<?= $h($product['product_name']) ?>">
                            <?php endforeach; ?>
                    </datalist>
                </div>
                <div class="col-sm-4 col-lg-2 d-flex gap-2">
                    <button class="btn btn-primary flex-grow-1" type="submit">Apply</button>
                    <a class="btn btn-outline-secondary" href="<?= BASE_URL ?>account" aria-label="Clear filters">Clear</a>
                </div>
            </form>

            <?php if ($reportError !== ''): ?>
                <div class="alert alert-danger" role="alert"><?= $h($reportError) ?></div>
            <?php endif; ?>

            <?php if ($productSearch !== '' && $reportError === ''): ?>
                <div class="border rounded-3 p-3 p-md-4 mb-4 bg-white">
                    <h3 class="h5 fw-bold mb-1">Item price history: <?= $h($productPurchases[0]['product_name'] ?? $productSearch) ?></h3>
                    <?php if (!$productPurchases): ?>
                        <p class="text-muted mb-0">No matching purchases were found in this period.</p>
                    <?php else: ?>
                        <p class="text-muted mb-3">Purchased <?= count($uniqueOrders) ?> <?= count($uniqueOrders) === 1 ? 'time' : 'times' ?> in the selected period.</p>
                        <div class="report-chart mb-3">
                            <svg viewBox="0 0 600 190" role="img" aria-labelledby="price-chart-title price-chart-description">
                                <title id="price-chart-title">Item price over purchase history</title>
                                <desc id="price-chart-description">Prices at each purchase, from <?= $h($productPurchases[0]['created_at']) ?> to <?= $h($productPurchases[count($productPurchases) - 1]['created_at']) ?>.</desc>
                                <line x1="30" y1="150" x2="570" y2="150" class="chart-axis"></line>
                                <polyline points="<?php foreach ($chartPoints as $point): ?><?= number_format($point['x'], 1, '.', '') ?>,<?= number_format($point['y'], 1, '.', '') ?> <?php endforeach; ?>"
                                    fill="none" class="chart-line"></polyline>
                                <?php foreach ($chartPoints as $point): ?>
                                    <circle cx="<?= number_format($point['x'], 1, '.', '') ?>" cy="<?= number_format($point['y'], 1, '.', '') ?>" r="5" class="chart-dot">
                                        <title><?= $h($point['purchase']['created_at']) ?>: $<?= number_format($point['price'], 2) ?></title>
                                    </circle>
                                <?php endforeach; ?>
                                <text x="30" y="177" class="chart-label"><?= $h((new DateTimeImmutable($productPurchases[0]['created_at']))->format('M j, Y')) ?></text>
                                <text x="570" y="177" text-anchor="end" class="chart-label"><?= $h((new DateTimeImmutable($productPurchases[count($productPurchases) - 1]['created_at']))->format('M j, Y')) ?></text>
                                <text x="35" y="20" class="chart-label">$<?= number_format($maximum, 2) ?></text>
                                <text x="35" y="142" class="chart-label">$<?= number_format($minimum, 2) ?></text>
                            </svg>
                        </div>
                        <div class="table-responsive">
                            <table class="table align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th scope="col">Date &amp; time</th>
                                        <th scope="col">Quantity</th>
                                        <th scope="col">Price each</th>
                                        <th scope="col">Change</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $previousPrice = null;
                                    foreach ($productPurchases as $purchase):
                                        $price = (float)$purchase['unit_price'];
                                        $change = $previousPrice === null ? null : $price - $previousPrice;
                                        $previousPrice = $price;
                                    ?>
                                        <tr>
                                            <td><?= $h((new DateTimeImmutable($purchase['created_at']))->format('Y-m-d \@ H:i')) ?></td>
                                            <td><?= (int)$purchase['quantity'] ?></td>
                                            <td class="money">$<?= number_format($price, 2) ?></td>
                                            <td class="money <?= $change !== null && $change > 0 ? 'text-danger' : 'text-success' ?>">
                                                <?= $change === null ? '—' : (($change > 0 ? '+' : '') . '$' . number_format($change, 2)) ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <section class="card store-card">
        <div class="card-body p-4">
            <h2 class="h4 fw-bold mb-3">Receipt history</h2>
            <?php if (!$purchases): ?>
                <p class="text-muted mb-0"><?= $reportError !== '' ? 'Receipts cannot be shown until the filters are corrected.' : 'No receipts found for this period.' ?></p>
            <?php else: ?>
                <?php foreach ($purchases as $order): ?>
                    <article class="receipt-entry py-4 border-top">
                        <div class="d-flex flex-wrap justify-content-between gap-2 mb-3">
                            <div>
                                <h3 class="h6 fw-bold mb-1">Receipt #<?= (int)$order['id'] ?></h3>
                                <time class="text-muted small" datetime="<?= $h((new DateTimeImmutable($order['created_at']))->format(DateTimeInterface::ATOM)) ?>">
                                    <?= $h((new DateTimeImmutable($order['created_at']))->format('Y-m-d \@ H:i')) ?>
                                </time>
                            </div>
                            <div class="text-end">
                                <strong class="money">$<?= number_format((float)$order['total'], 2) ?></strong>
                                <div class="small text-success">+<?= (int)$order['points_earned'] ?> points</div>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-3">
                                <thead>
                                    <tr>
                                        <th scope="col">Item</th>
                                        <th scope="col" class="text-end">Qty</th>
                                        <th scope="col" class="text-end">Price each</th>
                                        <th scope="col" class="text-end">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($purchaseItems[(int)$order['id']] ?? [] as $item): ?>
                                        <tr>
                                            <td><?= $h($item['product_name']) ?></td>
                                            <td class="text-end"><?= (int)$item['quantity'] ?></td>
                                            <td class="text-end money">$<?= number_format((float)$item['unit_price'], 2) ?></td>
                                            <td class="text-end money">$<?= number_format((float)$item['unit_price'] * (int)$item['quantity'], 2) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <form action="<?= BASE_URL ?>account/resend-receipt" method="post" class="row g-2 align-items-end">
                            <input type="hidden" name="csrf" value="<?= $h(CheckoutSecurity::csrfToken()) ?>">
                            <input type="hidden" name="order_id" value="<?= (int)$order['id'] ?>">
                            <div class="col-md-7">
                                <label class="form-label small" for="receipt_email_<?= (int)$order['id'] ?>">Email receipt to</label>
                                <input type="email" class="form-control" id="receipt_email_<?= (int)$order['id'] ?>" name="receipt_email"
                                    value="<?= $h($customer['email_address']) ?>" required>
                            </div>
                            <div class="col-md-5"><button type="submit" class="btn btn-outline-primary w-100">Resend receipt</button></div>
                        </form>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>
</div>
<?php require APP_VIEWS_PATH . '/checkout/no_js_footer.php'; ?>