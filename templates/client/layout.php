<?php
/** @var string $content */
/** @var string $basePath */
/** @var array $client */
/** @var array|null $user */
/** @var callable $e */
/** @var callable $logoUrl */
$user = $user ?? null;
$home = $basePath . '/' . $client['slug'];
$path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
$nav = static function (string $href, string $label) use ($home, $path, $e): void {
    $active = str_starts_with($path, $home . $href) ? ' active' : '';
    echo '<a class="navlink' . $active . '" href="' . $e($home . $href) . '">' . $e($label) . '</a>';
};
$logo = $logoUrl($client);
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title><?= $e(($title ?? '') !== '' ? $title . ' · ' . $client['name'] : $client['name']) ?></title>
    <?php if ($logo): ?><link rel="icon" href="<?= $e($logo) ?>"><?php endif; ?>
    <?php require __DIR__ . '/../_styles.php'; ?>
</head>
<body>
<?php if ($user): ?>
    <header>
        <div class="bar">
            <a class="brand" href="<?= $e($home) ?>/customers">
                <?php if ($logo): ?><img src="<?= $e($logo) ?>" alt=""><?php endif; ?>
                <?= $brand($client['name']) ?>
            </a>
            <nav class="main">
                <?php $nav('/customers', 'Customers'); ?>
                <?php $nav('/qr', 'QR codes'); ?>
            </nav>
            <div class="userbox">
                <span><?= $e($user['name']) ?></span>
                <span class="role <?= $user['role'] === 'admin' ? 'admin' : '' ?>"><?= $e($user['role']) ?></span>
                <a href="<?= $e($home) ?>/logout">Logout</a>
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
