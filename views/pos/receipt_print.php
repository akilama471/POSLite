<?php
$formatMoney = static function (mixed $value): string {
    return number_format((float) $value, 2, ".", ",");
};
$customerName = $customer["cus_name"] ?? ($bill["customer_name"] ?? "Cash Customer");
$cashierName = $cashier["visibledata"] ?? $cashier["ankaya"] ?? "";
$shopName = $shop["shop_info_name"] ?? $shop["shopname"] ?? "";
?>

<div class="print-shell" style="max-width:380px;">
    <div class="print-actions">
        <button class="print-btn" type="button" onclick="window.print()">Print</button>
        <a class="print-btn" href="/pos/receipts/<?= htmlspecialchars((string) $bill["billnumber"], ENT_QUOTES, "UTF-8") ?>">Back To Receipt</a>
        <button class="print-btn" type="button" onclick="window.close()">Close</button>
    </div>

    <div style="text-align:center; border-bottom:1px dashed #777; padding-bottom:12px; margin-bottom:12px;">
        <div style="font-size:20px; font-weight:700;"><?= htmlspecialchars((string) $shopName, ENT_QUOTES, "UTF-8") ?></div>
        <?php if (($shop["shopaddress"] ?? "") !== ""): ?>
            <div style="font-size:13px; margin-top:4px;"><?= htmlspecialchars((string) $shop["shopaddress"], ENT_QUOTES, "UTF-8") ?></div>
        <?php endif; ?>
        <?php if (($shop["shop_tel_1"] ?? "") !== ""): ?>
            <div style="font-size:13px; margin-top:4px;">Tel: <?= htmlspecialchars((string) $shop["shop_tel_1"], ENT_QUOTES, "UTF-8") ?></div>
        <?php endif; ?>
        <?php if (($shop["shopemail"] ?? "") !== ""): ?>
            <div style="font-size:13px; margin-top:4px;"><?= htmlspecialchars((string) $shop["shopemail"], ENT_QUOTES, "UTF-8") ?></div>
        <?php endif; ?>
    </div>

    <div style="font-size:13px; line-height:1.6; margin-bottom:12px;">
        <div><strong>Invoice:</strong> <?= htmlspecialchars((string) $bill["billnumber"], ENT_QUOTES, "UTF-8") ?></div>
        <div><strong>Date:</strong> <?= htmlspecialchars((string) ($bill["billaddedtime"] ?? ""), ENT_QUOTES, "UTF-8") ?></div>
        <div><strong>Cashier:</strong> <?= htmlspecialchars((string) $cashierName, ENT_QUOTES, "UTF-8") ?></div>
        <div><strong>Customer:</strong> <?= htmlspecialchars((string) $customerName, ENT_QUOTES, "UTF-8") ?></div>
    </div>

    <table style="width:100%; border-collapse:collapse; font-size:12px;">
        <thead>
            <tr>
                <th style="text-align:left; border-top:1px dashed #777; border-bottom:1px dashed #777; padding:6px 0;">Item</th>
                <th style="text-align:right; border-top:1px dashed #777; border-bottom:1px dashed #777; padding:6px 0;">Qty</th>
                <th style="text-align:right; border-top:1px dashed #777; border-bottom:1px dashed #777; padding:6px 0;">Price</th>
                <th style="text-align:right; border-top:1px dashed #777; border-bottom:1px dashed #777; padding:6px 0;">Amount</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($lines as $line): ?>
                <tr>
                    <td style="padding:8px 0; vertical-align:top;">
                        <div><?= htmlspecialchars((string) $line["item_name"], ENT_QUOTES, "UTF-8") ?></div>
                        <?php if (($line["imei_part_no"] ?? "") !== ""): ?>
                            <div style="font-size:11px; color:#555;"><?= htmlspecialchars((string) $line["imei_part_no"], ENT_QUOTES, "UTF-8") ?></div>
                        <?php endif; ?>
                        <?php if (($line["waranty"] ?? "") !== ""): ?>
                            <div style="font-size:11px; color:#555;">Warranty: <?= htmlspecialchars((string) $line["waranty"], ENT_QUOTES, "UTF-8") ?></div>
                        <?php endif; ?>
                    </td>
                    <td style="padding:8px 0; text-align:right; vertical-align:top;"><?= (int) $line["qty"] ?></td>
                    <td style="padding:8px 0; text-align:right; vertical-align:top;"><?= htmlspecialchars($formatMoney($line["sale_price"] ?? 0), ENT_QUOTES, "UTF-8") ?></td>
                    <td style="padding:8px 0; text-align:right; vertical-align:top;"><?= htmlspecialchars($formatMoney($line["sub_total"] ?? 0), ENT_QUOTES, "UTF-8") ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div style="border-top:1px dashed #777; margin-top:8px; padding-top:10px; font-size:13px; line-height:1.8;">
        <div style="display:flex; justify-content:space-between;"><span>Net Total</span><strong>Rs. <?= htmlspecialchars($formatMoney($bill["totalbill"] ?? 0), ENT_QUOTES, "UTF-8") ?></strong></div>
        <div style="display:flex; justify-content:space-between;"><span>Cash Payment</span><strong>Rs. <?= htmlspecialchars($formatMoney($bill["cash_pay"] ?? 0), ENT_QUOTES, "UTF-8") ?></strong></div>
        <?php if ((float) ($bill["card_pay"] ?? 0) > 0): ?>
            <div style="display:flex; justify-content:space-between;"><span>Card Payment</span><strong>Rs. <?= htmlspecialchars($formatMoney($bill["card_pay"] ?? 0), ENT_QUOTES, "UTF-8") ?></strong></div>
        <?php endif; ?>
        <div style="display:flex; justify-content:space-between;"><span>Balance / Change</span><strong>Rs. <?= htmlspecialchars($formatMoney($bill["balance"] ?? 0), ENT_QUOTES, "UTF-8") ?></strong></div>
    </div>

    <div style="text-align:center; margin:14px 0 10px;">
        <img src="/barcode.php?text=<?= rawurlencode((string) $bill["billnumber"]) ?>&print=false&size=38" alt="<?= htmlspecialchars((string) $bill["billnumber"], ENT_QUOTES, "UTF-8") ?>" style="max-width:100%; height:48px;">
        <div style="font-size:12px; letter-spacing:1px; margin-top:4px;"><?= htmlspecialchars((string) $bill["billnumber"], ENT_QUOTES, "UTF-8") ?></div>
    </div>

    <?php if (($shop["bill_foot_1"] ?? "") !== ""): ?>
        <div style="text-align:center; font-size:12px; margin-top:8px;"><?= htmlspecialchars((string) $shop["bill_foot_1"], ENT_QUOTES, "UTF-8") ?></div>
    <?php endif; ?>
    <?php if (($shop["bill_foot_2"] ?? "") !== ""): ?>
        <div style="text-align:center; font-size:12px; margin-top:4px;"><?= htmlspecialchars((string) $shop["bill_foot_2"], ENT_QUOTES, "UTF-8") ?></div>
    <?php endif; ?>
</div>

<script>
window.addEventListener("load", function () {
    window.print();
});
</script>
