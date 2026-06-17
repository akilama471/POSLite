<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Barcode Labels — <?= htmlspecialchars((string)$billNumber, ENT_QUOTES, 'UTF-8') ?></title>
    <style>
        body { font-family: sans-serif; background: #1a1a2e; color: #e0e0e0; min-height: 100vh; margin: 0; padding: 32px; }
        h1 { text-align: center; color: #60a5fa; margin-bottom: 4px; }
        .sub { text-align: center; color: #94a3b8; margin-bottom: 24px; }
        .card { background: #16213e; border-radius: 12px; padding: 24px; max-width: 900px; margin: 0 auto; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #0f3460; padding: 10px 12px; text-align: left; font-size: 0.85rem; color: #93c5fd; }
        td { padding: 8px 12px; border-bottom: 1px solid #1e3a5f; font-size: 0.9rem; }
        input[type=number] { background: #0f3460; color: #e0e0e0; border: 1px solid #1e4080; border-radius: 6px; padding: 4px 8px; width: 60px; text-align: center; }
        .btn-row { display: flex; gap: 12px; justify-content: center; margin-top: 24px; }
        button { padding: 12px 28px; border: none; border-radius: 8px; font-size: 1rem; cursor: pointer; font-weight: 600; }
        .btn-print { background: #60a5fa; color: #1a1a2e; }
        .btn-skip  { background: #374151; color: #e0e0e0; }
        #jspm-status { text-align: center; color: #f87171; font-size: 0.9rem; margin-top: 12px; min-height: 20px; }
    </style>
</head>
<body>
<h1>🏷 Barcode / Warranty Labels</h1>
<div class="sub">Bill # <?= htmlspecialchars((string)$billNumber, ENT_QUOTES, 'UTF-8') ?> — select quantity per item</div>

<div class="card">
    <form id="print-form">
        <table>
            <thead>
                <tr><th>Item Name</th><th>Sale Qty</th><th>Bill ID</th><th>Part / IMEI</th><th>Supplier</th><th>Print Count</th></tr>
            </thead>
            <tbody>
                <?php if (empty($items)): ?>
                    <tr><td colspan="6" style="text-align:center;color:#94a3b8;">No items found.</td></tr>
                <?php else: ?>
                    <?php foreach ($items as $i => $item): ?>
                        <tr>
                            <td><?= htmlspecialchars((string)($item['item_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= (int)($item['qty'] ?? 0) ?></td>
                            <td>
                                <input type="hidden" name="bill_id[]" value="<?= htmlspecialchars((string)$billNumber, ENT_QUOTES, 'UTF-8') ?>">
                                <?= htmlspecialchars((string)$billNumber, ENT_QUOTES, 'UTF-8') ?>
                            </td>
                            <td>
                                <input type="hidden" name="imei[]" value="<?= htmlspecialchars((string)($item['imei_part_no'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                <?= htmlspecialchars((string)($item['imei_part_no'] ?? '—'), ENT_QUOTES, 'UTF-8') ?>
                            </td>
                            <td>
                                <input type="hidden" name="supplier[]" value="<?= htmlspecialchars((string)($item['supplier_id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                <?= htmlspecialchars((string)($item['supplier_id'] ?? '—'), ENT_QUOTES, 'UTF-8') ?>
                            </td>
                            <td><input type="number" name="count[]" min="0" value="0" class="print-count"></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        <div id="jspm-status"></div>
        <div class="btn-row">
            <button type="button" class="btn-print" id="btn-print" onclick="doPrinting()" disabled>⏳ Connecting...</button>
            <button type="button" class="btn-skip"  onclick="window.close()">✖ Close</button>
        </div>
    </form>
</div>

<input type="hidden" id="label-printer" value="<?= htmlspecialchars((string)($shop['label_printer'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">

<?php require BASE_PATH . '/views/print/_jspm.php'; ?>
<script>
JSPM.JSPrintManager.WS.onStatusChanged = function() {
    if (jspmStatus()) {
        document.getElementById('btn-print').disabled = false;
        document.getElementById('btn-print').textContent = '🖨 Print Labels';
    }
};

function doPrinting() {
    if (!jspmStatus()) return;
    var escpos = Neodynamic.JSESCPOSBuilder;
    var printerName = document.getElementById('label-printer').value;
    var counts = document.querySelectorAll('.print-count');
    var imeis   = document.querySelectorAll('input[name="imei[]"]');
    var billIds = document.querySelectorAll('input[name="bill_id[]"]');
    var supIds  = document.querySelectorAll('input[name="supplier[]"]');

    for (var i = 0; i < counts.length; i++) {
        var count = parseInt(counts[i].value) || 0;
        if (count <= 0) continue;
        var imei   = imeis[i].value;
        var billId = billIds[i].value;
        var supId  = supIds[i].value;

        for (var j = 0; j < count; j++) {
            var doc = new escpos.Document();
            var cmd = doc
                .font(escpos.FontFamily.B).size(0,0)
                .align(escpos.TextAlignment.Center)
                .text(billId)
                .feed(1)
                .linearBarcode(imei || billId, escpos.Barcode1DType.CODE39,
                    new escpos.Barcode1DOptions(2, 80, true, escpos.BarcodeTextPosition.Below, escpos.BarcodeFont.A))
                .feed(1).text('Sup: ' + supId)
                .feed(2).cut()
                .generateUInt8Array();
            sendEscposJob(cmd, printerName);
        }
    }
}
</script>
</body>
</html>
