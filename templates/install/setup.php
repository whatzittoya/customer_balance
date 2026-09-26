<?php
/** @var array<string,string> $values */
/** @var array<string,string> $errors */
/** @var callable $e */
$err = static function (string $k) use ($errors, $e): void {
    if (isset($errors[$k])) {
        echo '<div class="error">' . $e($errors[$k]) . '</div>';
    }
};
?>
<div class="login-wrap" style="max-width:460px;">
    <div class="login-head">
        <div class="brand" style="font-size:22px;justify-content:center;"><?= $brand() ?></div>
        <div class="muted" style="font-size:13px;margin-top:4px;">First-time setup — connect the database</div>
    </div>
    <div class="card">
        <?php if (isset($errors['form'])): ?>
            <div class="flash err" style="white-space:pre-wrap;word-break:break-word;"><?= $e($errors['form']) ?></div>
        <?php endif; ?>
        <form method="post" action="<?= $basePath ?>/setup" autocomplete="off">
            <div class="row">
                <div style="flex:3;">
                    <label for="host">MySQL host</label>
                    <input id="host" type="text" name="host" value="<?= $e($values['host']) ?>" required>
                    <?php $err('host'); ?>
                </div>
                <div style="flex:1;min-width:90px;">
                    <label for="port">Port</label>
                    <input id="port" type="text" name="port" value="<?= $e($values['port']) ?>" required inputmode="numeric">
                    <?php $err('port'); ?>
                </div>
            </div>
            <label for="user">MySQL user</label>
            <input id="user" type="text" name="user" value="<?= $e($values['user']) ?>" required autofocus autocomplete="off">
            <?php $err('user'); ?>
            <label for="pass">MySQL password</label>
            <input id="pass" type="password" name="pass" autocomplete="new-password">
            <div class="hint">Leave empty if the user has no password (typical for local XAMPP).</div>
            <label for="parent_name">Main database</label>
            <input id="parent_name" type="text" name="parent_name" value="<?= $e($values['parent_name']) ?>" required>
            <div class="hint">Created if it doesn't exist and the user is allowed to; on cPanel, create it in MySQL Databases first and add this user to it.</div>
            <?php $err('parent_name'); ?>
            <div class="actions">
                <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;">Connect &amp; create tables</button>
            </div>
        </form>
    </div>
    <p class="muted" style="text-align:center;font-size:12px;">Saved to <code>config/local.php</code>. Next you'll create the super admin. This page closes once the tables exist.</p>
</div>
