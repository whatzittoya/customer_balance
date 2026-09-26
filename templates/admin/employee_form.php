<?php
/** @var array<string,mixed> $client */
/** @var array<string,mixed>|null $employee */
/** @var array<string,mixed> $data */
/** @var array<string,string> $errors */
/** @var callable $e */
$isEdit = $employee !== null;
$cid = (int) $client['id'];
$action = $basePath . '/admin/clients/' . $cid . '/employees' . ($isEdit ? '/' . (int) $employee['id'] : '');
$hasPin = $isEdit && trim((string) ($employee['pin'] ?? '')) !== '';
$err = static function (string $k) use ($errors, $e): void {
    if (isset($errors[$k])) {
        echo '<div class="error">' . $e($errors[$k]) . '</div>';
    }
};
$access = (string) ($data['access'] ?? '');
?>
<div class="toolbar">
    <div>
        <h1><?= $isEdit ? 'Edit ' . $e($employee['name']) : 'New staff' ?></h1>
        <div class="muted" style="font-size:13px;margin-top:4px;"><?= $e($client['name']) ?> · saved to <code><?= $e($client['db_name']) ?>.tbl_employees</code></div>
    </div>
    <a class="btn" href="<?= $basePath ?>/admin/clients/<?= $cid ?>/employees">Back</a>
</div>

<div class="card" style="max-width:720px;">
    <form method="post" action="<?= $e($action) ?>" autocomplete="off">
        <div class="row">
            <div>
                <label>Name</label>
                <input type="text" name="name" value="<?= $e($data['name'] ?? '') ?>" required autofocus>
                <?php $err('name'); ?>
            </div>
            <div>
                <label>Code <span class="muted" style="font-weight:400;">(optional)</span></label>
                <input type="text" name="code" value="<?= $e($data['code'] ?? '') ?>">
                <?php $err('code'); ?>
            </div>
        </div>
        <div class="hint">Staff sign in with their name <em>or</em> code, plus PIN.</div>

        <div class="row">
            <div>
                <label>PIN</label>
                <input type="password" name="pin" value="" inputmode="numeric" autocomplete="new-password"
                       placeholder="<?= $hasPin ? 'Leave blank to keep current PIN' : '' ?>">
                <?php $err('pin'); ?>
            </div>
            <div>
                <label>Job title</label>
                <input type="text" name="jobTitle" value="<?= $e($data['jobTitle'] ?? '') ?>">
            </div>
        </div>

        <div class="row">
            <div>
                <label>Phone</label>
                <input type="text" name="phone" value="<?= $e($data['phone'] ?? '') ?>">
            </div>
            <div>
                <label>Email</label>
                <input type="email" name="email" value="<?= $e($data['email'] ?? '') ?>">
            </div>
        </div>

        <div class="row" style="margin-top:6px;">
            <div>
                <label class="check"><input type="checkbox" name="active" value="1" <?= !empty($data['active']) ? 'checked' : '' ?>> Active</label>
            </div>
            <div>
                <label class="check"><input type="checkbox" name="resigned" value="1" <?= !empty($data['resigned']) ? 'checked' : '' ?>> Resigned</label>
            </div>
        </div>

        <label>Balance app access</label>
        <select name="access">
            <option value="" <?= $access === '' ? 'selected' : '' ?>>No access</option>
            <option value="cashier" <?= $access === 'cashier' ? 'selected' : '' ?>>Cashier</option>
            <option value="admin" <?= $access === 'admin' ? 'selected' : '' ?>>Admin</option>
        </select>
        <div class="hint">Stored in the parent database. Only staff with access can sign in at <code><?= $e($basePath . '/' . $client['slug']) ?></code>.</div>

        <div class="actions">
            <button type="submit" class="btn btn-primary"><?= $isEdit ? 'Save changes' : 'Add staff' ?></button>
            <a class="btn" href="<?= $basePath ?>/admin/clients/<?= $cid ?>/employees">Cancel</a>
        </div>
    </form>
</div>
