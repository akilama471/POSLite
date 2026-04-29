<?php
$shopName = $shop["shop_info_name"] ?? $shop["shopname"] ?? "";
?>

<div class="print-shell" style="max-width:none; padding:18px;">
    <div class="print-actions">
        <button class="print-btn" type="button" onclick="window.print()">Print</button>
        <a class="print-btn" href="/pos/receipts/<?= htmlspecialchars((string) ($bill["billnumber"] ?? ""), ENT_QUOTES, "UTF-8") ?>/barcodes">Back To Labels</a>
        <button class="print-btn" type="button" onclick="window.close()">Close</button>
    </div>

    <?php if ($labels === []): ?>
        <div style="padding:24px; border:1px solid #d0d0d0;">No barcode labels were selected for printing.</div>
    <?php else: ?>
        <div style="display:flex; flex-wrap:wrap; gap:0;">
            <?php foreach ($labels as $label): ?>
                <?php for ($copy = 0; $copy < (int) $label["count"]; $copy++): ?>
                    <div style="width:30mm; height:22mm; padding:1mm; overflow:hidden; page-break-inside:avoid; border:1px solid transparent;">
                        <div style="height:100%; text-align:center;">
                            <div style="height:4mm; font-size:11px; font-weight:700; overflow:hidden; white-space:nowrap;"><?= htmlspecialchars((string) $shopName, ENT_QUOTES, "UTF-8") ?></div>
                            <div style="height:9mm; display:flex; flex-direction:column; align-items:center; justify-content:center;">
                                <img src="/barcode.php?text=<?= rawurlencode((string) $label["code"]) ?>&print=false&size=24" alt="<?= htmlspecialchars((string) $label["code"], ENT_QUOTES, "UTF-8") ?>" style="width:100%; height:55%; object-fit:fill;">
                                <div style="font-size:8px; line-height:1; margin-top:1mm;"><?= htmlspecialchars((string) $label["code"], ENT_QUOTES, "UTF-8") ?></div>
                            </div>
                            <div style="height:4mm; font-size:10px; font-weight:700; overflow:hidden; white-space:nowrap;"><?= htmlspecialchars((string) $label["item_name"], ENT_QUOTES, "UTF-8") ?></div>
                            <div style="height:4mm; font-size:9px; overflow:hidden; white-space:nowrap;">
                                <?= htmlspecialchars((string) (($label["supplier_id"] ?? "") === "" ? "" : "S - " . $label["supplier_id"]), ENT_QUOTES, "UTF-8") ?>
                            </div>
                        </div>
                    </div>
                <?php endfor; ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>
window.addEventListener("load", function () {
    if (<?= $labels === [] ? "false" : "true" ?>) {
        window.print();
    }
});
</script>
