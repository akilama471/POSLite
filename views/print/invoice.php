<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Print Invoice — <?= htmlspecialchars((string)$billNumber, ENT_QUOTES, 'UTF-8') ?></title>
    <style>
        body { font-family: monospace; background: #1a1a2e; color: #e0e0e0; display: flex; flex-direction: column; align-items: center; justify-content: center; min-height: 100vh; margin: 0; }
        .card { background: #16213e; padding: 40px; border-radius: 12px; max-width: 540px; width: 100%; text-align: center; box-shadow: 0 8px 32px rgba(0,0,0,0.4); }
        h1 { font-size: 1.6rem; margin: 0 0 8px; color: #4ade80; }
        .sub { color: #94a3b8; margin-bottom: 24px; font-size: 0.95rem; }
        .badge { display: inline-block; background: #0f3460; color: #60a5fa; padding: 4px 14px; border-radius: 999px; font-size: 0.85rem; margin-bottom: 24px; }
        .btn-row { display: flex; gap: 12px; justify-content: center; flex-wrap: wrap; margin-top: 24px; }
        button { padding: 12px 28px; border: none; border-radius: 8px; font-size: 1rem; cursor: pointer; font-weight: 600; transition: opacity .2s; }
        button:hover { opacity: 0.88; }
        .btn-print  { background: #4ade80; color: #1a1a2e; }
        .btn-barcode{ background: #60a5fa; color: #1a1a2e; }
        .btn-skip   { background: #374151; color: #e0e0e0; }
        #jspm-status { color: #f87171; font-size: 0.9rem; margin-top: 16px; min-height: 20px; }
    </style>
</head>
<body>
<div class="card">
    <h1>✅ Bill Complete</h1>
    <div class="sub">Invoice ready to print</div>
    <div class="badge">Invoice # <?= htmlspecialchars((string)$billNumber, ENT_QUOTES, 'UTF-8') ?></div>

    <div style="text-align:left; background:#0f3460; border-radius:8px; padding:16px; font-size:0.9rem; margin-bottom:8px;">
        <div><strong>Customer:</strong> <?= htmlspecialchars((string)($bill['cus_name'] ?? 'Walk-In'), ENT_QUOTES, 'UTF-8') ?></div>
        <div><strong>Cashier:</strong>  <?= htmlspecialchars((string)($bill['cashier_name'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></div>
        <div><strong>Date:</strong>     <?= htmlspecialchars((string)($bill['billaddedtime'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
        <div><strong>Total:</strong>    Rs. <?= number_format((float)($bill['totalbill'] ?? 0), 2) ?></div>
        <div><strong>Cash:</strong>     Rs. <?= number_format((float)($bill['cash_pay'] ?? 0), 2) ?></div>
        <?php if (($bill['card_pay'] ?? 0) > 0): ?>
        <div><strong>Card:</strong>     Rs. <?= number_format((float)($bill['card_pay'] ?? 0), 2) ?></div>
        <?php endif; ?>
        <div><strong>Balance:</strong>  Rs. <?= number_format((float)($bill['balance'] ?? 0), 2) ?></div>
    </div>

    <div id="jspm-status"></div>

    <div class="btn-row">
        <button class="btn-print"   id="btn-print"   onclick="doPrinting()" disabled>⏳ Connecting...</button>
        <button class="btn-barcode" onclick="openBarcode()">🏷 Print Barcodes</button>
        <button class="btn-skip"    onclick="doSkip()">✖ No Need</button>
    </div>
</div>

<input type="hidden" id="bill-printer" value="<?= htmlspecialchars((string)($shop['bill_printer'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
<input type="hidden" id="bill-number"  value="<?= htmlspecialchars((string)$billNumber, ENT_QUOTES, 'UTF-8') ?>">
<input type="hidden" id="slot-id"      value="<?= htmlspecialchars((string)$slotId, ENT_QUOTES, 'UTF-8') ?>">

<?php require BASE_PATH . '/views/print/_jspm.php'; ?>
<script>
JSPM.JSPrintManager.WS.onStatusChanged = function() {
    if (jspmStatus()) {
        document.getElementById('btn-print').disabled = false;
        document.getElementById('btn-print').textContent = '🖨 Print Invoice';
    }
};

function doPrinting() {
    if (!jspmStatus()) return;
    var escpos = Neodynamic.JSESCPOSBuilder;
    var doc = new escpos.Document();
    var printerName = document.getElementById('bill-printer').value;
    var billId = document.getElementById('bill-number').value;

    <?php
    $shopAddr = addslashes((string)($shop['shop_address'] ?? ''));
    $shopTel  = addslashes((string)($shop['shop_tel_1'] ?? ''));
    $shopName = addslashes((string)($shop['shop_name'] ?? 'NextGen POS'));
    $cashier  = addslashes((string)($bill['cashier_name'] ?? ''));
    $cusName  = addslashes((string)($bill['cus_name'] ?? 'Walk-In'));
    $invDate  = addslashes((string)($bill['billaddedtime'] ?? ''));
    $invId    = addslashes((string)$billNumber);

    $partList = '';
    foreach ($items as $item) {
        $name   = addslashes((string)($item['item_name'] ?? ''));
        $imei   = addslashes((string)($item['imei_part_no'] ?? ''));
        $desc   = $imei ? $name . ' (' . $imei . ')' : $name;
        $desc48 = str_pad($desc, 48);
        $wStr   = $item['waranty'] ? '(Warranty:' . $item['waranty'] . ')' : str_repeat(' ', 22);
        $wStr   = str_pad($wStr, 22);
        $qty    = str_pad((string)$item['qty'], 4, ' ', STR_PAD_LEFT);
        $price  = str_pad(number_format((float)($item['sale_price'] ?? 0), 2), 11, ' ', STR_PAD_LEFT);
        $total  = str_pad(number_format((float)($item['sub_total'] ?? 0), 2), 11, ' ', STR_PAD_LEFT);
        $partList .= ".text(\"" . $desc48 . "\")";
        $partList .= ".text(\"" . $wStr . $qty . $price . $total . "\")";
    }

    $tot  = str_pad(number_format((float)($bill['totalbill'] ?? 0), 2), 10, ' ', STR_PAD_LEFT);
    $cash = str_pad(number_format((float)($bill['cash_pay'] ?? 0), 2), 10, ' ', STR_PAD_LEFT);
    $card = str_pad(number_format((float)($bill['card_pay'] ?? 0), 2), 10, ' ', STR_PAD_LEFT);
    $bal  = str_pad(number_format((float)($bill['balance'] ?? 0), 2), 10, ' ', STR_PAD_LEFT);
    $footList = ".text(\"Net Total   : {$tot}\")";
    $footList .= ".text(\"Cash Payment: {$cash}\")";
    if (($bill['card_pay'] ?? 0) > 0) {
        $footList .= ".text(\"Card Payment: {$card}\")";
    }
    $footList .= ".text(\"Balance     : {$bal}\")";
    ?>

    var escposCommands = doc
        .font(escpos.FontFamily.A).align(escpos.TextAlignment.Center).size(1,1)
        .text("<?= $shopName ?>")
        .font(escpos.FontFamily.B).size(0,0)
        .text("<?= $shopAddr ?>")
        .text("Tel: <?= $shopTel ?>")
        .feed(1).text("------------------------------------------------")
        .align(escpos.TextAlignment.LeftJustification)
        .text("Invoice : <?= $invId ?>")
        .text("Date    : <?= $invDate ?>")
        .text("Cashier : <?= $cashier ?>")
        .text("Customer: <?= $cusName ?>")
        .feed(1).text("Item                                    Qty      Price     Amount")
        .align(escpos.TextAlignment.Center).text("------------------------------------------------")
        .align(escpos.TextAlignment.LeftJustification)
        <?= $partList ?>
        .align(escpos.TextAlignment.Center).text("------------------------------------------------")
        .size(1,0).align(escpos.TextAlignment.LeftJustification)
        <?= $footList ?>
        .size(0,0).text("------------------------------------------------")
        .align(escpos.TextAlignment.Center).feed(1)
        .linearBarcode(billId, escpos.Barcode1DType.CODE128,
            new escpos.Barcode1DOptions(true, escpos.BarcodeTextPosition.Off, escpos.BarcodeFont.A))
        .feed(2).text("-Thank You Come Again-").feed(2).cut().cashDraw(1)
        .generateUInt8Array();

    sendEscposJob(escposCommands, printerName);
}

function openBarcode() {
    var billId = document.getElementById('bill-number').value;
    window.open('/print/barcode?docid=' + encodeURIComponent(billId), '_blank');
}

function doSkip() {
    var slot = document.getElementById('slot-id').value;
    if (slot === '1') { window.location.href = '/pos'; }
    else { window.close(); }
}
</script>
</body>
</html>
