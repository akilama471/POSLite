<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Supplier Payment Receipt</title>
    <style>
        body { font-family: monospace; background: #1a1a2e; color: #e0e0e0; display: flex; flex-direction: column; align-items: center; justify-content: center; min-height: 100vh; margin: 0; }
        .card { background: #16213e; padding: 40px; border-radius: 12px; max-width: 480px; width: 100%; text-align: center; box-shadow: 0 8px 32px rgba(0,0,0,.4); }
        h1 { font-size: 1.5rem; margin: 0 0 8px; color: #a78bfa; }
        .sub { color: #94a3b8; margin-bottom: 20px; }
        .info { background: #0f3460; border-radius: 8px; padding: 14px 18px; text-align: left; font-size: 0.9rem; margin-bottom: 16px; }
        .btn-row { display: flex; gap: 12px; justify-content: center; margin-top: 20px; }
        button { padding: 12px 28px; border: none; border-radius: 8px; font-size: 1rem; cursor: pointer; font-weight: 600; }
        .btn-print { background: #a78bfa; color: #1a1a2e; }
        .btn-skip  { background: #374151; color: #e0e0e0; }
        #jspm-status { color: #f87171; font-size: 0.88rem; margin-top: 12px; min-height: 18px; }
    </style>
</head>
<body>
<div class="card">
    <h1>✅ Payment Recorded</h1>
    <div class="sub">Supplier Payment Confirmation</div>
    <div class="info">
        <?php $s = $supplier; ?>
        <div><strong>Supplier:</strong> <?= htmlspecialchars((string)($s['suppler_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
        <div><strong>Contact:</strong>  <?= htmlspecialchars((string)($s['suppler_mobile'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
        <div><strong>Mode:</strong>     <?= $payType === 1 ? 'Cash' : ($payType === 2 ? 'Cheque' : 'Cash Credit') ?></div>
        <div><strong>Amount:</strong>   Rs. <?= number_format((float)$amount, 2) ?></div>
        <?php if ($payType === 2 && $chequeNo): ?>
        <div><strong>Cheque #:</strong> <?= htmlspecialchars((string)$chequeNo, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
        <div><strong>Balance:</strong>  Rs. <?= number_format((float)($s['accbalance'] ?? 0), 2) ?></div>
        <div><strong>Time:</strong>     <?= date('Y-m-d h:i A') ?></div>
    </div>
    <div id="jspm-status"></div>
    <div class="btn-row">
        <button class="btn-print" id="btn-print" onclick="doPrinting()" disabled>⏳ Connecting...</button>
        <button class="btn-skip"  onclick="window.location.href='/finance/supplier-payments'">✖ No Need</button>
    </div>
</div>
<input type="hidden" id="bill-printer" value="<?= htmlspecialchars((string)($shop['bill_printer'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
<?php require BASE_PATH . '/views/print/_jspm.php'; ?>
<script>
JSPM.JSPrintManager.WS.onStatusChanged = function() {
    if (jspmStatus()) {
        document.getElementById('btn-print').disabled = false;
        document.getElementById('btn-print').textContent = '🖨 Print Receipt';
    }
};
function doPrinting() {
    if (!jspmStatus()) return;
    var escpos = Neodynamic.JSESCPOSBuilder;
    var doc = new escpos.Document();
    var printerName = document.getElementById('bill-printer').value;
    <?php
    $shopName = addslashes((string)($shop['shop_name'] ?? 'NextGen POS'));
    $shopAddr = addslashes((string)($shop['shop_address'] ?? ''));
    $shopTel  = addslashes((string)($shop['shop_tel_1'] ?? ''));
    $suppName = addslashes((string)($supplier['suppler_name'] ?? ''));
    $suppTp   = addslashes((string)($supplier['suppler_mobile'] ?? ''));
    $modeStr  = $payType === 1 ? 'Cash' : ($payType === 2 ? 'Cheque' : 'Cash Credit');
    $now      = date('Y-m-d h:i A');
    ?>
    var escposCommands = doc
        .font(escpos.FontFamily.A).align(escpos.TextAlignment.Center).size(1,1)
        .text("<?= $shopName ?>")
        .font(escpos.FontFamily.B).size(0,0)
        .text("<?= $shopAddr ?>").text("Tel: <?= $shopTel ?>")
        .feed(1).text("------------------------------------------------")
        .align(escpos.TextAlignment.LeftJustification)
        .text("Supplier Payment Confirmation")
        .feed(1)
        .text("Supplier: <?= $suppName ?>")
        .text("Contact : <?= $suppTp ?>")
        .feed(1).text("------------------------------------------------")
        .text("Mode    : <?= addslashes($modeStr) ?>")
        .text("Time    : <?= $now ?>")
        .text("Amount  : Rs.<?= number_format((float)$amount, 2) ?>")
        <?php if ($payType === 2 && $chequeNo): ?>
        .text("Cheque# : <?= addslashes($chequeNo) ?>")
        <?php endif; ?>
        .feed(1).align(escpos.TextAlignment.Center)
        .text("------------------------------------------------")
        .feed(2).cut().generateUInt8Array();

    sendEscposJob(escposCommands, printerName, function() {
        window.location.href = '/finance/supplier-payments';
    });
}
</script>
</body>
</html>
