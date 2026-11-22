
<?php

use App\Helpers\ViewHelper;
use App\Helpers\Core\FlashMessage;

ViewHelper::loadHeader('Fridge Dashboard');

$h = static fn($value) => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');

$data_url = BASE_URL . 'fridge-dashboard/data';

?>

<div class="container my-4" style="max-width: 1100px;">

    <?= FlashMessage::render() ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger">
            <?= $h($_SESSION['error']) ?>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success">
            <?= $h($_SESSION['success']) ?>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <h2 class="text-center fw-light mb-3">Sensor Readings</h2>

    <div class="row g-3">

        <?php foreach (['Frig1', 'Frig2'] as $fridge): ?>

            <?php
                $reading = $fridges[$fridge];
                $temperature = $reading['temperature'];
                $humidity = $reading['humidity'];
                $threshold = $thresholds[$fridge] ?? 5.0;
            ?>

            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-body p-4">

                        <h3 class="fw-light mb-3"><?= $h($fridge) ?></h3>

                        <p>
                            Temperature:
                            <span id="<?= $fridge ?>-temp">
                                <?= $temperature !== null
                                    ? number_format((float) $temperature, 1) . ' °C'
                                    : '-- °C' ?>
                            </span>
                        </p>

                        <p>
                            Humidity:
                            <span id="<?= $fridge ?>-humidity">
                                <?= $humidity !== null
                                    ? number_format((float) $humidity, 1) . ' %'
                                    : '-- %' ?>
                            </span>
                        </p>

                        <p>
                            Threshold:
                            <span id="<?= $fridge ?>-threshold">
                                <?= number_format((float) $threshold, 1) ?> °C
                            </span>
                        </p>

                        <p
                            id="<?= $fridge ?>-status"
                            class="<?= $temperature === null
                                ? 'text-muted'
                                : ($temperature > $threshold ? 'text-danger' : 'text-muted') ?>"
                        >
                            <?= $temperature === null
                                ? 'No sensor data'
                                : ($temperature > $threshold ? 'Temperature too high!' : 'Temperature OK') ?>
                        </p>

                        <form
                            method="POST"
                            action="<?= $h($threshold_url) ?>"
                            class="d-flex align-items-center flex-wrap gap-1"
                        >
                            <input
                                type="hidden"
                                name="fridge"
                                value="<?= $h($fridge) ?>"
                            >

                            <input
                                type="number"
                                class="form-control form-control-sm"
                                name="threshold"
                                step="0.1"
                                min="-20"
                                max="30"
                                value="<?= $h($threshold) ?>"
                                style="width: 210px;"
                                required
                            >

                            <button class="btn btn-primary btn-sm" type="submit">
                                Save Threshold
                            </button>

                        </form>

                    </div>
                </div>
            </div>

        <?php endforeach; ?>

        <div class="col-md-6 mx-auto">
            <div class="card h-100">
                <div class="card-body p-4">

                    <h3 class="fw-light mb-3">Bluetooth Refrigerator</h3>

                    <p>
                        Temperature:
                        <span id="bluetooth-temp">
                            <?= isset($bluetooth['temperature'])
                                ? number_format((float) $bluetooth['temperature'], 1) . ' °C'
                                : '-- °C' ?>
                        </span>
                    </p>

                    <p>
                        Humidity:
                        <span id="bluetooth-humidity">
                            <?= isset($bluetooth['humidity'])
                                ? number_format((float) $bluetooth['humidity'], 1) . ' %'
                                : '-- %' ?>
                        </span>
                    </p>

                    <p>
                        Motion Sensor:
                        <span
                            id="bluetooth-motion"
                            class="<?= !isset($bluetooth['motion_sensor'])
                                ? 'text-muted'
                                : ((int) $bluetooth['motion_sensor'] === 1 ? 'text-warning' : 'text-success') ?>"
                        >
                            <?= !isset($bluetooth['motion_sensor'])
                                ? 'No data'
                                : ((int) $bluetooth['motion_sensor'] === 1 ? 'Motion detected' : 'No motion') ?>
                        </span>
                    </p>

                    <p class="text-muted mb-0">
                        Last Reading:
                        <span id="bluetooth-time">
                            <?= $h($bluetooth['recorded_at'] ?? '--') ?>
                        </span>
                    </p>

                </div>
            </div>
        </div>

    </div>

    <h2 class="text-center fw-light mt-4 mb-3">Fan Control</h2>

    <div
        id="fan-box"
        class="card mx-auto"
        style="max-width: 550px; background-color: <?= $fanStatus === 'ON' ? '#198754' : '#842029' ?> !important; border-color: <?= $fanStatus === 'ON' ? '#198754' : '#842029' ?> !important;"
    >
        <div
            id="fan-body"
            class="card-body text-center p-4"
            style="background-color: <?= $fanStatus === 'ON' ? '#105736' : '#842029' ?> !important; color: white !important;"
        >

            <p class="mb-2">Fan Status:</p>

            <h3 class="mb-3">
                <span
                    id="fan-status"
                    class="badge bg-light <?= $fanStatus === 'ON' ? 'text-success' : 'text-danger' ?>"
                >
                    <?= $h($fanStatus) ?>
                </span>
            </h3>

            <div class="d-flex justify-content-center gap-2">

                <form method="POST" action="<?= $h($fan_url) ?>">
                    <input type="hidden" name="command" value="ON">

                    <button class="btn btn-success" type="submit">
                        Turn Fan ON
                    </button>
                </form>

                <form method="POST" action="<?= $h($fan_url) ?>">
                    <input type="hidden" name="command" value="OFF">

                    <button class="btn btn-danger" type="submit">
                        Turn Fan OFF
                    </button>
                </form>

            </div>

        </div>
    </div>

    <h2 class="text-center fw-light mt-4 mb-3">Temperature Warnings</h2>

    <div id="alerts">

        <?php if (empty($alerts)): ?>

            <p class="text-success text-center">
                No current temperature warnings.
            </p>

        <?php else: ?>

            <?php foreach ($alerts as $alert): ?>
                <div class="alert alert-danger">
                    <?= $h($alert) ?>
                </div>
            <?php endforeach; ?>

        <?php endif; ?>

    </div>

    <p class="text-muted text-center mt-4">
        Last dashboard update:
        <span id="updated-at"><?= date('Y-m-d H:i:s') ?></span>
    </p>

