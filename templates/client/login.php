<?php
/** @var array<string,mixed> $client */
/** @var callable $e */
/** @var callable $logoUrl */
$logo = $logoUrl($client);
?>
<div class="login-wrap">
    <div class="login-head">
        <?php if ($logo): ?><img src="<?= $e($logo) ?>" alt=""><?php endif; ?>
        <div class="brand" style="font-size:22px;justify-content:center;"><?= $brand($client['name']) ?></div>
        <div class="muted" style="font-size:13px;margin-top:4px;">Customer balance · staff sign in</div>
    </div>
    <div class="card">
        <?php if (!empty($error)): ?>
            <div class="flash err"><?= $e($error) ?></div>
        <?php endif; ?>
        <form method="post" action="<?= $e($basePath . '/' . $client['slug']) ?>/login">
            <label>Name or code</label>
            <input type="text" name="username" value="<?= $e($username ?? '') ?>" autofocus required autocomplete="username">
            <label>PIN</label>
            <input type="password" name="pin" required inputmode="numeric" autocomplete="current-password">
            <div class="actions">
                <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;">Sign in</button>
            </div>
        </form>
    </div>
    <p class="muted" style="text-align:center;font-size:12px;">Use your POS employee name/code and PIN.</p>
</div>
