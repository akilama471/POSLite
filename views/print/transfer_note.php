<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Transfer Note — <?= htmlspecialchars((string)$transId, ENT_QUOTES, 'UTF-8') ?></title>
    <style>
        @media print { body { background: #fff; color: #000; } .no-print { display: none !important; } }
        body { font-family: sans-serif; background: #1a1a2e; color: #e0e0e0; min-height: 100vh; margin: 0; padding: 32px; }
        h1 { text-align: center; color: #818cf8; margin-bottom: 4px; }
        .sub { text-align: center; color: #94a3b8; margin-bottom: 24px; }
        .card { background: #16213e; border-radius: 12px; padding: 24px; max-width: 860px; margin: 0 auto; }
        .meta-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 20px; font-size: 0.92rem; }
        hr { border-color: #1e3a5f; margin: 16px 0; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #0f3460; padding: 10px 12px; text-align: left; font-size: 0.85rem; color: #a5b4fc; }
        td { padding: 8px 12px; border-bottom: 1px solid #1e3a5f; font-size: 0.9rem; }
        tfoot td { font-weight: 700; border-top: 2px solid #3b4cca; }
        .btn-row { display: flex; gap: 12px; justify-content: center; margin-top: 24px; }
        button { padding: 12px 28px; border: none; border-radius: 8px; font-size: 1rem; cursor: pointer; font-weight: 600; }
        .btn-print { background: #818cf8; color: #fff; }
        .btn-pdf   { background: #34d399; color: #1a1a2e; }
        .btn-skip  { background: #374151; color: #e0e0e0; }
    </style>
</head>
<body>
<h1>📦 Stock Transfer Note</h1>
<div class="sub">Transfer # <?= htmlspecialchars((string)$transId, ENT_QUOTES, 'UTF-8') ?></div>
<div class="card">
    <?php if ($header): ?>
    <div class="meta-grid">
        <div><strong>Transfer ID:</strong> <?= htmlspecialchars((string)($header['trans_id'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
        <div><strong>Date:</strong>        <?= htmlspecialchars((string)($header['record_time'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
        <div><strong>From Shop:</strong>   <?= htmlspecialchars((string)($header['from_shop'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></div>
        <div><strong>Operator:</strong>    <?= htmlspecialchars((string)($header['operator_name'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></div>
        <div><strong>Transfer User:</strong> <?= htmlspecialchars((string)($header['transfer_user_name'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></div>
        <div><strong>Status:</strong>      <?= htmlspecialchars((string)($header['trans_status'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
        <div><strong>Sending Time:</strong> <?= htmlspecialchars((string)($header['sending_time'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></div>
        <div><strong>Total Value:</strong>  Rs. <?= number_format((float)($header['total_cost'] ?? 0), 2) ?></div>
    </div>
    <hr>
    <?php endif; ?>

    <table>
        <thead>
            <tr><th>Item Name</th><th>Code / IMEI</th><th style="text-align:center">Qty</th><th style="text-align:right">Cost</th><th style="text-align:right">Value</th><th style="text-align:right">Transfer To</th></tr>
        </thead>
        <tbody>
        <?php if (empty($items)): ?>
            <tr><td colspan="6" style="text-align:center;color:#94a3b8;">No items found.</td></tr>
        <?php else: ?>
            <?php $total = 0; foreach ($items as $item): $total += (float)($item['transfer_value'] ?? 0); ?>
                <tr>
                    <td><?= htmlspecialchars((string)($item['Item_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars((string)($item['code'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                    <td style="text-align:center"><?= (int)($item['stock_count'] ?? 0) ?></td>
                    <td style="text-align:right"><?= number_format((float)($item['part_cost'] ?? 0), 2) ?></td>
                    <td style="text-align:right"><?= number_format((float)($item['transfer_value'] ?? 0), 2) ?></td>
                    <td style="text-align:right"><?= htmlspecialchars((string)($item['to_shop'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
        <?php if (!empty($items)): ?>
        <tfoot>
            <tr>
                <td colspan="4" style="text-align:right;">Total Transfer Value:</td>
                <td style="text-align:right">Rs. <?= number_format($total, 2) ?></td>
                <td></td>
            </tr>
        </tfoot>
        <?php endif; ?>
    </table>

    <div class="btn-row no-print">
        <button class="btn-print" onclick="window.print()">🖨 Print / Save PDF</button>
        <button class="btn-skip"  onclick="window.location.href='/stock/transfer'">✖ Close</button>
    </div>
</div>
</body>
</html>
