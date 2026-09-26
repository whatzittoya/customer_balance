<?php
/** @var array<string,mixed> $client */
/** @var array<int,array<string,mixed>> $employees */
/** @var array<int,string> $roles employee_id => role */
/** @var callable $e */
$cid = (int) $client['id'];
$granted = count($roles);
?>
<div class="toolbar">
    <div>
        <h1><?= $e($client['name']) ?> — Staff <span class="muted" style="font-size:14px;font-weight:400;">(<?= count($employees) ?>)</span></h1>
        <div class="muted" style="font-size:13px;margin-top:4px;">
            From <code><?= $e($client['db_name']) ?>.tbl_employees</code> · <?= $granted ?> with access ·
            sign in at <a href="<?= $e($basePath . '/' . $client['slug']) ?>/login" target="_blank" rel="noopener"><?= $e($basePath . '/' . $client['slug']) ?></a>
        </div>
    </div>
    <div style="display:flex;gap:8px;">
        <a class="btn" href="<?= $basePath ?>/admin">Back</a>
        <a class="btn btn-primary" href="<?= $basePath ?>/admin/clients/<?= $cid ?>/employees/new">+ New staff</a>
    </div>
</div>

<div class="filterbar">
    <input type="search" placeholder="Search staff by name, code, job title or access…" data-filter=".js-staff" autocomplete="off">
    <label class="check" style="margin:0;"><input type="checkbox" id="onlyAccess"> Only with access</label>
    <span class="count"></span>
</div>

<div class="card flush table-wrap">
    <table class="list">
        <thead>
            <tr>
                <th>Name</th>
                <th>Code</th>
                <th class="hide-sm">Job title</th>
                <th>PIN</th>
                <th>Status</th>
                <th>App access</th>
                <th class="num"></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($employees as $emp): ?>
            <?php
            $id      = (int) $emp['id'];
            $role    = $roles[$id] ?? '';
            $hasPin  = trim((string) ($emp['pin'] ?? '')) !== '';
            $working = (int) $emp['active'] === 1 && (int) $emp['resigned'] === 0;
            ?>
            <tr class="js-staff" data-access="<?= $role !== '' ? '1' : '0' ?>">
                <td><strong><?= $e($emp['name']) ?></strong></td>
                <td class="muted"><?= $e($emp['code']) ?: '—' ?></td>
                <td class="hide-sm"><?= $e($emp['jobTitle']) ?: '<span class="muted">—</span>' ?></td>
                <td><?= $hasPin ? '<span class="pill ok">Set</span>' : '<span class="pill off">None</span>' ?></td>
                <td>
                    <?php if ($working): ?>
                        <span class="pill ok">Working</span>
                    <?php else: ?>
                        <span class="pill off"><?= (int) $emp['resigned'] === 1 ? 'Resigned' : 'Inactive' ?></span>
                    <?php endif; ?>
                </td>
                <td>
                    <form method="post" action="<?= $basePath ?>/admin/clients/<?= $cid ?>/employees/<?= $id ?>/access" class="inline">
                        <select name="access" data-autosubmit style="width:auto;padding:5px 8px;font-size:13px;<?= $role !== '' ? 'border-color:var(--accent);' : '' ?>">
                            <option value="" <?= $role === '' ? 'selected' : '' ?>>No access</option>
                            <option value="cashier" <?= $role === 'cashier' ? 'selected' : '' ?>>Cashier</option>
                            <option value="admin" <?= $role === 'admin' ? 'selected' : '' ?>>Admin</option>
                        </select>
                        <noscript><button class="btn btn-sm">Set</button></noscript>
                    </form>
                    <?php if ($role !== '' && (!$working || !$hasPin)): ?>
                        <div class="hint" style="color:var(--warn-ink);">Can't sign in: <?= !$hasPin ? 'no PIN' : 'not working' ?></div>
                    <?php endif; ?>
                </td>
                <td class="num"><a class="btn btn-sm" href="<?= $basePath ?>/admin/clients/<?= $cid ?>/employees/<?= $id ?>/edit">Edit</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script>
(function () {
    var only = document.getElementById('onlyAccess');
    var box = document.querySelector('input[data-filter=".js-staff"]');
    function apply() {
        document.querySelectorAll('.js-staff').forEach(function (r) {
            r.style.display = only.checked && r.getAttribute('data-access') !== '1' ? 'none' : '';
        });
    }
    only.addEventListener('change', apply);
    box.addEventListener('filtered', apply);
})();
</script>
