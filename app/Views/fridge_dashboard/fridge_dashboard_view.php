<?php
use App\Helpers\ViewHelper;

ViewHelper::loadHeader('Refrigerator Monitor');
ViewHelper::loadJsScripts();
?>

<div class="container mt-4">
    <section class="store-hero p-4 p-md-5 mb-4 d-flex justify-content-between flex-wrap gap-3">
  <div><h1 class="fw-bold display-6 mb-0 text-center">Refrigerator Monitor</h1></div>
 </section>

    <h2 class="text-center mt-4 mb-3">Sensor Readings</h2>

    <!-- Fridge cards side by side via bootstrap rows and columns -->
    <div class="row g-3">
        <?php foreach (['Frig1', 'Frig2'] as $fridge): ?>
            <div class="col-md-6">
                <div class="card p-3 h-100 rounded p-4">
                    <h3><?= $fridge ?></h3>

                    <p>Temperature: <span id="<?= $fridge ?>-temp">--</span> °C</p>
                    <p>Humidity: <span id="<?= $fridge ?>-humidity">--</span> %</p>
                    <p>Threshold: <?= $thresholds[$fridge] ?? 5 ?> °C</p>
                    <p id="<?= $fridge ?>-status">Waiting for data...</p>

                    <form method="post" action="<?= BASE_URL ?>fridge-dashboard/threshold" class="mt-auto">
                        <input type="hidden" name="fridge" value="<?= $fridge ?>">
                        <input type="number" name="threshold" step="0.1"
                               value="<?= $thresholds[$fridge] ?? 5 ?>" required>
                        <button type="submit" class="btn btn-primary btn-sm">Save Threshold</button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <h2 class="text-center mt-4 mb-3">Fan Control</h2>

    <div class="row g-3 justify-content-center">
        <div class="col-md-6">
            <!-- card on and off stuff -->
            <!-- <div class="card p-3 text-white text-center" id="fan-card"
                 style="background-color: #dc3545; transition: background-color 0.3s ease;"> testing -->
                 <div class="card p-3 text-white text-center" id="fan-card"
                 style="background-color: #ffffff; transition: background-color 0.3s ease;">
                 <!-- ok white is better -->
                <p class="mb-2">Fan Status:</p>
                <h3>
                    <span id="fan-status" class="badge bg-light text-danger fs-3">OFF</span>
                </h3>

                <form method="post" action="<?= BASE_URL ?>fridge-dashboard/fan"
                      class="d-flex justify-content-center gap-2 mt-2">
                    <button class="btn btn-light text-success fw-bold" name="command" value="ON">Turn Fan ON</button>
                    <button class="btn btn-light text-danger fw-bold" name="command" value="OFF">Turn Fan OFF</button>
                </form>
            </div>
        </div>
    </div>
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
                Number(item.temperature) > Number(item.threshold) ? 'WARNING: Too warm!' : 'Temperature OK';
        }

        // CARD ON AND OFF LOGIC for colors - default status is red so it might twitch in color her and there ok i fixed it sorta
        const isOn = data.fan_status === 'ON';
        const fanStatusEl = document.getElementById('fan-status');
        const fanCardEl = document.getElementById('fan-card');

        fanStatusEl.textContent = isOn ? 'ON' : 'OFF';
        fanCardEl.style.backgroundColor = isOn ? '#65976e' : '#2f0a0e';
        fanStatusEl.className = 'badge bg-light fs-3 ' + (isOn ? 'text-success' : 'text-danger');
    }

    updateDashboard();
    setInterval(updateDashboard, 3000);
</script>

<?php ViewHelper::loadFooter(); ?>