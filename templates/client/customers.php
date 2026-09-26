<?php
/** @var array<string,mixed> $client */
/** @var string $q */
/** @var array{rows:array,total:int,pages:int,page:int} $list */
/** @var float $total */
/** @var array<string,int> $duplicates */
/** @var \App\Money $money */
/** @var callable $e */
$home = $basePath . '/' . $client['slug'];
$pageUrl = static fn (int $p): string => $home . '/customers?' . http_build_query(array_filter(['q' => $q, 'page' => $p > 1 ? $p : null]));
?>
<div class="stats">
    <div class="stat hero">
        <div class="k">Total balance held</div>
        <div class="v"><?= $e($money->format($total)) ?></div>
    </div>
    <div class="stat">
        <div class="k"><?= $q === '' ? 'Active customers' : 'Matching customers' ?></div>
        <div class="v"><?= number_format($list['total'], 0, ',', '.') ?></div>
    </div>
</div>

<div class="toolbar">
    <h1>Customers</h1>
    <a class="btn" href="<?= $e($home) ?>/qr">QR codes</a>
</div>

<form class="filterbar" method="get" action="<?= $e($home) ?>/customers">
    <input type="search" name="q" value="<?= $e($q) ?>" placeholder="Search name, code or phone…"
           data-filter=".js-cust" data-noun="match" autocomplete="off">
    <button class="btn" type="submit">Search all</button>
    <?php if ($q !== ''): ?><a class="btn" href="<?= $e($home) ?>/customers">Clear</a><?php endif; ?>
    <span class="count"></span>
</form>
<?php if ($list['pages'] > 1): ?>
    <div class="hint" style="margin:-8px 0 12px;">Typing filters this page instantly; press Enter or <em>Search all</em> to search every customer.</div>
<?php endif; ?>

<div class="card flush table-wrap">
    <table class="list">
        <thead>
            <tr>
                <th>Customer</th>
                <th>Code</th>
                <th class="hide-sm">Phone</th>
                <th class="num">Balance</th>
            </tr>
        </thead>
        <tbody>
        <?php if ($list['rows'] === []): ?>
            <tr><td colspan="4" class="muted" style="text-align:center;padding:30px;">No customers<?= $q !== '' ? ' match "' . $e($q) . '"' : '' ?>.</td></tr>
        <?php endif; ?>
        <?php foreach ($list['rows'] as $row): ?>
            <?php $bal = (float) $row['balance']; $code = trim((string) $row['code']); ?>
            <tr class="js-cust" style="cursor:pointer;" onclick="location.href='<?= $e($home) ?>/customers/<?= (int) $row['id'] ?>'">
                <td><a href="<?= $e($home) ?>/customers/<?= (int) $row['id'] ?>"><strong><?= $e($row['name']) ?></strong></a></td>
                <td>
                    <?= $code !== '' ? '<code>' . $e($code) . '</code>' : '<span class="muted">—</span>' ?>
                    <?php if ($code !== '' && isset($duplicates[$code])): ?>
                        <span class="pill warn" title="<?= (int) $duplicates[$code] ?> active customers share this code">shared</span>
                    <?php endif; ?>
                </td>
                <td class="hide-sm muted"><?= $e($row['phone1']) ?: '—' ?></td>
                <td class="num"><strong class="<?= $bal < 0 ? 'neg' : '' ?>"><?= $e($money->format($bal)) ?></strong></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php if ($list['pages'] > 1): ?>
    <div class="pager">
        <?php if ($list['page'] > 1): ?><a class="btn btn-sm" href="<?= $e($pageUrl($list['page'] - 1)) ?>">← Prev</a><?php endif; ?>
        <span class="muted">Page <?= $list['page'] ?> of <?= $list['pages'] ?></span>
        <?php if ($list['page'] < $list['pages']): ?><a class="btn btn-sm" href="<?= $e($pageUrl($list['page'] + 1)) ?>">Next →</a><?php endif; ?>
    </div>
<?php endif; ?>
