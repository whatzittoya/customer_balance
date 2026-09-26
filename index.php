<?php

declare(strict_types=1);

use App\ClientAuth;
use App\Database;
use App\Installer;
use App\Money;
use App\Parent\AdminRepository;
use App\Parent\ClientRepository;
use App\Parent\ClientUserRepository;
use App\QrService;
use App\SuperAdminAuth;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use Slim\Psr7\Response as SlimResponse;
use Slim\Views\PhpRenderer;

// `php -S … index.php` (local dev): let the built-in server hand out real files
// such as uploaded logos. Apache does this through .htaccess.
if (PHP_SAPI === 'cli-server') {
    $file = __DIR__ . parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    if (is_file($file) && !str_ends_with($file, '.php')) {
        return false;
    }
}

require __DIR__ . '/vendor/autoload.php';

session_start();

$config = require __DIR__ . '/config/config.php';
date_default_timezone_set((string) $config['timezone']);

/* -------------------------------------------------------------------------
 * Base path auto-detection — makes the app work at the web root or in a
 * subfolder (public_html/balance on cPanel, htdocs/customer-balance on XAMPP)
 * with no code change.
 * ---------------------------------------------------------------------- */
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
$basePath   = str_replace('\\', '/', dirname($scriptName));
// The built-in server sets SCRIPT_NAME to the request path for URLs that look
// like files (…/qr.png), so it can't be trusted there; it always serves at /.
if ($basePath === '/' || $basePath === '.' || PHP_SAPI === 'cli-server') {
    $basePath = '';
}

/* ------------------------------------------------------------------ Slim */
$app = AppFactory::create();
$app->setBasePath($basePath);
$app->addBodyParsingMiddleware();
$app->addRoutingMiddleware();
$errors = $app->addErrorMiddleware((bool) $config['displayErrorDetails'], true, true);

/* --------------------------------------------------------------- Helpers */
$esc = static fn ($v): string => htmlspecialchars((string) ($v ?? ''), ENT_QUOTES);

/**
 * Brand mark: the first word plain, everything after it in the accent colour.
 * Returns ready-to-echo HTML.
 */
$brand = static function (?string $name = null) use ($config, $esc): string {
    $parts = explode(' ', trim((string) ($name ?? $config['app_name'])), 2);

    return $esc($parts[0]) . (isset($parts[1]) ? ' <span>' . $esc($parts[1]) . '</span>' : '');
};

/* ------------------------------------------------- First-run setup gate
 * Until the parent tables exist there is nothing to serve: /setup takes the
 * MySQL credentials and creates them, everything else redirects there.
 * ------------------------------------------------------------------- */
$installer = new Installer($config, __DIR__);
if ($installer->needed()) {
    $installView = new PhpRenderer(__DIR__ . '/templates', [
        'basePath' => $basePath, 'appName' => (string) $config['app_name'], 'brand' => $brand, 'e' => $esc,
    ]);
    $installView->setLayout('install/layout.php');
    (require __DIR__ . '/src/routes/install.php')($app, [
        'installer' => $installer, 'view' => $installView, 'basePath' => $basePath,
    ]);
    $app->run();
    exit;
}
$app->get('/setup', static fn ($req, $res) => $res->withHeader('Location', $basePath . '/admin')->withStatus(302));

/* ---------------------------------------------------------- Dependencies */
$parentPdo  = Database::parent($config['db']);
$admins     = new AdminRepository($parentPdo);
$clients    = new ClientRepository($parentPdo, (string) $config['db']['parent_name']);
$grants     = new ClientUserRepository($parentPdo);
$saAuth     = new SuperAdminAuth($admins);
$clientAuth = new ClientAuth($grants);
$money      = new Money($config['money']);
$qr         = new QrService((string) $config['app_secret']);

/** Public URL of a client's logo, or null. */
$logoUrl = static function (?array $client) use ($basePath): ?string {
    $path = (string) ($client['logo_path'] ?? '');

    return $path !== '' && is_file(__DIR__ . '/' . $path) ? $basePath . '/' . $path : null;
};

$shared = [
    'basePath' => $basePath,
    'appName'  => (string) $config['app_name'],
    'brand'    => $brand,
    'money'    => $money,
    'e'        => $esc,
    'logoUrl'  => $logoUrl,
];

// One renderer per look: super admin, client staff, and the bare public pages.
$adminView = new PhpRenderer(__DIR__ . '/templates', $shared + ['saAuth' => $saAuth]);
$adminView->setLayout('admin/layout.php');
$clientView = new PhpRenderer(__DIR__ . '/templates', $shared);
$clientView->setLayout('client/layout.php');
$publicView = new PhpRenderer(__DIR__ . '/templates', $shared);
$publicView->setLayout('public/layout.php');

/** The friendly 404 used for unknown slugs, bad QR links and missing rows. */
$notFound = static function (?Request $request = null) use ($publicView) {
    return $publicView->render((new SlimResponse())->withStatus(404), 'public/not_found.php', [
        'title' => 'Page not found',
    ]);
};
$errors->setErrorHandler(\Slim\Exception\HttpNotFoundException::class, static fn () => $notFound());

$container = [
    'config'     => $config,
    'basePath'   => $basePath,
    'root'       => __DIR__,
    'admins'     => $admins,
    'clients'    => $clients,
    'grants'     => $grants,
    'saAuth'     => $saAuth,
    'clientAuth' => $clientAuth,
    'money'      => $money,
    'qr'         => $qr,
    'adminView'  => $adminView,
    'clientView' => $clientView,
    'publicView' => $publicView,
    'notFound'   => $notFound,
];

/* --------------------------------------------------------------- Routes  */
$app->get('/', static fn ($req, $res) => $res->withHeader('Location', $basePath . '/admin')->withStatus(302));

// Admin first: its static paths must win over the /{slug} catch-all.
(require __DIR__ . '/src/routes/admin.php')($app, $container);
(require __DIR__ . '/src/routes/client.php')($app, $container);

$app->run();
