<?php

use App\Helpers\ViewHelper;

ViewHelper::loadHeader('Customer Activity Report');
$h = static fn($value) => htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
$activity = $activity ?? [];
$dailyActivity = $activity['dailyActivity'] ?? [];
$reportError = $reportError ?? '';
$chartLabels = array_column($dailyActivity, 'date');
$chartCounts = array_column($dailyActivity, 'customer_count');
?>

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
        <div>
            <div class="text-uppercase small text-primary fw-bold">Administrator</div>
            <h1 class="fw-bold mb-1">Customer Activity Report</h1>
            <p class="text-muted mb-0">Distinct customers who made purchases during the selected period.</p>
        </div>
        <a class="btn btn-outline-primary" href="<?= BASE_URL ?>product">Back to products</a>
    </div>

    <section class="card store-card mb-4">
        <div class="card-body p-4">
            <form action="<?= BASE_URL ?>product/customer-activity" method="get" class="row g-3 align-items-end mb-4">
                <div class="col-sm-6 col-lg-4">
                    <label class="form-label" for="activity-from">From</label>
                    <input class="form-control" type="date" id="activity-from" name="from"
                        value="<?= $h($fromDate ?? '') ?>" required>
                </div>
                <div class="col-sm-6 col-lg-4">
                    <label class="form-label" for="activity-to">To</label>
                    <input class="form-control" type="date" id="activity-to" name="to"
                        value="<?= $h($toDate ?? '') ?>" required>
                </div>
                <div class="col-lg-4">
                    <button class="btn btn-primary" type="submit">Generate report</button>
                </div>
            </form>

            <?php if ($reportError !== ''): ?>
                <div class="alert alert-danger" role="alert"><?= $h($reportError) ?></div>
            <?php else: ?>
                <div class="row g-3 mb-4">
                    <div class="col-sm-6 col-xl-4">
                        <div class="card h-100 bg-light border-0">
                            <div class="card-body">
                                <div class="small text-muted">Total customers</div>
                                <div class="display-6 fw-bold"><?= (int)$activity['totalCustomers'] ?></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-xl-4">
                        <div class="card h-100 bg-light border-0">
                            <div class="card-body">
                                <div class="small text-muted">New customers</div>
                                <div class="display-6 fw-bold"><?= (int)$activity['newCustomers'] ?></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-xl-4">
                        <div class="card h-100 bg-light border-0">
                            <div class="card-body">
                                <div class="small text-muted">Returning customers</div>
                                <div class="display-6 fw-bold"><?= (int)$activity['returningCustomers'] ?></div>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if ($activity['totalCustomers'] === 0): ?>
                    <div class="alert alert-info" role="status">No customer purchases were recorded during this period.</div>
                <?php else: ?>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <div class="p-3 border rounded h-100">
                                <div class="small text-muted">Busiest day</div>
                                <strong><?= $h($activity['busiestDay']['date']) ?></strong>
                                <span> — <?= (int)$activity['busiestDay']['customer_count'] ?> customers</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-3 border rounded h-100">
                                <div class="small text-muted">Least busy day</div>
                                <strong><?= $h($activity['leastBusyDay']['date']) ?></strong>
                                <span> — <?= (int)$activity['leastBusyDay']['customer_count'] ?> customers</span>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <h2 class="h5 fw-bold">Daily customer activity</h2>
                <?php if ($dailyActivity): ?>
                    <div style="height: 360px;">
                        <canvas id="customer-activity-chart" role="img"
                            aria-label="Bar chart showing distinct purchasing customers for each date"></canvas>
                    </div>
                <?php else: ?>
                    <p class="text-muted mb-0">Generate a report to view daily activity.</p>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </section>
</div>

<?php if ($reportError === '' && $dailyActivity): ?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        new Chart(document.getElementById('customer-activity-chart'), {
            type: 'bar',
            data: {
                labels: <?= json_encode($chartLabels, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
                datasets: [{
                    label: 'Distinct customers',
                    data: <?= json_encode($chartCounts) ?>,
                    backgroundColor: 'rgba(13, 110, 253, 0.65)',
                    borderColor: 'rgb(13, 110, 253)',
                    borderWidth: 1
                }]
            },
            options: {
                maintainAspectRatio: false,
                scales: {
                    x: { title: { display: true, text: 'Date' } },
                    y: { beginAtZero: true, ticks: { precision: 0 }, title: { display: true, text: 'Customers' } }
                }
            }
        });
    </script>
<?php endif; ?>

<?php
ViewHelper::loadJsScripts();
ViewHelper::loadFooter();
