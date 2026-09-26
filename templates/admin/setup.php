<?php
/** @var array<string,string> $errors */
/** @var callable $e */
?>
<div class="login-wrap">
    <div class="login-head">
        <div class="brand" style="font-size:22px;justify-content:center;"><?= $brand() ?></div>
        <div class="muted" style="font-size:13px;margin-top:4px;">First-time setup — create the super admin</div>
    </div>
    <div class="card">
        <form method="post" action="<?= $basePath ?>/admin/setup">
            <label>Username</label>
            <input type="text" name="username" value="<?= $e($username ?? '') ?>" autofocus required autocomplete="username">
            <?php if (isset($errors['username'])): ?><div class="error"><?= $e($errors['username']) ?></div><?php endif; ?>
            <label>Password</label>
            <input type="password" name="password" required autocomplete="new-password">
            <?php if (isset($errors['password'])): ?><div class="error"><?= $e($errors['password']) ?></div><?php endif; ?>
            <label>Repeat password</label>
            <input type="password" name="password2" required autocomplete="new-password">
            <?php if (isset($errors['password2'])): ?><div class="error"><?= $e($errors['password2']) ?></div><?php endif; ?>
            <div class="actions">
                <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;">Create super admin</button>
            </div>
        </form>
    </div>
    <p class="muted" style="text-align:center;font-size:12px;">This page closes once a super admin exists.</p>
</div>
