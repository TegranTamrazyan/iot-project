<?php

use App\Helpers\ViewHelper;
use App\Helpers\CheckoutSecurity;
use App\Helpers\Core\FlashMessage;

ViewHelper::loadHeader('Purchase Report');
$h = static fn($value) => htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
$customer = $customer ?? [];
$purchases = $purchases ?? [];
$totalSpent = $totalSpent ?? 0;
$spendingByDate = $spendingByDate ?? [];
$productRows = $productRows ?? [];
$productRowsCount = $productRowsCount ?? count($productRows);
$productRowsPage = $productRowsPage ?? 1;
$productRowsPageCount = $productRowsPageCount ?? 1;
$productRowsPerPage = $productRowsPerPage ?? 10;
$productSearch = $productSearch ?? '';
$productPurchases = $productPurchases ?? [];
$purchasedProductNames = $purchasedProductNames ?? [];
$reportError = $reportError ?? '';
$fromDate = $fromDate ?? '';
$toDate = $toDate ?? '';

$spendChartPoints = [];
$maxDailySpend = max($spendingByDate ?: [0]);
$spendDates = array_keys($spendingByDate);
$firstSpendTimestamp = $spendDates
    ? (new DateTimeImmutable($spendDates[0]))->getTimestamp()
    : 0;
$lastSpendTimestamp = $spendDates
    ? (new DateTimeImmutable($spendDates[count($spendDates) - 1]))->getTimestamp()
    : 0;
$spendTimestampRange = $lastSpendTimestamp - $firstSpendTimestamp;
$spendScale = $maxDailySpend > 0.0 ? $maxDailySpend : 1.0;
foreach ($spendingByDate as $date => $amount) {
    $timestamp = (new DateTimeImmutable($date))->getTimestamp();
    $spendChartPoints[] = [
        'x' => $spendTimestampRange > 0
            ? 35 + (530 * ($timestamp - $firstSpendTimestamp) / $spendTimestampRange)
            : 300,
        'y' => 150 - (120 * (float)$amount / $spendScale),
        'date' => $date,
        'amount' => (float)$amount,
    ];
}

