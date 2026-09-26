<?php
/** @var array<string,mixed> $client */
/** @var array<int,array<string,mixed>> $customers */
/** @var array<string,int> $duplicates */
/** @var int $max */
/** @var \App\Money $money */
/** @var callable $e */
$home = $basePath . '/' . $client['slug'];
$withCode = count(array_filter($customers, static fn ($c) => trim((string) $c['code']) !== ''));
?>
<div class="toolbar">
    <div>
        <h1>QR codes</h1>
        <div class="muted" style="font-size:13px;margin-top:4px;">
            Pick customers and download their QR codes as one zip (PNG, <?= $e($client['name']) ?> logo in the middle).
            <?= $withCode ?> of <?= count($customers) ?> active customers have a code.
        </div>
    </div>
</div>

<form method="post" action="<?= $e($home) ?>/qr/zip" id="bulkForm">
    <div class="filterbar">
        <input type="search" placeholder="Filter by name or code…" data-filter=".js-qr" autocomplete="off">
        <button type="button" class="btn btn-sm" id="selVisible">Select visible</button>
        <button type="button" class="btn btn-sm" id="selNone">Clear</button>
        <span class="count"></span>
        <span style="flex:1;"></span>
        <strong id="selCount">0 selected</strong>
        <button type="submit" class="btn btn-primary" id="dlBtn" disabled>Download zip</button>
    </div>
    <div class="hint" style="margin:-6px 0 12px;">Up to <?= $max ?> per zip.</div>

    <div class="card flush table-wrap">
        <table class="list">
            <thead>
                <tr>
                    <th style="width:42px;"><input type="checkbox" id="selAll" title="Select all visible"></th>
                    <th>Customer</th>
                    <th>Code</th>
                    <th class="num">Balance</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($customers as $row): ?>
                <?php $code = trim((string) $row['code']); $has = $code !== ''; ?>
                <tr class="js-qr">
                    <td><input type="checkbox" name="ids[]" value="<?= (int) $row['id'] ?>" class="js-pick" <?= $has ? '' : 'disabled' ?>></td>
                    <td><a href="<?= $e($home) ?>/customers/<?= (int) $row['id'] ?>"><?= $e($row['name']) ?></a></td>
                    <td>
                        <?= $has ? '<code>' . $e($code) . '</code>' : '<span class="muted">no code</span>' ?>
                        <?php if ($has && isset($duplicates[$code])): ?><span class="pill warn">shared</span><?php endif; ?>
                    </td>
                    <td class="num muted"><?= $e($money->format((float) $row['balance'])) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</form>

<script>
(function () {
    var max = <?= (int) $max ?>;
    var picks = function () { return Array.prototype.slice.call(document.querySelectorAll('.js-pick:not([disabled])')); };
    var visible = function () { return picks().filter(function (c) { return !c.closest('tr').classList.contains('f-hide'); }); };
    var countEl = document.getElementById('selCount');
    var btn = document.getElementById('dlBtn');
    var all = document.getElementById('selAll');

    function refresh() {
        var n = picks().filter(function (c) { return c.checked; }).length;
        countEl.textContent = n + ' selected' + (n > max ? ' — max ' + max : '');
        countEl.style.color = n > max ? 'var(--danger)' : '';
        btn.disabled = n === 0 || n > max;
        var vis = visible();
        all.checked = vis.length > 0 && vis.every(function (c) { return c.checked; });
    }
    function setVisible(on) { visible().forEach(function (c) { c.checked = on; }); refresh(); }

    document.getElementById('selVisible').addEventListener('click', function () { setVisible(true); });
    document.getElementById('selNone').addEventListener('click', function () { picks().forEach(function (c) { c.checked = false; }); refresh(); });
    all.addEventListener('change', function () { setVisible(all.checked); });
    document.addEventListener('change', function (ev) { if (ev.target.classList && ev.target.classList.contains('js-pick')) refresh(); });
    document.querySelector('input[data-filter=".js-qr"]').addEventListener('filtered', refresh);
    // The zip streams back as a download, so the page never reloads — re-enable the button shortly after.
    document.getElementById('bulkForm').addEventListener('submit', function () {
        btn.disabled = true; btn.textContent = 'Preparing…';
        setTimeout(function () { btn.textContent = 'Download zip'; refresh(); }, 4000);
    });
    refresh();
})();
</script>
