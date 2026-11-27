<?php

use App\Helpers\ViewHelper;

ViewHelper::loadHeader('Inventory Report');
$h = static fn($value) => htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
$inventory = $inventory ?? [];
$categories = $categories ?? [];
$selectedCategory = $selectedCategory ?? '';
$lowThreshold = $lowThreshold ?? 5;
$totalProducts = $totalProducts ?? 0;
$lowStockCount = $lowStockCount ?? 0;
$totalInStock = $totalInStock ?? 0;
$totalValue = $totalValue ?? 0.0;

$chartLabels = array_column($inventory, 'name');
$chartQuantities = array_column($inventory, 'in_stock');
$chartColors = array_column($inventory, 'stock_color');
$bgColors = array_map(
    static fn($c) => $c === 'danger' ? 'rgba(220, 53, 69, 0.65)'
        : ($c === 'warning' ? 'rgba(255, 193, 7, 0.65)' : 'rgba(25, 135, 84, 0.65)'),
    $chartColors
);
$borderColors = array_map(
    static fn($c) => $c === 'danger' ? 'rgb(220, 53, 69)'
        : ($c === 'warning' ? 'rgb(255, 193, 7)' : 'rgb(25, 135, 84)'),
    $chartColors
);
?>

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
        <div>
            <div class="text-uppercase small text-primary fw-bold">Administrator</div>
            <h1 class="fw-bold mb-1">Inventory Report</h1>
            <p class="text-muted mb-0">Current stock levels for all active products. Real-time from stock receptions and completed orders.</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a class="btn btn-outline-primary" href="<?= BASE_URL ?>product">Back to products</a>
            <a class="btn btn-outline-secondary" href="<?= BASE_URL ?>product/customer-activity">Customer Activity</a>
            <a class="btn btn-success" href="<?= BASE_URL ?>reports/inventory?export=csv<?= $selectedCategory !== '' ? '&category=' . urlencode($selectedCategory) : '' ?>">Export CSV</a>
        </div>
    </div>

    <section class="card store-card mb-4">
        <div class="card-body p-4">
            <form action="<?= BASE_URL ?>reports/inventory" method="get" class="row g-3 align-items-end mb-4">
                <div class="col-sm-6 col-lg-3">
                    <label class="form-label" for="inventory-category">Category</label>
                    <select class="form-select" id="inventory-category" name="category">
                        <option value="">-- All Categories --</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $h($cat['category']) ?>" <?= ($selectedCategory === $cat['category']) ? 'selected' : '' ?>>
                                <?= $h($cat['category']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <label class="form-label" for="low-threshold">Low stock threshold (&le;)</label>
                    <input class="form-control" type="number" id="low-threshold" name="low_threshold"
                           value="<?= $h($lowThreshold) ?>" min="0" required>
                </div>
                <div class="col-lg-3 d-grid">
                    <button class="btn btn-primary" type="submit">Apply filters</button>
                </div>
            </form>

            <div class="row g-3 mb-4">
                <div class="col-sm-6 col-xl-3">
                    <div class="card h-100 bg-light border-0">
                        <div class="card-body">
                            <div class="small text-muted">Total products</div>
                            <div class="display-6 fw-bold"><?= (int)$totalProducts ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <div class="card h-100 bg-light border-0">
                        <div class="card-body">
                            <div class="small text-muted">Low stock items</div>
                            <div class="display-6 fw-bold text-danger"><?= (int)$lowStockCount ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <div class="card h-100 bg-light border-0">
                        <div class="card-body">
                            <div class="small text-muted">Units in stock</div>
                            <div class="display-6 fw-bold"><?= (int)$totalInStock ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <div class="card h-100 bg-light border-0">
                        <div class="card-body">
                            <div class="small text-muted">Total stock value</div>
                            <div class="display-6 fw-bold">$<?= number_format($totalValue, 2) ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <?php if ($totalProducts === 0): ?>
                <div class="alert alert-info" role="status">No products found matching your filters.</div>
            <?php else: ?>
                <div class="d-flex justify-content-center gap-3 mb-3 text-muted small">
                    <span><strong style="color:#198754;">&#9632;</strong> Green = OK (&gt; <?= (int)$lowThreshold * 2 ?> units)</span>
                    <span><strong style="color:#ffc107;">&#9632;</strong> Yellow = Moderate (<?= (int)$lowThreshold + 1 ?>–<?= (int)$lowThreshold * 2 ?> units)</span>
                    <span><strong style="color:#dc3545;">&#9632;</strong> Red = Low (&le; <?= (int)$lowThreshold ?> units)</span>
                </div>

                <div class="table-responsive mb-4">
                    <table class="table table-striped align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Product</th>
                                <th>ID</th>
                                <th>Category</th>
                                <th>Price</th>
                                <th>UPC</th>
                                <th class="text-center">Available Qty</th>
                                <th class="text-center">Stock Level</th>
                                <th class="text-center">Received</th>
                                <th class="text-center">Sold</th>
                                <th>Last Restocked</th>
                                <th class="text-center">EPCs</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($inventory as $item): ?>
                                <tr>
                                    <td><?= $h($item['name']) ?></td>
                                    <td><?= (int)$item['id'] ?></td>
                                    <td><?= $h($item['category']) ?></td>
                                    <td>$<?= number_format((float)$item['price'], 2) ?></td>
                                    <td><?= $h($item['upc']) ?></td>
                                    <td class="text-center fw-bold"><?= (int)$item['in_stock'] ?></td>
                                    <td class="text-center">
                                        <span class="badge bg-<?= $item['stock_color'] ?> fw-normal"><?= ucfirst($item['stock_level']) ?></span>
                                    </td>
                                    <td class="text-center"><?= (int)$item['received_total'] ?></td>
                                    <td class="text-center"><?= (int)$item['sold_total'] ?></td>
                                    <td><?= $item['last_restocked'] ? $h((new \DateTimeImmutable($item['last_restocked']))->format('Y-m-d')) : 'Never' ?></td>
                                    <td class="text-center"><?= (int)$item['epcs_registered'] ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <h2 class="h5 fw-bold mb-3">Stock Levels by Product</h2>
                <div style="height: 400px;">
                    <canvas id="inventory-chart" role="img"
                            aria-label="Bar chart showing available quantity per product, colored by stock level"></canvas>
                </div>

                <div class="d-flex justify-content-center gap-2 mt-3">
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="switchChart('bar')">Bar Chart</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="switchChart('pie')">Pie Chart</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="switchChart('doughnut')">Doughnut Chart</button>
                </div>
            <?php endif; ?>
        </div>
    </section>
</div>

<?php if ($totalProducts > 0): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const inventoryLabels = <?= json_encode($chartLabels, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const inventoryData = <?= json_encode($chartQuantities) ?>;
    const inventoryBgColors = <?= json_encode($bgColors) ?>;
    const inventoryBorderColors = <?= json_encode($borderColors) ?>;

    let inventoryChart = null;

    function createInventoryChart(type) {
        const ctx = document.getElementById('inventory-chart');
        if (inventoryChart) {
            inventoryChart.destroy();
        }
        inventoryChart = new Chart(ctx, {
            type: type,
            data: {
                labels: inventoryLabels,
                datasets: [{
                    label: 'Available Quantity',
                    data: inventoryData,
                    backgroundColor: inventoryBgColors,
                    borderColor: inventoryBorderColors,
                    borderWidth: type === 'pie' || type === 'doughnut' ? 2 : 1,
                }]
            },
            options: {
                maintainAspectRatio: false,
                responsive: true,
                plugins: {
                    legend: {
                        display: type === 'pie' || type === 'doughnut',
                        position: 'bottom'
                    },
                    title: {
                        display: true,
                        text: 'Available Quantity by Product (color-coded by stock level)'
                    }
                },
                scales: type === 'pie' || type === 'doughnut' ? {} : {
                    x: { title: { display: true, text: 'Product' } },
                    y: { beginAtZero: true, ticks: { precision: 0 }, title: { display: true, text: 'Units' } }
                }
            }
        });
    }

    function switchChart(type) {
        createInventoryChart(type);
    }

    createInventoryChart('bar');
</script>
<?php endif; ?>

<?php
ViewHelper::loadJsScripts();
ViewHelper::loadFooter();