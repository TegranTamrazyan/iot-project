<?php

use App\Helpers\ViewHelper;

ViewHelper::loadHeader('Sales Report');
$h = static fn($value) => htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
$fromDate = $fromDate ?? '';
$toDate = $toDate ?? '';
$reportError = $reportError ?? '';
$salesData = $salesData ?? [];
$dailyRevenue = $dailyRevenue ?? [];
$totals = $totals ?? [
    'totalItems' => 0,
    'totalRevenue' => 0.0,
    'totalOrders' => 0,
];
$topProduct = $topProduct ?? null;
$bottomProduct = $bottomProduct ?? null;
$categories = $categories ?? [];
$selectedCategory = $selectedCategory ?? '';
$chartType = $chartType ?? 'line';
?>

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
        <div>
            <div class="text-uppercase small text-primary fw-bold">Administrator</div>
            <h1 class="fw-bold mb-1">Sales Report</h1>
            <p class="text-muted mb-0">Sold items, revenue analysis, and product performance within a selected date range.</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a class="btn btn-outline-primary" href="<?= BASE_URL ?>product">Back to products</a>
            <a class="btn btn-outline-secondary" href="<?= BASE_URL ?>product/customer-activity">Customer Activity</a>
            <a class="btn btn-success" href="<?= BASE_URL ?>reports/sales?export=csv&from=<?= urlencode($fromDate) ?>&to=<?= urlencode($toDate) ?><?= $selectedCategory !== '' ? '&category=' . urlencode($selectedCategory) : '' ?>">Export CSV</a>
        </div>
    </div>

    <section class="card store-card mb-4">
        <div class="card-body p-4">
            <form action="<?= BASE_URL ?>reports/sales" method="get" class="row g-3 align-items-end mb-4">
                <div class="col-sm-6 col-lg-2">
                    <label class="form-label" for="sales-from">From</label>
                    <input class="form-control" type="date" id="sales-from" name="from"
                           value="<?= $h($fromDate) ?>" required>
                </div>
                <div class="col-sm-6 col-lg-2">
                    <label class="form-label" for="sales-to">To</label>
                    <input class="form-control" type="date" id="sales-to" name="to"
                           value="<?= $h($toDate) ?>" required>
                </div>
                <div class="col-sm-6 col-lg-2">
                    <label class="form-label" for="sales-category">Category</label>
                    <select class="form-select" id="sales-category" name="category">
                        <option value="">-- All Categories --</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $h($cat['category']) ?>" <?= ($selectedCategory === $cat['category']) ? 'selected' : '' ?>>
                                <?= $h($cat['category']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-lg-3 d-grid">
                    <button class="btn btn-primary" type="submit">Generate report</button>
                </div>
            </form>

            <?php if ($reportError !== ''): ?>
                <div class="alert alert-danger" role="alert"><?= $h($reportError) ?></div>
            <?php elseif (!array_key_exists('from', $_GET ?? []) && !array_key_exists('to', $_GET ?? [])): ?>
                <div class="alert alert-info" role="status">Select a date range and click Generate report to view sales data.</div>
            <?php else: ?>
                <div class="row g-3 mb-4">
                    <div class="col-sm-6 col-xl-3">
                        <div class="card h-100 bg-light border-0">
                            <div class="card-body">
                                <div class="small text-muted">Total items sold</div>
                                <div class="display-6 fw-bold"><?= (int)$totals['totalItems'] ?></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-xl-3">
                        <div class="card h-100 bg-light border-0">
                            <div class="card-body">
                                <div class="small text-muted">Total revenue</div>
                                <div class="display-6 fw-bold">$<?= number_format($totals['totalRevenue'], 2) ?></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-xl-3">
                        <div class="card h-100 bg-light border-0">
                            <div class="card-body">
                                <div class="small text-muted">Total orders</div>
                                <div class="display-6 fw-bold"><?= (int)$totals['totalOrders'] ?></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-xl-3">
                        <div class="card h-100 bg-light border-0">
                            <div class="card-body">
                                <div class="small text-muted">Period</div>
                                <div class="display-6 fw-bold"><?= $h($fromDate) ?> to <?= $h($toDate) ?></div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php if (empty($salesData)): ?>
                    <div class="alert alert-info" role="status">No sales recorded during this period.</div>
                <?php else: ?>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <div class="card h-100 border-0">
                                <div class="card-body">
                                    <div class="small text-muted text-success">Most sold item</div>
                                    <div class="fw-bold"><?= $h($topProduct['name']) ?></div>
                                    <div class="text-success fs-6"><?= (int)$topProduct['quantity_sold'] ?> units sold</div>
                                    <div class="small text-muted">Revenue: $<?= number_format((float)$topProduct['revenue'], 2) ?></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card h-100 border-0">
                                <div class="card-body">
                                    <div class="small text-muted text-danger">Least sold item</div>
                                    <div class="fw-bold"><?= $h($bottomProduct['name']) ?></div>
                                    <div class="text-danger fs-6"><?= (int)$bottomProduct['quantity_sold'] ?> unit<?= (int)$bottomProduct['quantity_sold'] !== 1 ? 's' : '' ?> sold</div>
                                    <div class="small text-muted">Revenue: $<?= number_format((float)$bottomProduct['revenue'], 2) ?></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <h2 class="h5 fw-bold mb-3">Sales by Product</h2>
                    <div class="table-responsive mb-4">
                        <table class="table table-striped align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Product</th>
                                    <th>Category</th>
                                    <th class="text-center">Quantity Sold</th>
                                    <th class="text-center">Revenue</th>
                                    <th class="text-center">Orders</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($salesData as $item): ?>
                                    <tr>
                                        <td><?= $h($item['name']) ?></td>
                                        <td><?= $h($item['category']) ?></td>
                                        <td class="text-center fw-bold"><?= (int)$item['quantity_sold'] ?></td>
                                        <td class="text-center">$<?= number_format((float)$item['revenue'], 2) ?></td>
                                        <td class="text-center"><?= (int)$item['order_count'] ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
            <?php endif; ?>
        </div>
    </section>
</div>

<?php
ViewHelper::loadJsScripts();
ViewHelper::loadFooter();