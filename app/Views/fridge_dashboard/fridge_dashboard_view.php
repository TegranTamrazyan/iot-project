<?php
use App\Helpers\ViewHelper;

ViewHelper::loadHeader('Refrigerator Monitor');
ViewHelper::loadJsScripts();
?>

<div class="container mt-4">
    <h1>Refrigerator Monitor</h1>

    <h2>Sensor Readings</h2>

    <?php foreach (['Frig1', 'Frig2'] as $fridge): ?>
        <div class="card p-3 mb-3">
            <h3><?= $fridge ?></h3>

            <p>Temperature: <span id="<?= $fridge ?>-temp">--</span> °C</p>
            <p>Humidity: <span id="<?= $fridge ?>-humidity">--</span> %</p>
            <p>Threshold: <?= $thresholds[$fridge] ?? 5 ?> °C</p>
            <p id="<?= $fridge ?>-status">Waiting for data...</p>

            <form method="post" action="<?= BASE_URL ?>fridge-dashboard/threshold">
                <input type="hidden" name="fridge" value="<?= $fridge ?>">
                <input type="number" name="threshold" step="0.1"
                       value="<?= $thresholds[$fridge] ?? 5 ?>" required>
                <button type="submit">Save Threshold</button>
            </form>
        </div>
    <?php endforeach; ?>

    <h2>Fan Control</h2>

    <form method="post" action="<?= BASE_URL ?>fridge-dashboard/fan">
        <button name="command" value="ON">Turn Fan ON</button>
        <button name="command" value="OFF">Turn Fan OFF</button>
    </form>

    <p>Fan Status: <span id="fan-status">--</span></p>
</div>

<script>
    async function updateDashboard() {
        const response = await fetch('<?= BASE_URL ?>fridge-dashboard/data');
        const data = await response.json();

        for (const fridge of ['Frig1', 'Frig2']) {
            const item = data.fridges[fridge];

            document.getElementById(fridge + '-temp').textContent =
                item.temperature ?? '--';

            document.getElementById(fridge + '-humidity').textContent =
                item.humidity ?? '--';

            document.getElementById(fridge + '-status').textContent =
                item.temperature === null ? 'No data' :
                item.temperature > item.threshold ? 'WARNING: Too warm!' : 'Temperature OK';
        }

        document.getElementById('fan-status').textContent = data.fan_status;
    }

    updateDashboard();
    setInterval(updateDashboard, 3000);
</script>

<?php ViewHelper::loadFooter(); ?>
