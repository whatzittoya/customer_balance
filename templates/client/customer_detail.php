<?php
/** @var array<string,mixed> $client */
/** @var array<string,mixed> $customer */
/** @var array<int,array<string,mixed>> $history */
/** @var string|null $publicUrl */
/** @var int $sharedWith */
/** @var \App\Money $money */
/** @var callable $e */
$home = $basePath . '/' . $client['slug'];
$id = (int) $customer['id'];
$bal = (float) $customer['balance'];
$code = trim((string) $customer['code']);
?>
<div class="toolbar">
    <div>
        <h1><?= $e($customer['name']) ?></h1>
        <div class="muted" style="font-size:13px;margin-top:4px;">
            Code <?= $code !== '' ? '<code>' . $e($code) . '</code>' : '—' ?>
            <?php if ($customer['phone1']): ?> · <?= $e($customer['phone1']) ?><?php endif; ?>
            <?php if ((int) $customer['active'] !== 1): ?> · <span class="pill off">Inactive</span><?php endif; ?>
        </div>
    </div>
    <a class="btn" href="<?= $e($home) ?>/customers">← Customers</a>
</div>

<?php if ($sharedWith > 1): ?>
    <div class="note"><?= $sharedWith ?> active customers share the code <code><?= $e($code) ?></code>. Their history below is combined, and the QR code opens the oldest of them. Give each customer a unique code in the POS.</div>
<?php endif; ?>

<div class="row" style="align-items:flex-start;">
    <div style="flex:2;min-width:280px;">
        <div class="stats" style="grid-template-columns:1fr 1fr;">
            <div class="stat hero">
                <div class="k">Current balance</div>
                <div class="v" style="font-size:28px;"><?= $e($money->format($bal)) ?></div>
            </div>
            <div class="stat">
                <div class="k">Transactions</div>
                <div class="v"><?= count($history) ?></div>
            </div>
        </div>

        <div class="card flush">
            <div style="padding:14px 16px 0;"><h2>History</h2></div>
            <div class="table-wrap">
                <table class="list">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Type</th>
                            <th class="hide-sm">Payment</th>
                            <th class="hide-sm">Outlet</th>
                            <th class="hide-sm">By</th>
                            <th class="num">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if ($history === []): ?>
                        <tr><td colspan="6" class="muted" style="text-align:center;padding:26px;">No transactions yet.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($history as $tx): ?>
                        <?php $amt = (float) $tx['amount']; ?>
                        <tr>
                            <td style="white-space:nowrap;"><?= $e($tx['date'] ? date('d M Y H:i', strtotime((string) $tx['date'])) : '—') ?></td>
                            <td><span class="pill violet"><?= $e($tx['remark'] ?: '—') ?></span></td>
                            <td class="hide-sm muted"><?= $e(trim($tx['paymentMethod'] . ' ' . $tx['paymentRemark'])) ?: '—' ?></td>
                            <td class="hide-sm muted"><?= $e($tx['outlet']) ?: '—' ?></td>
                            <td class="hide-sm muted"><?= $e($tx['employeeName']) ?: '—' ?></td>
                            <td class="num"><strong class="<?= $amt < 0 ? 'neg' : 'pos' ?>"><?= $e($money->signed($amt)) ?></strong></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div style="flex:1;min-width:240px;max-width:340px;">
        <div class="card" style="text-align:center;">
            <h2>Customer QR code</h2>
            <?php if ($publicUrl !== null): ?>
                <img src="<?= $e($home) ?>/customers/<?= $id ?>/qr.png" alt="QR code for <?= $e($customer['name']) ?>"
                     style="width:100%;max-width:260px;border:1px solid var(--line);border-radius:10px;">
                <div class="actions" style="justify-content:center;margin-top:12px;">
                    <a class="btn btn-primary" href="<?= $e($home) ?>/customers/<?= $id ?>/qr.png?dl=1">Download PNG</a>
                    <a class="btn" href="<?= $e($publicUrl) ?>" target="_blank" rel="noopener">Open page</a>
                </div>
                <div class="hint" style="word-break:break-all;margin-top:10px;"><?= $e($publicUrl) ?></div>
            <?php else: ?>
                <p class="muted" style="font-size:14px;">This customer has no code in the POS, so there is no QR code. Set a code on the customer in the POS first.</p>
            <?php endif; ?>
        </div>
    </div>
</div>
