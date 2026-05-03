<div class="app-shell">
    <header class="topbar">
        <div>
            <div class="brand">Shop Sale Profit</div>
            <div class="muted" style="color: #b8c6cf;">Migrated from `rpt_cashier_saleprofit.php`</div>
        </div>
    </header>

    <div class="shell-grid">
        <?php require BASE_PATH . "/views/reports/_nav.php"; ?>
        <main class="page">
            <section class="card stack" style="margin-bottom: 24px;">
                <div class="grid" style="grid-template-columns: repeat(3, 1fr) auto; gap: 16px; align-items: end;">
                    <div class="form-row" style="margin: 0;">
                        <label for="from_date">From Date</label>
                        <input type="date" class="input" id="from_date" value="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="form-row" style="margin: 0;">
                        <label for="to_date">To Date</label>
                        <input type="date" class="input" id="to_date" value="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="form-row" style="margin: 0;">
                        <label for="shop_id">Select Shop</label>
                        <select class="input" id="shop_id">
                            <option value="0" disabled selected>Choose...</option>
                            <option value="-1">All Shops</option>
                            <?php foreach ($shops as $shop): ?>
                                <?php if ($shop['shopid'] > 0): ?>
                                    <option value="<?= (int) $shop['shopid'] ?>"><?= htmlspecialchars((string) $shop['shop_info_name'], ENT_QUOTES, "UTF-8") ?></option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div style="display:flex; gap: 8px;">
                        <button class="btn btn-primary" onclick="loadReport()">Load Data</button>
                        <button class="btn btn-secondary" onclick="window.print()">Print</button>
                    </div>
                </div>
            </section>

            <section class="card stack print-area" id="report-container" style="display:none; min-height: 400px; max-width: 600px; margin: 0 auto;">
                <div style="text-align: center; margin-bottom: 32px;">
                    <h2 style="margin-bottom: 4px;">Shop Sale Profit Report</h2>
                    <div class="muted" id="report-subtitle"></div>
                </div>
                
                <div class="grid" style="grid-template-columns: 1fr auto; gap: 16px; font-size: 1.1em; line-height: 2;">
                    
                    <div>All Sale Value:</div>
                    <div style="text-align: right;" id="all-sale">0.00</div>

                    <div>Value of Repair Bills:</div>
                    <div style="text-align: right; border-bottom: 1px solid #e1e8ed; padding-bottom: 8px;" id="rep-bills">0.00</div>

                    <div class="muted">All Sale Purchases Value:</div>
                    <div class="muted" style="text-align: right;">(<span id="all-cost">0.00</span>)</div>

                    <div class="muted">Value of Repair Item Cost:</div>
                    <div class="muted" style="text-align: right;">(<span id="rep-cost">0.00</span>)</div>

                    <div class="muted">All Removed Stock Value:</div>
                    <div class="muted" style="text-align: right; border-bottom: 1px solid #e1e8ed; padding-bottom: 8px;">(<span id="loss-cost">0.00</span>)</div>

                    <div style="font-weight: bold; font-size: 1.2em; margin-top: 16px;">Net Gain Profit:</div>
                    <div style="font-weight: bold; font-size: 1.2em; margin-top: 16px; text-align: right; color: #28a745; border-bottom: 4px double #2c3e50;" id="net-profit">
                        0.00
                    </div>

                </div>
            </section>
        </main>
    </div>
</div>

<script>
async function loadReport() {
    const fromDate = document.getElementById('from_date').value;
    const toDate = document.getElementById('to_date').value;
    const shopId = document.getElementById('shop_id').value;

    if (shopId == 0) {
        alert("Please Select the Shop.");
        document.getElementById('shop_id').focus();
        return;
    }

    try {
        const formData = new FormData();
        formData.append('type', 'cashier_profit');
        formData.append('from_date', fromDate);
        formData.append('to_date', toDate);
        formData.append('shop_id', shopId);
        formData.append('_token', '<?= htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8") ?>');

        const response = await fetch('/reports/api/fetch', {
            method: 'POST',
            body: formData
        });
        
        if (response.ok) {
            const result = await response.json();
            if (result.status === "error") {
                alert(result.message);
                return;
            }

            renderReport(result.data, fromDate, toDate);
        }
    } catch (e) {
        console.error('Failed to load report data', e);
    }
}

function renderReport(data, from, to) {
    const container = document.getElementById('report-container');
    const subtitle = document.getElementById('report-subtitle');

    container.style.display = 'block';
    subtitle.textContent = `Profit details from ${from} to ${to}`;
    
    document.getElementById('all-sale').textContent = data.all_sale_value.toFixed(2);
    document.getElementById('all-cost').textContent = data.all_sale_cost.toFixed(2);
    document.getElementById('rep-bills').textContent = data.repair_bills.toFixed(2);
    document.getElementById('rep-cost').textContent = data.repair_costs.toFixed(2);
    document.getElementById('loss-cost').textContent = data.removed_stock_value.toFixed(2);

    const netEl = document.getElementById('net-profit');
    netEl.textContent = data.net_profit.toFixed(2);
    
    if (data.net_profit < 0) {
        netEl.style.color = '#dc3545';
    } else {
        netEl.style.color = '#28a745';
    }
}
</script>
