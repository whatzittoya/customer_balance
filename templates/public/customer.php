<?php
/** @var array<string,mixed> $client */
/** @var array<string,mixed> $customer */
/** @var array<int,array<string,mixed>> $history */
/** @var string $greeting */
/** @var \App\Money $money */
/** @var callable $e */
/** @var callable $logoUrl */
$logo = $logoUrl($client);
$bal = (float) $customer['balance'];
$labels = ['TOPUP' => 'Top up', 'CONSUME' => 'Purchase', 'ADJUSTMENT' => 'Adjustment', 'REFUND' => 'Refund'];
?>
<div class="client">
    <?php if ($logo): ?>
        <img src="<?= $e($logo) ?>" alt="<?= $e($client['name']) ?>">
    <?php else: ?>
        <div class="ph"><?= $e(mb_strtoupper(mb_substr((string) $client['name'], 0, 1))) ?></div>
    <?php endif; ?>
    <h1><?= $e($client['name']) ?></h1>
</div>

<p class="hello"><?= $e($greeting) ?>, <strong><?= $e($customer['name']) ?></strong> 👋</p>

<div class="balance">
    <div class="code"><?= $e($customer['code']) ?></div>
    <div class="label">Current balance</div>
    <div class="amount"><?= $e($money->format($bal)) ?></div>
    <div class="asof">as of <?= $e(date('d M Y, H:i')) ?></div>
</div>

<details class="history">
    <summary>
        <span class="chev"></span>
        Transaction history
        <span class="n"><?= count($history) ?></span>
    </summary>
    <?php if ($history === []): ?>
        <div class="empty">No transactions yet.</div>
    <?php endif; ?>
    <?php foreach ($history as $tx): ?>
        <?php
        $amt = (float) $tx['amount'];
        $dir = $amt < 0 ? 'out' : 'in';
        $remark = strtoupper(trim((string) $tx['remark']));
        $label = $labels[$remark] ?? strtolower($remark ?: 'transaction');
        $detail = array_filter([
            $tx['date'] ? date('d M Y · H:i', strtotime((string) $tx['date'])) : null,
            trim((string) $tx['paymentMethod']) ?: null,
            trim((string) $tx['outlet']) ?: null,
        ]);
        ?>
        <div class="tx">
            <div class="ico <?= $dir ?>"><?= $dir === 'in' ? '+' : '−' ?></div>
            <div class="what">
                <div class="t"><?= $e($label) ?></div>
                <div class="d"><?= $e(implode(' · ', $detail)) ?></div>
            </div>
            <div class="amt <?= $dir ?>"><?= $e($money->signed($amt)) ?></div>
        </div>
    <?php endforeach; ?>
</details>

<div class="foot">Balance questions? Ask our staff at <?= $e($client['name']) ?>.</div>
