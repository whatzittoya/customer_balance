<?php
/** @var callable $e */
?>
<div class="login-wrap">
    <div class="login-head">
        <div class="brand" style="font-size:22px;justify-content:center;"><?= $brand() ?></div>
        <div class="muted" style="font-size:13px;margin-top:4px;">Super admin sign in</div>
    </div>
    <div class="card">
        <?php if (!empty($error)): ?>
            <div class="flash err"><?= $e($error) ?></div>
        <?php endif; ?>
        <form method="post" action="<?= $basePath ?>/admin/login">
            <label>Username</label>
            <input type="text" name="username" value="<?= $e($username ?? '') ?>" autofocus required autocomplete="username">
            <label>Password</label>
            <input type="password" name="password" required autocomplete="current-password">
            <div class="actions">
                <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;">Sign in</button>
            </div>
        </form>
    </div>
    <p class="muted" style="text-align:center;font-size:12px;">Client staff sign in at their own address, e.g. <code>/your-client</code>.</p>
</div>
