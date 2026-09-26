<?php
/**
 * Application configuration.
 *
 * One install serves many clients. The parent database (customer_balance_db)
 * holds super admins, the client registry and login grants — see
 * sql/parent_schema.sql. Every client has its own Quinos POS database on the
 * same MySQL server, reached with the same credentials; which one is stored
 * per client in `clients.db_name`.
 *
 * Values default to a local MySQL but can be overridden with environment
 * variables or config/local.php.
 */

declare(strict_types=1);

$config = [
    // Shown in the super admin header, the admin login page and page titles.
    // Everything before the first space is plain, the rest takes the accent
    // colour — "Quinos Balance" renders as Quinos + Balance.
    'app_name' => getenv('APP_NAME') ?: 'Quinos Balance',

    'db' => [
        'host'        => getenv('DB_HOST') ?: '127.0.0.1',
        'port'        => getenv('DB_PORT') ?: '3306',
        // The parent database. Client database names come from `clients`.
        'parent_name' => getenv('DB_PARENT') ?: 'customer_balance_db',
        'user'        => getenv('DB_USER') ?: 'root',
        // No password is committed. Set DB_PASS in the environment or put it
        // in config/local.php, which is gitignored.
        'pass'        => getenv('DB_PASS') !== false ? getenv('DB_PASS') : '',
        // The POS tables are latin1 but hold ASCII data; utf8mb4 client is fine.
        'charset'     => 'utf8mb4',
    ],

    // Signs customer QR links. Required — set it in config/local.php. Changing
    // it invalidates every QR code already handed out.
    'app_secret' => getenv('APP_SECRET') ?: '',

    // Public address QR codes point at, e.g. https://balance.example.com or
    // https://example.com/balance (no trailing slash). Empty = work it out from
    // the request, which is right unless a proxy hides the real scheme/host.
    'base_url' => rtrim((string) (getenv('APP_URL') ?: ''), '/'),

    // Time zone for "now" (e.g. the "as of" time on the customer page). POS
    // dates are stored as local time, so this should match the outlets.
    'timezone' => getenv('APP_TIMEZONE') ?: 'Asia/Makassar',

    // Money formatting for balances and history.
    'money' => [
        'symbol'    => getenv('MONEY_SYMBOL') ?: 'Rp',
        'decimals'  => (int) (getenv('MONEY_DECIMALS') ?: 0),
        'dec_point' => getenv('MONEY_DEC_POINT') ?: ',',
        'thousands' => getenv('MONEY_THOUSANDS') ?: '.',
    ],

    // Show detailed errors. Set to false in production.
    'displayErrorDetails' => true,
];

/*
 * Local overrides — config/local.php is gitignored, so this is where a machine's
 * real DB password and app secret live without ever reaching the repo. Return
 * only the keys you want to change, e.g.:
 *
 *     <?php return ['db' => ['pass' => 'your-password'], 'app_secret' => '…'];
 */
$local = __DIR__ . '/local.php';
if (is_file($local)) {
    $config = array_replace_recursive($config, (array) require $local);
}

return $config;
