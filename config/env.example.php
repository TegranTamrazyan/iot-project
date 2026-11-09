<?php

declare(strict_types=1);

// Copy this file to config/env.php and fill in your MariaDB credentials.
// Keep config/env.php private; it is ignored by Git.
return function (array $settings): array {
    $settings['db']['host'] = '127.0.0.1';
    $settings['db']['port'] = '3306';
    $settings['db']['database'] = 'iot';
    $settings['db']['username'] = 'YOUR_DB_USERNAME';
    $settings['db']['password'] = 'YOUR_DB_PASSWORD';

    return $settings;
};
