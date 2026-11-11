<?php

use App\Helpers\ViewHelper;
use App\Helpers\Core\FlashMessage;

$thresholds = $thresholds ?? ['Frig1' => 5.0, 'Frig2' => 5.0];
$data_url = $data_url ?? BASE_URL . 'fridge-dashboard/data';
$fan_url = $fan_url ?? BASE_URL . 'fridge-dashboard/fan';
$threshold_url = $threshold_url ?? BASE_URL . 'fridge-dashboard/threshold';

ViewHelper::loadHeader('Fridge Dashboard');
?>

<div class="container my-4" style="max-width: 1100px;">
    <h2 class="mb-2">Fridge Dashboard</h2>
    <p class="text-muted">Live readings from both refrigerators. The dashboard refreshes every three seconds.</p>

    <?= FlashMessage::render() ?>
    <!-- <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <?= htmlspecialchars((string) $_SESSION['error'], ENT_QUOTES, 'UTF-8') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?> -->

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= htmlspecialchars((string) $_SESSION['success'], ENT_QUOTES, 'UTF-8') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <div id="notification" class="alert alert-danger" role="status" style="display: none;"></div>

    <h4 class="mt-4 mb-3">Sensor readings</h4>
    <div class="row g-3">
        <?php foreach (['Frig1' => 'Refrigerator 1', 'Frig2' => 'Refrigerator 2'] as $fridge => $label): ?>
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></h5>
                        <p class="mb-1">Temperature</p>
                        <h3 id="<?= $fridge ?>-temp">-- °C</h3>
                        <p class="mb-1">Humidity</p>
                        <h3 id="<?= $fridge ?>-humidity">-- %</h3>
                        <p class="mb-1">Temperature threshold</p>
                        <p><strong><span id="<?= $fridge ?>-threshold"><?= htmlspecialchars((string) ($thresholds[$fridge] ?? 5.0), ENT_QUOTES, 'UTF-8') ?></span> °C</strong></p>
                        <p id="<?= $fridge ?>-status" class="text-muted mb-0">Waiting for sensor data...</p>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>

        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Fan status</h5>
                    <h3 id="fan-status">OFF</h3>
                    <p class="text-muted">The Raspberry Pi applies the saved fan state.</p>
                    <div class="d-flex flex-wrap gap-2">
                        <form method="POST" action="<?= htmlspecialchars($fan_url, ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="command" value="ON">
                            <button class="btn btn-success" type="submit">Turn fan ON</button>
                        </form>
                        <form method="POST" action="<?= htmlspecialchars($fan_url, ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="command" value="OFF">
                            <button class="btn btn-secondary" type="submit">Turn fan OFF</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <h4 class="mt-4 mb-3">Temperature thresholds</h4>
    <div class="row g-3">
        <?php foreach (['Frig1' => 'Refrigerator 1', 'Frig2' => 'Refrigerator 2'] as $fridge => $label): ?>
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></h5>
                        <form method="POST" action="<?= htmlspecialchars($threshold_url, ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="fridge" value="<?= htmlspecialchars($fridge, ENT_QUOTES, 'UTF-8') ?>">
                            <div class="mb-3">
                                <label class="form-label" for="threshold-<?= $fridge ?>">Maximum temperature (°C)</label>
                                <input class="form-control" id="threshold-<?= $fridge ?>" type="number" name="threshold" step="0.1" min="-20" max="30" value="<?= htmlspecialchars((string) ($thresholds[$fridge] ?? 5.0), ENT_QUOTES, 'UTF-8') ?>" required>
                            </div>
                            <button class="btn btn-primary" type="submit">Save threshold</button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <h4 class="mt-4 mb-3">Temperature warnings</h4>
    <div id="alerts" class="d-flex flex-column gap-2">
        <p class="text-muted">Checking current temperatures...</p>
    </div>
    <p class="text-muted mt-3" id="updated-at">Waiting for first update...</p>
</div>

<script>
const fridgeDashboardDataUrl = <?= json_encode($data_url, JSON_UNESCAPED_SLASHES) ?>;

async function refreshFridgeDashboard() {
    try {
        const response = await fetch(fridgeDashboardDataUrl, { cache: 'no-store' });
        if (!response.ok) {
            throw new Error('HTTP ' + response.status);
        }

        const data = await response.json();

        for (const fridge of ['Frig1', 'Frig2']) {
            const item = data.fridges[fridge];
            document.getElementById(fridge + '-temp').textContent = item.temperature === null ? '-- °C' : Number(item.temperature).toFixed(1) + ' °C';
            document.getElementById(fridge + '-humidity').textContent = item.humidity === null ? '-- %' : Number(item.humidity).toFixed(1) + ' %';
            document.getElementById(fridge + '-threshold').textContent = Number(item.threshold).toFixed(1);

            const status = document.getElementById(fridge + '-status');
            if (item.temperature === null) {
                status.textContent = 'No sensor data received yet.';
                status.className = 'text-muted mb-0';
            } else if (Number(item.temperature) > Number(item.threshold)) {
                status.textContent = 'Temperature is above the threshold.';
                status.className = 'text-danger fw-bold mb-0';
            } else {
                status.textContent = 'Temperature is within the threshold.';
                status.className = 'text-success fw-bold mb-0';
            }
        }

        document.getElementById('fan-status').textContent = data.fan_status === 'ON' ? 'ON' : 'OFF';

        const alerts = document.getElementById('alerts');
        alerts.replaceChildren();
        if (!data.alerts.length) {
            const message = document.createElement('p');
            message.className = 'text-success';
            message.textContent = 'No current temperature warnings.';
            alerts.appendChild(message);
        } else {
            for (const alert of data.alerts) {
                const message = document.createElement('div');
                message.className = 'alert alert-danger mb-0';
                message.textContent = alert.message + ' Current temperature: ' + Number(alert.temperature).toFixed(1) + ' °C; threshold: ' + Number(alert.threshold_value).toFixed(1) + ' °C.';
                alerts.appendChild(message);
            }
        }

        const notification = document.getElementById('notification');
        if (data.alerts.length) {
            notification.textContent = 'Temperature warning: ' + data.alerts.map(alert => alert.fridge).join(', ') + ' is above the configured threshold.';
            notification.style.display = 'block';
        } else {
            notification.style.display = 'none';
        }

        document.getElementById('updated-at').textContent = 'Last dashboard update: ' + new Date().toLocaleTimeString();
    } catch (error) {
        document.getElementById('updated-at').textContent = 'Could not load dashboard data. Check the PHP and database configuration.';
    }
}

refreshFridgeDashboard();
setInterval(refreshFridgeDashboard, 3000);
</script>

<?php
ViewHelper::loadJsScripts();
ViewHelper::loadFooter();
?>