</div>

<script>
const fridgeDashboardDataUrl = <?= json_encode($data_url, JSON_UNESCAPED_SLASHES) ?>;

async function refreshFridgeDashboard() {
    try {
        const response = await fetch(fridgeDashboardDataUrl, {
            cache: 'no-store'
        });

        if (!response.ok) {
            throw new Error('HTTP ' + response.status);
        }

        const data = await response.json();

        // Update both DHT11 refrigerators.
        for (const fridge of ['Frig1', 'Frig2']) {
            const item = data.fridges[fridge];

            if (!item) {
                continue;
            }

            document.getElementById(fridge + '-temp').textContent =
                item.temperature === null
                    ? '-- °C'
                    : Number(item.temperature).toFixed(1) + ' °C';

            document.getElementById(fridge + '-humidity').textContent =
                item.humidity === null
                    ? '-- %'
                    : Number(item.humidity).toFixed(1) + ' %';

            document.getElementById(fridge + '-threshold').textContent =
                Number(item.threshold).toFixed(1) + ' °C';

            const status = document.getElementById(fridge + '-status');

            if (item.temperature === null) {
                status.textContent = 'No sensor data';
                status.className = 'text-muted';

            } else if (Number(item.temperature) > Number(item.threshold)) {
                status.textContent = 'Temperature too high!';
                status.className = 'text-danger';

            } else {
                status.textContent = 'Temperature OK';
                status.className = 'text-muted';
            }
        }

        // Update the Bluetooth refrigerator.
        const bluetooth = data.bluetooth ?? {};

        document.getElementById('bluetooth-temp').textContent =
            bluetooth.temperature == null
                ? '-- °C'
                : Number(bluetooth.temperature).toFixed(1) + ' °C';

        document.getElementById('bluetooth-humidity').textContent =
            bluetooth.humidity == null
                ? '-- %'
                : Number(bluetooth.humidity).toFixed(1) + ' %';

        const motion = document.getElementById('bluetooth-motion');

        if (bluetooth.motion_sensor == null) {
            motion.textContent = 'No data';
            motion.className = 'text-muted';

        } else if (Number(bluetooth.motion_sensor) === 1) {
            motion.textContent = 'Motion detected';
            motion.className = 'text-warning';

        } else {
            motion.textContent = 'No motion';
            motion.className = 'text-success';
        }

        document.getElementById('bluetooth-time').textContent =
            bluetooth.recorded_at ?? '--';

        // Update the fan status and both background colors.
        const fanOn = data.fan_status === 'ON';

        const fanStatus = document.getElementById('fan-status');
        const fanBox = document.getElementById('fan-box');
        const fanBody = document.getElementById('fan-body');

        fanStatus.textContent = fanOn ? 'ON' : 'OFF';

        fanStatus.className =
            'badge bg-light ' + (fanOn ? 'text-success' : 'text-danger');

        fanBox.style.setProperty(
            'background-color',
            fanOn ? '#198754' : '#842029',
            'important'
        );

        fanBox.style.setProperty(
            'border-color',
            fanOn ? '#198754' : '#842029',
            'important'
        );

        fanBody.style.setProperty(
            'background-color',
            fanOn ? '#105736' : '#842029',
            'important'
        );

        // Update temperature warnings.
        const alerts = document.getElementById('alerts');
        alerts.replaceChildren();

        if (!data.alerts.length) {
            const message = document.createElement('p');
            message.className = 'text-success text-center';
            message.textContent = 'No current temperature warnings.';
            alerts.appendChild(message);

        } else {
            for (const alert of data.alerts) {
                const message = document.createElement('div');
                message.className = 'alert alert-danger';
                message.textContent = alert.message;
                alerts.appendChild(message);
            }
        }

        // Update the timestamp without reloading the page.
        document.getElementById('updated-at').textContent =
            new Date().toLocaleString();

    } catch (error) {
        console.error('Dashboard refresh failed:', error);
    }
}

refreshFridgeDashboard();
setInterval(refreshFridgeDashboard, 3000);
</script>

<?php
ViewHelper::loadJsScripts();
ViewHelper::loadFooter();
?>
