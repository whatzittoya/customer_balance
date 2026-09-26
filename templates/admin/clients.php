<?php
/** @var array<int,array<string,mixed>> $clients */
/** @var array<int,string> $missing */
/** @var callable $e */
/** @var callable $logoUrl */
?>
<?php if ($missing !== []): ?>
    <div class="note">PHP extension missing on this server: <?= $e(implode(', ', $missing)) ?>. Enable it in cPanel → Select PHP Version → Extensions.</div>
<?php endif; ?>

<div class="toolbar">
    <h1>Clients <span class="muted" style="font-size:14px;font-weight:400;">(<?= count($clients) ?>)</span></h1>
    <a class="btn btn-primary" href="<?= $basePath ?>/admin/clients/new">+ New client</a>
</div>

<?php if ($clients === []): ?>
    <div class="card" style="text-align:center;padding:40px 18px;">
        <p style="margin:0 0 14px;">No clients yet. Create one, pair it with its POS database, then give its staff access.</p>
        <a class="btn btn-primary" href="<?= $basePath ?>/admin/clients/new">+ New client</a>
    </div>
<?php else: ?>
    <div class="filterbar">
        <input type="search" placeholder="Search clients…" data-filter=".js-client" autocomplete="off">
        <span class="count"></span>
    </div>
    <div class="card flush table-wrap">
        <table class="list">
            <thead>
                <tr>
                    <th style="width:56px;"></th>
                    <th>Client</th>
                    <th>Login URL</th>
                    <th>Database</th>
                    <th>Users</th>
                    <th>Status</th>
                    <th class="num">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($clients as $cl): ?>
                <?php $logo = $logoUrl($cl); $url = $basePath . '/' . $cl['slug']; ?>
                <tr class="js-client">
                    <td>
                        <?php if ($logo): ?>
                            <img class="logo-thumb" src="<?= $e($logo) ?>" alt="">
                        <?php else: ?>
                            <span class="logo-ph"><?= $e(mb_strtoupper(mb_substr((string) $cl['name'], 0, 1))) ?></span>
                        <?php endif; ?>
                    </td>
                    <td><strong><?= $e($cl['name']) ?></strong></td>
                    <td><a href="<?= $e($url) ?>/login" target="_blank" rel="noopener"><?= $e($url) ?></a></td>
                    <td><code><?= $e($cl['db_name']) ?></code></td>
                    <td><?= (int) $cl['user_count'] ?></td>
                    <td>
                        <?php if ((int) $cl['active'] === 1): ?>
                            <span class="pill ok">Active</span>
                        <?php else: ?>
                            <span class="pill off">Disabled</span>
                        <?php endif; ?>
                    </td>
                    <td class="num">
                        <a class="btn btn-sm" href="<?= $basePath ?>/admin/clients/<?= (int) $cl['id'] ?>/employees">Staff</a>
                        <a class="btn btn-sm" href="<?= $basePath ?>/admin/clients/<?= (int) $cl['id'] ?>/edit">Edit</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
