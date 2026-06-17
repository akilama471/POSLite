<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Repair Bill — <?= htmlspecialchars((string)$jobNumber, ENT_QUOTES, 'UTF-8') ?></title>
    <style>
        body { font-family: monospace; background: #1a1a2e; color: #e0e0e0; display: flex; flex-direction: column; align-items: center; justify-content: center; min-height: 100vh; margin: 0; }
        .card { background: #16213e; padding: 40px; border-radius: 12px; max-width: 520px; width: 100%; text-align: center; box-shadow: 0 8px 32px rgba(0,0,0,.4); }
        h1 { font-size: 1.5rem; color: #fb923c; margin: 0 0 8px; }
        .sub { color: #94a3b8; margin-bottom: 20px; }
        .info { background: #0f3460; border-radius: 8px; padding: 14px 18px; text-align: left; font-size: 0.9rem; margin-bottom: 16px; }
        .btn-row { display: flex; gap: 12px; justify-content: center; margin-top: 20px; }
        button { padding: 12px 28px; border: none; border-radius: 8px; font-size: 1rem; cursor: pointer; font-weight: 600; }
        .btn-print { background: #fb923c; color: #1a1a2e; }
        .btn-skip  { background: #374151; color: #e0e0e0; }
        #jspm-status { color: #f87171; font-size: 0.88rem; margin-top: 12px; min-height: 18px; }
    </style>
</head>
<body>
<div class="card">
    <h1>🔧 Repair Bill</h1>
    <div class="sub">Job # <?= htmlspecialchars((string)$jobNumber, ENT_QUOTES, 'UTF-8') ?></div>
    <?php $j = $job; ?>
    <div class="info">
        <div><strong>Job ID:</strong>      <?= htmlspecialchars((string)($j['job_number'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
        <div><strong>Customer:</strong>    <?= htmlspecialchars((string)($j['job_cus_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
        <div><strong>Contact:</strong>     <?= htmlspecialchars((string)($j['job_cus_contac'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
        <div><strong>IMEI:</strong>        <?= htmlspecialchars((string)($j['job_cus_imei'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
        <div><strong>Model:</strong>       <?= htmlspecialchars((string)($j['job_cus_model'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
        <div><strong>Fault:</strong>       <?= htmlspecialchars((string)($j['job_fault'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
        <div><strong>Cashier:</strong>     <?= htmlspecialchars((string)($j['cashier_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
        <div><strong>Parts Cost:</strong>  Rs. <?= number_format((float)($j['job_partcost'] ?? 0), 2) ?></div>
        <div><strong>Repair Cost:</strong> Rs. <?= number_format((float)($j['job_repaircost'] ?? 0), 2) ?></div>
        <div><strong>Net Total:</strong>   Rs. <?= number_format((float)($j['job_payment_total'] ?? 0), 2) ?></div>
        <div><strong>Cash Paid:</strong>   Rs. <?= number_format((float)($j['cus_cash_pay'] ?? 0), 2) ?></div>
        <div><strong>Balance:</strong>     Rs. <?= number_format((float)($j['amt_cus_balance'] ?? 0), 2) ?></div>
    </div>
    <div id="jspm-status"></div>
    <div class="btn-row">
        <button class="btn-print" id="btn-print" onclick="doPrinting()" disabled>⏳ Connecting...</button>
        <button class="btn-skip"  onclick="doSkip()">✖ No Need</button>
    </div>
</div>
<input type="hidden" id="bill-printer" value="<?= htmlspecialchars((string)($shop['bill_printer'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
<input type="hidden" id="return-to"    value="<?= htmlspecialchars((string)$returnTo, ENT_QUOTES, 'UTF-8') ?>">
<?php require BASE_PATH . '/views/print/_jspm.php'; ?>
<script>
JSPM.JSPrintManager.WS.onStatusChanged = function() {
    if (jspmStatus()) {
        document.getElementById('btn-print').disabled = false;
        document.getElementById('btn-print').textContent = '🖨 Print Bill';
    }
};
function doPrinting() {
    if (!jspmStatus()) return;
    var escpos = Neodynamic.JSESCPOSBuilder;
    var doc = new escpos.Document();
    var printerName = document.getElementById('bill-printer').value;
    <?php
    $shopName   = addslashes((string)($shop['shop_name'] ?? 'NextGen POS'));
    $shopAddr   = addslashes((string)($shop['shop_address'] ?? ''));
    $shopTel    = addslashes((string)($shop['shop_tel_1'] ?? ''));
    $jobId      = addslashes((string)($j['job_number'] ?? ''));
    $jobDate    = addslashes((string)($j['handover_time'] ?? $j['job_add_date'] ?? ''));
    $cashier    = addslashes((string)($j['cashier_name'] ?? ''));
    $cusName    = addslashes((string)($j['job_cus_name'] ?? ''));
    $cusTp      = addslashes((string)($j['job_cus_contac'] ?? ''));
    $fault      = addslashes((string)($j['job_fault'] ?? ''));
    $imei       = addslashes((string)($j['job_cus_imei'] ?? ''));
    $model      = addslashes((string)($j['job_cus_model'] ?? ''));
    $fullAmt    = (float)($j['job_partcost'] ?? 0) + (float)($j['job_repaircost'] ?? 0);
    $advance    = (float)($j['job_payment_adv'] ?? 0);
    $net        = (float)($j['job_payment_total'] ?? 0);
    $cashPaid   = (float)($j['cus_cash_pay'] ?? 0);
    $cardPaid   = (float)($j['cus_card_pay'] ?? 0);
    $balance    = (float)($j['amt_cus_balance'] ?? 0);
    ?>
    var escposCommands = doc
        .font(escpos.FontFamily.A).align(escpos.TextAlignment.Center).size(1,1)
        .text("<?= $shopName ?>")
        .font(escpos.FontFamily.B).size(0,0)
        .text("<?= $shopAddr ?>").text("Tel: <?= $shopTel ?>")
        .feed(1).text("------------------------------------------------")
        .align(escpos.TextAlignment.LeftJustification)
        .text("Job ID       : <?= $jobId ?>")
        .text("Job Date     : <?= $jobDate ?>")
        .text("Staff Member : <?= $cashier ?>")
        .text("Customer Name: <?= $cusName ?>")
        .text("Contact No#  : <?= $cusTp ?>")
        .feed(1).text("------------------------------------------------")
        .text("Fault Details: <?= $fault ?>")
        .text("Phone IMEI  : <?= $imei ?>")
        .text("Phone Model : <?= $model ?>")
        .feed(1).text("------------------------------------------------")
        .size(1,0).align(escpos.TextAlignment.RightJustification)
        .text("Job Charge : Rs.<?= number_format($fullAmt, 2) ?>")
        .text("Job Advance: Rs.<?= number_format($advance, 2) ?>")
        .text("Net Total  : Rs.<?= number_format($net, 2) ?>")
        .feed(1)
        .text("Cash Paid  : Rs.<?= number_format($cashPaid, 2) ?>")
        .text("Card Paid  : Rs.<?= number_format($cardPaid, 2) ?>")
        .text("Balance    : Rs.<?= number_format($balance, 2) ?>")
        .size(0,0).text("------------------------------------------------")
        .align(escpos.TextAlignment.Center).feed(1)
        .linearBarcode("<?= $jobId ?>", escpos.Barcode1DType.CODE39,
            new escpos.Barcode1DOptions(2, 100, true, escpos.BarcodeTextPosition.Below, escpos.BarcodeFont.A))
        .feed(2).text("-Thank You Come Again-").feed(2).cut()
        .generateUInt8Array();

    sendEscposJob(escposCommands, printerName, function() { doSkip(); });
}
function doSkip() {
    var rt = document.getElementById('return-to').value;
    if (rt === '1') { window.location.href = '/repair/handover'; }
    else { window.location.href = '/repair/jobs'; }
}
</script>
</body>
</html>
