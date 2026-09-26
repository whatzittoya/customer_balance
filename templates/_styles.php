<?php
/* Shared staff/admin styles — the reservation app's look, in blue-violet. */
?>
<style>
    :root{
        --bg:#f5f4fb; --card:#fff; --line:#e4e2f0; --ink:#1e1b3a; --muted:#6b6887;
        --accent:#6d28d9; --accent-2:#8a2be2; --accent-ink:#fff; --link:#4f46e5;
        --soft:#ede9fe; --soft-ink:#5b21b6; --soft-bd:#c4b5fd;
        --ok:#dcfce7; --ok-ink:#166534; --ok-bd:#86e0a3;
        --warn:#fef3c7; --warn-ink:#8a5a00; --warn-bd:#f0c150;
        --bad:#fee2e2; --bad-ink:#991b1b; --bad-bd:#f3a3a3;
        --danger:#dc2626;
        --grad:linear-gradient(135deg,#4f46e5 0%,#6d28d9 55%,#8a2be2 100%);
    }
    *{box-sizing:border-box;}
    body{margin:0;font-family:system-ui,-apple-system,"Segoe UI",Roboto,sans-serif;background:var(--bg);color:var(--ink);}
    a{color:var(--link);text-decoration:none;}
    a:hover{text-decoration:underline;}
    header{background:var(--card);border-bottom:1px solid var(--line);position:sticky;top:0;z-index:20;}
    .bar{max-width:1180px;margin:0 auto;padding:0 18px;min-height:56px;display:flex;align-items:center;gap:18px;flex-wrap:wrap;}
    .brand{font-weight:800;color:var(--ink);font-size:16px;white-space:nowrap;display:flex;align-items:center;gap:9px;}
    .brand span{color:var(--accent);}
    .brand img{height:30px;width:auto;max-width:90px;object-fit:contain;border-radius:6px;}
    .brand:hover{text-decoration:none;opacity:.85;}
    nav.main{display:flex;gap:4px;flex:1;flex-wrap:wrap;}
    .navlink{padding:7px 12px;border-radius:8px;color:var(--ink);font-size:14px;font-weight:600;}
    .navlink:hover{background:var(--soft);text-decoration:none;}
    .navlink.active{background:var(--accent);color:var(--accent-ink);}
    .userbox{display:flex;align-items:center;gap:10px;font-size:13px;color:var(--muted);white-space:nowrap;}
    .role{display:inline-block;padding:2px 8px;border-radius:999px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.03em;background:var(--soft);color:var(--soft-ink);}
    .role.admin{background:var(--accent);color:var(--accent-ink);}
    main{max-width:1180px;margin:0 auto;padding:22px 18px 60px;}
    .card{background:var(--card);border:1px solid var(--line);border-radius:10px;padding:18px;margin-bottom:16px;}
    .card.flush{padding:0;overflow:hidden;}
    .btn{display:inline-flex;align-items:center;gap:6px;padding:8px 14px;border-radius:8px;border:1px solid var(--line);background:var(--card);color:var(--ink);cursor:pointer;font-size:14px;font-weight:600;font-family:inherit;}
    .btn-primary{background:var(--accent);border-color:var(--accent);color:var(--accent-ink);}
    .btn-danger{color:var(--danger);border-color:var(--bad-bd);background:#fff;}
    .btn-sm{padding:5px 10px;font-size:13px;}
    .btn:hover{opacity:.92;text-decoration:none;}
    .btn[disabled]{opacity:.5;cursor:not-allowed;}
    .toolbar{display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:16px;flex-wrap:wrap;}
    h1{font-size:20px;margin:0;}
    h2{font-size:16px;margin:0 0 12px;}
    .table-wrap{overflow-x:auto;}
    table.list{width:100%;border-collapse:collapse;}
    table.list th,table.list td{text-align:left;padding:9px 12px;border-bottom:1px solid var(--line);font-size:14px;vertical-align:middle;}
    table.list th{color:var(--muted);font-size:12px;text-transform:uppercase;letter-spacing:.03em;background:#faf9fe;}
    table.list tr:last-child td{border-bottom:0;}
    table.list tbody tr:hover{background:#faf9fe;}
    .num{text-align:right !important;font-variant-numeric:tabular-nums;white-space:nowrap;}
    .pos{color:var(--ok-ink);} .neg{color:var(--danger);}
    .muted{color:var(--muted);}
    .flash{background:var(--ok);border:1px solid var(--ok-bd);color:var(--ok-ink);padding:9px 13px;border-radius:8px;margin-bottom:14px;font-size:14px;}
    .flash.err{background:var(--bad);border-color:var(--bad-bd);color:var(--bad-ink);}
    .note{background:var(--warn);border:1px solid var(--warn-bd);color:var(--warn-ink);padding:9px 13px;border-radius:8px;margin-bottom:14px;font-size:13px;}
    .pill{display:inline-block;padding:2px 9px;border-radius:999px;font-size:12px;font-weight:700;white-space:nowrap;}
    .pill.ok{background:var(--ok);color:var(--ok-ink);}
    .pill.bad{background:var(--bad);color:var(--bad-ink);}
    .pill.off{background:#eeedf4;color:var(--muted);}
    .pill.warn{background:var(--warn);color:var(--warn-ink);}
    .pill.violet{background:var(--soft);color:var(--soft-ink);}
    /* list filter box — see the [data-filter] script */
    .filterbar{display:flex;align-items:center;gap:10px;margin-bottom:14px;flex-wrap:wrap;}
    .filterbar input[type=search]{width:320px;max-width:100%;padding:8px 11px;}
    .filterbar .count{font-size:13px;color:var(--muted);white-space:nowrap;}
    .filterbar .count.none{color:var(--danger);font-weight:600;}
    .f-hide{display:none !important;}
    label{display:block;font-size:13px;font-weight:600;margin:12px 0 5px;}
    label.check{display:flex;align-items:center;gap:8px;font-weight:500;}
    label.check input{width:auto;}
    input,select,textarea{width:100%;padding:9px 11px;border:1px solid var(--line);border-radius:8px;font-size:14px;font-family:inherit;background:#fff;color:var(--ink);}
    input:focus,select:focus,textarea:focus{outline:2px solid var(--soft-bd);border-color:var(--accent);}
    input[type=checkbox]{width:16px;height:16px;accent-color:var(--accent);padding:0;}
    .row{display:flex;gap:14px;flex-wrap:wrap;}
    .row>div{flex:1;min-width:180px;}
    .error{color:var(--danger);font-size:12px;margin-top:4px;}
    .hint{color:var(--muted);font-size:12px;margin-top:4px;}
    .actions{margin-top:20px;display:flex;gap:10px;align-items:center;flex-wrap:wrap;}
    .inline{display:inline;}
    .stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:14px;margin-bottom:16px;}
    .stat{background:var(--card);border:1px solid var(--line);border-radius:10px;padding:14px 16px;}
    .stat .k{font-size:12px;color:var(--muted);text-transform:uppercase;letter-spacing:.03em;font-weight:700;}
    .stat .v{font-size:22px;font-weight:800;margin-top:4px;font-variant-numeric:tabular-nums;}
    .stat.hero{background:var(--grad);border:0;color:#fff;}
    .stat.hero .k{color:rgba(255,255,255,.8);}
    .pager{display:flex;gap:6px;align-items:center;justify-content:center;margin-top:14px;flex-wrap:wrap;font-size:14px;}
    .logo-thumb{width:40px;height:40px;object-fit:contain;border-radius:8px;border:1px solid var(--line);background:#fff;}
    .logo-ph{width:40px;height:40px;border-radius:8px;background:var(--grad);color:#fff;display:inline-flex;align-items:center;justify-content:center;font-weight:800;}
    code{background:#f1f0f8;padding:1px 6px;border-radius:5px;font-size:13px;}
    /* confirm dialog (any form with data-confirm) */
    dialog.dlg{width:min(420px,calc(100vw - 32px));padding:0;border:1px solid var(--line);border-radius:12px;box-shadow:0 20px 48px rgba(30,27,58,.22);color:var(--ink);background:var(--card);}
    dialog.dlg::backdrop{background:rgba(30,27,58,.42);}
    .dlg-body{padding:20px 20px 0;}
    .dlg-body h2{margin:0 0 8px;font-size:17px;}
    .dlg-body p{margin:0;font-size:14px;line-height:1.5;color:var(--muted);}
    .dlg-actions{display:flex;justify-content:flex-end;gap:10px;padding:18px 20px 20px;}
    .login-wrap{max-width:380px;margin:8vh auto 0;}
    .login-head{text-align:center;margin-bottom:18px;}
    .login-head img{max-height:84px;max-width:200px;object-fit:contain;margin-bottom:10px;}
    @media (max-width:640px){
        .bar{padding:8px 14px;gap:10px;}
        main{padding:16px 14px 50px;}
        .userbox{width:100%;justify-content:flex-end;}
        .hide-sm{display:none;}
    }
</style>
