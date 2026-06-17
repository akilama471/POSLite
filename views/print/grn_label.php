<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>GRN Barcode Labels — <?= htmlspecialchars((string)$grnRefNo, ENT_QUOTES, 'UTF-8') ?></title>
    <style>
        body { font-family: sans-serif; background: #1a1a2e; color: #e0e0e0; min-height: 100vh; margin: 0; padding: 32px; }
        h1 { text-align: center; color: #34d399; margin-bottom: 4px; }
        .sub { text-align: center; color: #94a3b8; margin-bottom: 24px; }
        .card { background: #16213e; border-radius: 12px; padding: 24px; max-width: 860px; margin: 0 auto; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #0f3460; padding: 10px 12px; text-align: left; font-size: 0.85rem; color: #6ee7b7; }
        td { padding: 8px 12px; border-bottom: 1px solid #1e3a5f; font-size: 0.9rem; }
        input[type=number] { background: #0f3460; color: #e0e0e0; border: 1px solid #1e4080; border-radius: 6px; padding: 4px 8px; width: 60px; text-align: center; }
        .btn-row { display: flex; gap: 12px; justify-content: center; margin-top: 24px; }
        button { padding: 12px 28px; border: none; border-radius: 8px; font-size: 1rem; cursor: pointer; font-weight: 600; }
        .btn-print { background: #34d399; color: #1a1a2e; }
        .btn-skip  { background: #374151; color: #e0e0e0; }
        #jspm-status { text-align: center; color: #f87171; margin-top: 12px; min-height: 18px; }
    </style>
</head>
<body>
<h1>📦 GRN Barcode Labels</h1>
<div class="sub">GRN # <?= htmlspecialchars((string)$grnRefNo, ENT_QUOTES, 'UTF-8') ?></div>
<div class="card">
    <table>
        <thead>
            <tr><th>Item Name</th><th>IMEI / Part No.</th><th>Cost</th><th>Sale Price</th><th>Qty</th><th>Print Count</th></tr>
        </thead>
        <tbody>
        <?php if (empty($items)): ?>
            <tr><td colspan="6" style="text-align:center;color:#94a3b8;">No items found for this GRN.</td></tr>
        <?php else: ?>
            <?php foreach ($items as $item): ?>
                <tr>
                    <td><?= htmlspecialchars((string)($item['item_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                    <td>
                        <input type="hidden" class="item-imei" value="<?= htmlspecialchars((string)($item['imei_no'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                        <?= htmlspecialchars((string)($item['imei_no'] ?? '—'), ENT_QUOTES, 'UTF-8') ?>
                    </td>
                    <td><?= number_format((float)($item['item_costpri'] ?? 0), 2) ?></td>
                    <td><?= number_format((float)($item['item_sellpri'] ?? 0), 2) ?></td>
                    <td><?= (int)($item['item_qty'] ?? 0) ?></td>
                    <td><input type="number" class="print-count" min="0" value="0"></td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
    <div id="jspm-status"></div>
    <div class="btn-row">
        <button class="btn-print" id="btn-print" onclick="doPrinting()" disabled>⏳ Connecting...</button>
        <button class="btn-skip"  onclick="window.location.href='/grn'">✖ Close</button>
    </div>
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
    var imeis  = document.querySelectorAll('.item-imei');

    for (var i = 0; i < counts.length; i++) {
        var count = parseInt(counts[i].value) || 0;
        if (count <= 0) continue;
        var imei = imeis[i].value;
        for (var j = 0; j < count; j++) {
            var doc = new escpos.Document();
            var cmd = doc
                .font(escpos.FontFamily.B).size(0,0)
                .align(escpos.TextAlignment.Center)
                .linearBarcode(imei, escpos.Barcode1DType.CODE39,
                    new escpos.Barcode1DOptions(2, 80, true, escpos.BarcodeTextPosition.Below, escpos.BarcodeFont.A))
                .feed(2).cut().generateUInt8Array();
            sendEscposJob(cmd, printerName);
        }
    }
}
</script>
</body>
</html>
