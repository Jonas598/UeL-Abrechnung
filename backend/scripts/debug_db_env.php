<?php

require __DIR__ . '/../vendor/autoload.php';

// Load .env like Laravel would in local dev
\Dotenv\Dotenv::createImmutable(dirname(__DIR__))->safeLoad();

$keys = [
    'DB_CONNECTION',
    'DB_URL',
    'DB_HOST',
    'DB_PORT',
    'DB_DATABASE',
    'DB_USERNAME',
    'DB_PASSWORD',
    'DB_SSLMODE',
];

foreach ($keys as $k) {
    $v = getenv($k);
    if ($v === false) {
        $v = $_ENV[$k] ?? null;
    }

    if ($k === 'DB_PASSWORD' && is_string($v)) {
        // never print full password
        $v = substr($v, 0, 12) . '… (len=' . strlen($v) . ')';
    }

    echo $k . '=' . ($v === null ? 'NULL' : $v) . PHP_EOL;
}

// Also show parsed components of DB_URL (if present)
$dbUrl = getenv('DB_URL') ?: ($_ENV['DB_URL'] ?? null);
if ($dbUrl) {
    echo PHP_EOL . "Parsed DB_URL:" . PHP_EOL;
    $p = parse_url($dbUrl);
    var_export($p);
    echo PHP_EOL;
}

