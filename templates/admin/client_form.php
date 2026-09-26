<?php
/** @var array<string,mixed>|null $client */
/** @var array<string,mixed> $data */
/** @var array<string,string> $errors */
/** @var array<int,string> $databases */
/** @var callable $e */
/** @var callable $logoUrl */
$isEdit = $client !== null;
$action = $basePath . '/admin/clients' . ($isEdit ? '/' . (int) $client['id'] : '');
$logo = $isEdit ? $logoUrl($client) : null;
$err = static function (string $k) use ($errors, $e): void {
    if (isset($errors[$k])) {
        echo '<div class="error">' . $e($errors[$k]) . '</div>';
    }
};
?>
<div class="toolbar">
    <h1><?= $isEdit ? 'Edit client' : 'New client' ?></h1>
    <a class="btn" href="<?= $basePath ?>/admin">Back</a>
</div>

<div class="card" style="max-width:720px;">
    <form method="post" action="<?= $e($action) ?>" enctype="multipart/form-data">
        <div class="row">
            <div>
                <label for="name">Client name</label>
                <input id="name" type="text" name="name" value="<?= $e($data['name'] ?? '') ?>" required maxlength="120" autofocus>
                <?php $err('name'); ?>
            </div>
            <div>
                <label for="slug">URL slug</label>
                <input id="slug" type="text" name="slug" value="<?= $e($data['slug'] ?? '') ?>" required maxlength="64"
                       pattern="[a-z0-9](?:[a-z0-9\-]*[a-z0-9])?" <?= $isEdit ? '' : 'data-auto="1"' ?>>
                <div class="hint">Staff sign in at <code><?= $e($basePath) ?>/<span id="slugPreview"><?= $e(($data['slug'] ?? '') ?: 'slug') ?></span></code><?= $isEdit ? ' — changing it breaks printed QR codes.' : '' ?></div>
                <?php $err('slug'); ?>
            </div>
        </div>

        <label for="db_name">Client database</label>
        <select id="db_name" name="db_name" required>
            <option value="">— pick the POS database —</option>
            <?php foreach ($databases as $db): ?>
                <option value="<?= $e($db) ?>" <?= ($data['db_name'] ?? '') === $db ? 'selected' : '' ?>><?= $e($db) ?></option>
            <?php endforeach; ?>
        </select>
        <div class="hint">Same server and credentials as this app. It must have tbl_customers, tbl_employees and tbl_point_transactions.</div>
        <?php $err('db_name'); ?>

        <label for="logo">Logo</label>
        <?php if ($logo): ?>
            <div style="display:flex;align-items:center;gap:12px;margin-bottom:8px;">
                <img src="<?= $e($logo) ?>" alt="" style="height:64px;max-width:160px;object-fit:contain;border:1px solid var(--line);border-radius:8px;background:#fff;padding:4px;">
                <label class="check" style="margin:0;"><input type="checkbox" name="remove_logo" value="1"> Remove logo</label>
            </div>
        <?php endif; ?>
        <input id="logo" type="file" name="logo" accept="image/png,image/jpeg,image/gif,image/webp">
        <div class="hint">PNG, JPG, GIF or WebP, up to 1 MB. A square logo on a transparent or white background looks best in the middle of the QR codes.</div>
        <?php $err('logo'); ?>

        <label class="check" style="margin-top:16px;">
            <input type="checkbox" name="active" value="1" <?= !empty($data['active']) ? 'checked' : '' ?>>
            Active — when off, the client's login page and QR links show "not found"
        </label>

        <div class="actions">
            <button type="submit" class="btn btn-primary"><?= $isEdit ? 'Save changes' : 'Create client' ?></button>
            <a class="btn" href="<?= $basePath ?>/admin">Cancel</a>
        </div>
    </form>
</div>

<script>
(function () {
    var name = document.getElementById('name');
    var slug = document.getElementById('slug');
    var preview = document.getElementById('slugPreview');
    var auto = slug.hasAttribute('data-auto') && slug.value === '';
    function slugify(s) {
        return s.toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '')
            .replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '').slice(0, 64);
    }
    function show() { preview.textContent = slug.value || 'slug'; }
    name.addEventListener('input', function () { if (auto) { slug.value = slugify(name.value); show(); } });
    slug.addEventListener('input', function () { auto = false; show(); });
})();
</script>
