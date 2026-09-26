<?php
/** @var string $content */
/** @var callable $e */
/** @var callable $logoUrl */
$icon = isset($client) ? $logoUrl($client) : null;
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#6d28d9">
    <title><?= $e($title ?? $appName) ?></title>
    <?php if ($icon): ?><link rel="icon" href="<?= $e($icon) ?>"><link rel="apple-touch-icon" href="<?= $e($icon) ?>"><?php endif; ?>
    <style>
        :root{
            --bg:#f5f4fb; --card:#fff; --line:#e9e6f5; --ink:#1e1b3a; --muted:#6b6887;
            --accent:#6d28d9; --soft:#ede9fe; --soft-ink:#5b21b6;
            --pos:#15803d; --neg:#dc2626;
            --grad:linear-gradient(135deg,#4f46e5 0%,#6d28d9 55%,#8a2be2 100%);
        }
        *{box-sizing:border-box;}
        html,body{margin:0;}
        body{font-family:system-ui,-apple-system,"Segoe UI",Roboto,sans-serif;background:var(--bg);color:var(--ink);
             -webkit-font-smoothing:antialiased;min-height:100vh;min-height:100dvh;}
        .page{max-width:480px;margin:0 auto;padding:max(28px,env(safe-area-inset-top)) 18px max(32px,env(safe-area-inset-bottom));}
        .client{text-align:center;margin-bottom:22px;}
        .client img{display:block;margin:0 auto 10px;max-width:120px;max-height:120px;object-fit:contain;}
        .client .ph{width:72px;height:72px;margin:0 auto 10px;border-radius:20px;background:var(--grad);color:#fff;
                    display:flex;align-items:center;justify-content:center;font-size:30px;font-weight:800;}
        .client h1{margin:0;font-size:20px;font-weight:800;letter-spacing:-.01em;}
        .hello{text-align:center;font-size:17px;margin:0 0 16px;color:var(--muted);}
        .hello strong{color:var(--ink);}
        .balance{background:var(--grad);color:#fff;border-radius:22px;padding:24px 20px 26px;text-align:center;
                 box-shadow:0 14px 34px rgba(109,40,217,.28);}
        .balance .code{display:inline-block;font-size:13px;font-weight:600;letter-spacing:.04em;background:rgba(255,255,255,.18);
                       padding:5px 12px;border-radius:999px;margin-bottom:16px;word-break:break-all;}
        .balance .label{font-size:13px;text-transform:uppercase;letter-spacing:.08em;opacity:.85;font-weight:700;}
        .balance .amount{font-size:clamp(34px,11vw,52px);font-weight:800;line-height:1.1;margin-top:6px;
                         font-variant-numeric:tabular-nums;word-break:break-word;}
        .balance .asof{font-size:12px;opacity:.75;margin-top:10px;}
        details.history{margin-top:20px;background:var(--card);border:1px solid var(--line);border-radius:18px;overflow:hidden;}
        details.history summary{list-style:none;cursor:pointer;padding:16px 18px;display:flex;align-items:center;gap:10px;
                                font-weight:700;font-size:15px;-webkit-tap-highlight-color:transparent;}
        details.history summary::-webkit-details-marker{display:none;}
        details.history summary .n{margin-left:auto;font-size:12px;font-weight:700;background:var(--soft);color:var(--soft-ink);
                                   padding:3px 9px;border-radius:999px;}
        details.history summary .chev{width:10px;height:10px;border-right:2px solid var(--muted);border-bottom:2px solid var(--muted);
                                      transform:rotate(45deg);transition:transform .2s;margin:0 4px 4px 0;}
        details.history[open] summary .chev{transform:rotate(-135deg);margin:4px 4px 0 0;}
        .tx{display:flex;align-items:center;gap:12px;padding:13px 18px;border-top:1px solid var(--line);}
        .tx .ico{flex:none;width:36px;height:36px;border-radius:11px;display:flex;align-items:center;justify-content:center;
                 font-weight:800;font-size:18px;}
        .tx .ico.in{background:#dcfce7;color:var(--pos);}
        .tx .ico.out{background:#fee2e2;color:var(--neg);}
        .tx .what{flex:1;min-width:0;}
        .tx .what .t{font-weight:700;font-size:14px;text-transform:capitalize;}
        .tx .what .d{font-size:12px;color:var(--muted);margin-top:2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
        .tx .amt{font-weight:800;font-size:14px;font-variant-numeric:tabular-nums;white-space:nowrap;}
        .tx .amt.in{color:var(--pos);} .tx .amt.out{color:var(--neg);}
        .empty{padding:18px;border-top:1px solid var(--line);color:var(--muted);text-align:center;font-size:14px;}
        .foot{text-align:center;color:var(--muted);font-size:12px;margin-top:26px;}
    </style>
</head>
<body>
<div class="page">
    <?= $content ?>
</div>
</body>
</html>
