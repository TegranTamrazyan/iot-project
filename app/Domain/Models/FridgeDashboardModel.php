<?php

declare(strict_types=1);

namespace App\Domain\Models;

use App\Helpers\Core\PDOService;

class FridgeDashboardModel extends BaseModel
{
    public function __construct(PDOService $db_service)
    {
        parent::__construct($db_service);
    }

    public function getLatestReadings(): array
    {
        return $this->selectAll("
            SELECT fridge, temperature, humidity, recorded_at
            FROM sensor_readings
            WHERE id IN (
                SELECT MAX(id)
                FROM sensor_readings
                GROUP BY fridge
            )
        ");
    }

    public function getThresholds(): array
    {
        $thresholds = ['Frig1' => 5.0, 'Frig2' => 5.0];

        foreach ($this->selectAll('SELECT fridge, max_temperature FROM thresholds') as $row) {
            $thresholds[$row['fridge']] = (float) $row['max_temperature'];
        }

        return $thresholds;
    }

    public function getFanStatus(): string
    {
        return $this->selectOne('SELECT status FROM fan_state WHERE id = 1')['status'] ?? 'OFF';
    }

    public function setFanStatus(string $status): void
    {
        $this->execute(
            'INSERT INTO fan_state (id, status) VALUES (1, ?)
             ON DUPLICATE KEY UPDATE status = ?',
            [$status, $status]
        );
    }

    public function updateThreshold(string $fridge, float $threshold): void
    {
        $this->execute(
            'INSERT INTO thresholds (fridge, max_temperature) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE max_temperature = ?',
            [$fridge, $threshold, $threshold]
        );
    }
}