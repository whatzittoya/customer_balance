<?php
/** @var string $content */
/** @var callable $e */
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
<main>
    <?php $flash = \App\Flash::take(); ?>
    <?php if ($flash !== null): ?>
        <div class="flash<?= $flash['type'] === 'err' ? ' err' : '' ?>"><?= $e($flash['text']) ?></div>
    <?php endif; ?>
    <?= $content ?>
</main>
</body>
</html>
