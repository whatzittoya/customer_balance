<?php
/** @var string $content */
/** @var string $basePath */
/** @var \App\SuperAdminAuth $saAuth */
/** @var callable $e */
$sa = $saAuth->user();
$path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
$onClients = str_starts_with(substr($path, strlen($basePath)), '/admin');
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title><?= $e(($title ?? '') !== '' ? $title . ' · ' . $appName : $appName) ?></title>
    <?php require __DIR__ . '/../_styles.php'; ?>
</head>
<body>
<?php if ($sa): ?>
    <header>
        <div class="bar">
            <a class="brand" href="<?= $basePath ?>/admin"><?= $brand() ?></a>
            <nav class="main">
                <a class="navlink<?= $onClients ? ' active' : '' ?>" href="<?= $basePath ?>/admin">Clients</a>
            </nav>
            <div class="userbox">
                <span><?= $e($sa['username']) ?></span>
                <span class="role admin">super admin</span>
                <a href="<?= $basePath ?>/admin/logout">Logout</a>
            </div>
        </div>
    </header>
<?php endif; ?>
<main>
    <?php $flash = \App\Flash::take(); ?>
    <?php if ($flash !== null): ?>
        <div class="flash<?= $flash['type'] === 'err' ? ' err' : '' ?>"><?= $e($flash['text']) ?></div>
    <?php endif; ?>
    <?= $content ?>
</main>
<?php require __DIR__ . '/../_scripts.php'; ?>
</body>
</html>
