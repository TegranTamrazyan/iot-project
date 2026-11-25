<?php

declare(strict_types=1);

namespace App\Helpers;

use RuntimeException;

class RfidScannerState
{
    private static function path(): string
    {
        return sys_get_temp_dir() . '/iot-rfid-' . md5(dirname(__DIR__, 2)) . '.json';
    }

    private static function access(callable $callback): mixed
    {
        $file = fopen(self::path(), 'c+');

        if (!$file) {
            throw new RuntimeException('Cannot open RFID state file.');
        }

        try {
            if (!flock($file, LOCK_EX)) {
                throw new RuntimeException('Cannot lock RFID state file.');
            }

            $state = json_decode(stream_get_contents($file), true);

            if (!is_array($state)) {
                $state = [
                    'mode' => 'idle',
                    'batch' => null,
                    'epcs' => [],
                    'updated' => 0
                ];
            }

            $result = $callback($state);

            rewind($file);
            ftruncate($file, 0);
            fwrite($file, json_encode($state, JSON_THROW_ON_ERROR));
            fflush($file);

            return $result;
        } finally {
            flock($file, LOCK_UN);
            fclose($file);
        }
    }

    public static function activate(string $mode, ?string $batch = null): void
    {
        if (!in_array($mode, ['checkout', 'product'], true)) {
            throw new RuntimeException('Invalid scanner mode.');
        }

        self::access(function (&$state) use ($mode, $batch) {
            if ($mode === 'product' && $state['batch'] !== $batch) {
                $state['batch'] = $batch;
                $state['epcs'] = [];
            }

            $state['mode'] = $mode;
            $state['updated'] = time();
        });
    }

    public static function current(): array
    {
        return self::access(function (&$state) {
            if (time() - $state['updated'] > 8) {
                return ['mode' => 'idle'];
            }

            return $state;
        });
    }

    public static function addEpcs(array $epcs): int
    {
        return self::access(function (&$state) use ($epcs) {
            if (
                $state['mode'] !== 'product' ||
                time() - $state['updated'] > 8
            ) {
                return 0;
            }

            $added = 0;

            foreach ($epcs as $epc) {
                $epc = strtoupper(trim($epc));

                if (!preg_match('/^[0-9A-F]{1,24}$/D', $epc)) {
                    continue;
                }

                if (!in_array($epc, $state['epcs'], true)) {
                    if (count($state['epcs']) >= 1000) {
                        break;
                    }

                    $state['epcs'][] = $epc;
                    $added++;
                }
            }

            return $added;
        });
    }

    public static function pending(string $batch): array
    {
        return self::access(function (&$state) use ($batch) {
            return $state['batch'] === $batch ? $state['epcs'] : [];
        });
    }

    public static function finish(string $batch): void
    {
        self::access(function (&$state) use ($batch) {
            if ($state['batch'] === $batch) {
                $state['batch'] = null;
                $state['epcs'] = [];

                if ($state['mode'] === 'product') {
                    $state['mode'] = 'idle';
                    $state['updated'] = 0;
                }
            }
        });
    }
    
    public static function pauseProduct(int $productId): void
    {
        self::access(function (&$state) use ($productId) {
            if (
                ($state['mode'] ?? '') === 'product' &&
                (int) ($state['batch'] ?? 0) === $productId
            ) {
                $state['mode'] = 'idle';
                $state['updated'] = 0;
            }
        });
    }
}