$priceChartBars = [];
$uniqueOrders = [];
if ($productPurchases) {
    $prices = array_map(static fn(array $purchase): float => (float)$purchase['unit_price'], $productPurchases);
    $maximumPrice = max($prices);
    $slotWidth = 530 / count($prices);
    $barWidth = min(40, max(2, $slotWidth * 0.7));
    foreach ($prices as $index => $price) {
        $height = 120 * $price / max($maximumPrice, 0.01);
        $priceChartBars[] = [
            'x' => 35 + ($slotWidth * $index) + (($slotWidth - $barWidth) / 2),
            'y' => 150 - $height,
            'width' => $barWidth,
            'height' => $height,
            'price' => $price,
            'purchase' => $productPurchases[$index],
        ];
    }
    $uniqueOrders = array_unique(array_column($productPurchases, 'order_id'));
}
?>
<div class="container py-5">
    <?= FlashMessage::render() ?>
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
        <div>
            <div class="text-uppercase small text-primary fw-bold">Customer portal</div>
            <h1 class="fw-bold mb-1">Purchase report</h1>
            <p class="text-muted mb-0">Spending and purchased products for <?= $h($customer['customer_name']) ?>.</p>
        </div>
        <a class="btn btn-outline-primary" href="<?= BASE_URL ?>account">Back to my account</a>
    </div>

    <section class="card store-card mb-4">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
                <div>
                    <h2 class="h4 fw-bold mb-1">Spending over time</h2>
                    <p class="text-muted mb-0">Daily totals include tax and reflect receipts in the selected date range.</p>
                </div>
                <div class="text-end">
                    <div class="small text-muted">Total spent in selected period</div>
                    <div class="metric-number money">$<?= number_format((float)$totalSpent, 2) ?></div>
                    <div class="small text-muted"><?= count($purchases) ?> <?= count($purchases) === 1 ? 'receipt' : 'receipts' ?></div>
                </div>
            </div>

            <form action="<?= BASE_URL ?>account/report" method="get" class="row g-3 align-items-end report-filter mb-4">
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
                    <a class="btn btn-outline-secondary" href="<?= BASE_URL ?>account/report" aria-label="Clear filters">Clear</a>
                </div>
            </form>

            <?php if ($reportError !== ''): ?>
                <div class="alert alert-danger" role="alert"><?= $h($reportError) ?></div>
            <?php elseif (!$spendChartPoints): ?>
                <p class="text-muted mb-0">No purchases found for this period.</p>
            <?php else: ?>
                <div class="report-chart">
                    <svg viewBox="0 0 600 190" role="img" aria-labelledby="spend-chart-title spend-chart-description">
                        <title id="spend-chart-title">Total spending by purchase date</title>
                        <desc id="spend-chart-description">
                            Daily spending from <?= $h($spendChartPoints[0]['date']) ?> to
                            <?= $h($spendChartPoints[count($spendChartPoints) - 1]['date']) ?>.
                        </desc>
                        <line x1="30" y1="150" x2="570" y2="150" class="chart-axis"></line>
                        <polyline points="<?php foreach ($spendChartPoints as $point): ?><?= number_format($point['x'], 1, '.', '') ?>,<?= number_format($point['y'], 1, '.', '') ?> <?php endforeach; ?>"
                            fill="none" class="chart-line"></polyline>
                        <?php foreach ($spendChartPoints as $point): ?>
                            <circle cx="<?= number_format($point['x'], 1, '.', '') ?>" cy="<?= number_format($point['y'], 1, '.', '') ?>" r="5" class="chart-dot">
                                <title><?= $h($point['date']) ?>: $<?= number_format($point['amount'], 2) ?></title>
                            </circle>
                        <?php endforeach; ?>
                        <?php if (count($spendChartPoints) === 1): ?>
                            <text x="300" y="177" text-anchor="middle" class="chart-label"><?= $h((new DateTimeImmutable($spendChartPoints[0]['date']))->format('M j, Y')) ?></text>
                        <?php else: ?>
                            <text x="35" y="177" class="chart-label"><?= $h((new DateTimeImmutable($spendChartPoints[0]['date']))->format('M j, Y')) ?></text>
                            <text x="565" y="177" text-anchor="end" class="chart-label"><?= $h((new DateTimeImmutable($spendChartPoints[count($spendChartPoints) - 1]['date']))->format('M j, Y')) ?></text>
                        <?php endif; ?>
                    </svg>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <?php if ($productSearch !== '' && $reportError === ''): ?>
        <section class="card store-card mb-4">
            <div class="card-body p-4">
                <h2 class="h5 fw-bold mb-1">Item price history: <?= $h($productPurchases[0]['product_name'] ?? $productSearch) ?></h2>
                <?php if (!$productPurchases): ?>
                    <p class="text-muted mb-0">No matching purchases were found in this period.</p>
                <?php else: ?>
                    <p class="text-muted mb-3">
                        Purchased <?= count($uniqueOrders) ?> <?= count($uniqueOrders) === 1 ? 'time' : 'times' ?> in the selected period.
                        Exact purchase prices are listed below.
                    </p>
                    <div class="report-chart mb-3">
                        <svg viewBox="0 0 600 190" role="img" aria-labelledby="price-chart-title price-chart-description">
                            <title id="price-chart-title">Item price at each purchase</title>
                            <desc id="price-chart-description">Bar heights show the item price for each purchase. Purchase dates range from <?= $h($productPurchases[0]['created_at']) ?> to <?= $h($productPurchases[count($productPurchases) - 1]['created_at']) ?>.</desc>
                            <line x1="30" y1="150" x2="570" y2="150" class="chart-axis"></line>
                            <?php foreach ($priceChartBars as $bar): ?>
                                <rect x="<?= number_format($bar['x'], 1, '.', '') ?>"
                                    y="<?= number_format($bar['y'], 1, '.', '') ?>"
                                    width="<?= number_format($bar['width'], 1, '.', '') ?>"
                                    height="<?= number_format($bar['height'], 1, '.', '') ?>"
                                    class="chart-bar">
                                    <title><?= $h($bar['purchase']['created_at']) ?>: $<?= number_format($bar['price'], 2) ?></title>
                                </rect>
                            <?php endforeach; ?>
                            <?php if (count($priceChartBars) === 1): ?>
                                <text x="300" y="177" text-anchor="middle" class="chart-label"><?= $h((new DateTimeImmutable($productPurchases[0]['created_at']))->format('M j, Y')) ?></text>
                            <?php else: ?>
                                <text x="35" y="177" class="chart-label"><?= $h((new DateTimeImmutable($productPurchases[0]['created_at']))->format('M j, Y')) ?></text>
                                <text x="565" y="177" text-anchor="end" class="chart-label"><?= $h((new DateTimeImmutable($productPurchases[count($productPurchases) - 1]['created_at']))->format('M j, Y')) ?></text>
                            <?php endif; ?>
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
        </section>
    <?php endif; ?>

    <section class="card store-card">
        <div class="card-body p-4">
            <h2 class="h4 fw-bold mb-1">Products purchased by date</h2>
            <p class="text-muted mb-3">Each product is listed with its receipt date, quantity, and cost.</p>
            <?php if ($reportError !== ''): ?>
                <p class="text-muted mb-0">Products cannot be shown until the filters are corrected.</p>
            <?php elseif (!$productRows): ?>
                <p class="text-muted mb-0">No purchased products found for this period.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr>
                                <th scope="col">Purchase date</th>
                                <th scope="col">Product</th>
                                <th scope="col" class="text-end">Quantity</th>
                                <th scope="col" class="text-end">Price each</th>
                                <th scope="col" class="text-end">Product total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($productRows as $item): ?>
                                <tr>
                                    <td>
                                        <time datetime="<?= $h((new DateTimeImmutable($item['created_at']))->format(DateTimeInterface::ATOM)) ?>">
                                            <?= $h((new DateTimeImmutable($item['created_at']))->format('Y-m-d \@ H:i')) ?>
                                        </time>
                                    </td>
                                    <td><?= $h($item['product_name']) ?></td>
                                    <td class="text-end"><?= (int)$item['quantity'] ?></td>
                                    <td class="text-end money">$<?= number_format((float)$item['unit_price'], 2) ?></td>
                                    <td class="text-end money">$<?= number_format((float)$item['unit_price'] * (int)$item['quantity'], 2) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php if ($productRowsPageCount > 1): ?>
                    <?php
                    $productPageParams = array_filter([
                        'from' => $fromDate,
                        'to' => $toDate,
                        'item' => $productSearch,
                    ], static fn(string $value): bool => $value !== '');
                    $productPageUrl = static function (int $page) use ($productPageParams): string {
                        $params = $productPageParams;
                        $params['products_page'] = $page;
                        return BASE_URL . 'account/report?' . http_build_query($params);
                    };
                    ?>
                    <nav class="mt-4" aria-label="Purchased product pages">
                        <ul class="pagination justify-content-center flex-wrap mb-0">
                            <li class="page-item <?= $productRowsPage <= 1 ? 'disabled' : '' ?>">
                                <?php if ($productRowsPage > 1): ?>
                                    <a class="page-link" href="<?= $h($productPageUrl($productRowsPage - 1)) ?>" aria-label="Previous page">Previous</a>
                                <?php else: ?>
                                    <span class="page-link">Previous</span>
                                <?php endif; ?>
                            </li>
                            <?php for ($pageNumber = 1; $pageNumber <= $productRowsPageCount; $pageNumber++): ?>
                                <li class="page-item <?= $pageNumber === $productRowsPage ? 'active' : '' ?>">
                                    <?php if ($pageNumber === $productRowsPage): ?>
                                        <span class="page-link" aria-current="page"><?= $pageNumber ?></span>
                                    <?php else: ?>
                                        <a class="page-link" href="<?= $h($productPageUrl($pageNumber)) ?>"><?= $pageNumber ?></a>
                                    <?php endif; ?>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item <?= $productRowsPage >= $productRowsPageCount ? 'disabled' : '' ?>">
                                <?php if ($productRowsPage < $productRowsPageCount): ?>
                                    <a class="page-link" href="<?= $h($productPageUrl($productRowsPage + 1)) ?>" aria-label="Next page">Next</a>
                                <?php else: ?>
                                    <span class="page-link">Next</span>
                                <?php endif; ?>
                            </li>
                        </ul>
                        <p class="text-muted text-center small mt-2 mb-0">
                            Showing <?= min(($productRowsPage - 1) * $productRowsPerPage + 1, $productRowsCount) ?>–<?= min($productRowsPage * $productRowsPerPage, $productRowsCount) ?>
                            of <?= $productRowsCount ?> <?= $productRowsCount === 1 ? 'product' : 'products' ?>
                        </p>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </section>
</div>
<?php require APP_VIEWS_PATH . '/checkout/no_js_footer.php'; ?>
