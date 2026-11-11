<?php

declare(strict_types=1);

// Copy this file to config/env.php and fill in your MariaDB credentials.
// Keep config/env.php private; it is ignored by Git.
// Configure outbound receipt email through environment variables:
// MAIL_HOST, MAIL_PORT, MAIL_ENCRYPTION (tls or ssl), MAIL_USERNAME, MAIL_PASSWORD.
return function (array $settings): array {
    $settings['db']['host'] = '127.0.0.1';
    $settings['db']['port'] = '3306';
    $settings['db']['database'] = 'iot';
    $settings['db']['username'] = 'YOUR_DB_USERNAME';
    $settings['db']['password'] = 'YOUR_DB_PASSWORD';

    $_ENV['MAIL_HOST'] = 'smtp.gmail.com';
    $_ENV['MAIL_PORT'] = '587';
    $_ENV['MAIL_ENCRYPTION'] = 'tls';
    $_ENV['MAIL_USERNAME'] = 'YOUR-GMAIL-ADDRESS@gmail.com';
    $_ENV['MAIL_PASSWORD'] = 'YOUR-GMAIL-APP-PASSWORD';
    return $settings;
};
