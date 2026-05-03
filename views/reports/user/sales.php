<div class="app-shell">
    <header class="topbar">
        <div>
            <div class="brand">User Sales Report</div>
            <div class="muted" style="color: #b8c6cf;">Migrated from `rpt_usersale_rpt.php`</div>
        </div>
    </header>
    <div class="shell-grid">
        <?php require BASE_PATH . "/views/reports/_nav.php"; ?>
        <main class="page">
            <section class="card stack" style="margin-bottom:24px;">
                <div class="grid" style="grid-template-columns: repeat(3,1fr) auto; gap:16px; align-items:end;">
                    <div class="form-row" style="margin:0;">
                        <label for="from_date">From Date</label>
                        <input type="date" class="input" id="from_date" value="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="form-row" style="margin:0;">
                        <label for="to_date">To Date</label>
                        <input type="date" class="input" id="to_date" value="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="form-row" style="margin:0;">
                        <label for="shop_id">Select Shop</label>
                        <select class="input" id="shop_id">
                            <option value="-1">All Shops</option>
                            <?php foreach ($shops as $shop): ?>
                                <?php if ($shop['shopid'] > 0): ?>
                                    <option value="<?= (int)$shop['shopid'] ?>"><?= htmlspecialchars((string)$shop['shop_info_name'], ENT_QUOTES, 'UTF-8') ?></option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div style="display:flex; gap:8px;">
                        <button class="btn btn-primary" onclick="loadReport()">Load</button>
                        <button class="btn btn-secondary" onclick="window.print()">Print</button>
                    </div>
                </div>
            </section>
            <section class="card stack print-area" id="report-container" style="display:none; min-height:400px;">
                <div style="text-align:center; margin-bottom:24px;">
                    <h2 style="margin-bottom:4px;">User Sales Performance</h2>
                    <div class="muted" id="report-subtitle"></div>
                </div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th style="width:50%;">User Name</th>
                            <th style="width:25%; text-align:center;">Sale Count (Bills)</th>
                            <th style="width:25%; text-align:right;">Net Profit (Rs.)</th>
                        </tr>
                    </thead>
                    <tbody id="report-body"></tbody>
                </table>
            </section>
        </main>
    </div>
</div>
<script>
async function loadReport() {
    const from = document.getElementById('from_date').value;
    const to = document.getElementById('to_date').value;
    const shopId = document.getElementById('shop_id').value;
    const fd = new FormData();
    fd.append('type','user_sales'); fd.append('from_date',from); fd.append('to_date',to); fd.append('shop_id',shopId);
    fd.append('_token','<?= htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8") ?>');
    const res = await fetch('/reports/api/fetch',{method:'POST',body:fd});
    const result = await res.json();
    document.getElementById('report-container').style.display='block';
    document.getElementById('report-subtitle').textContent = `Sales from ${from} to ${to}`;
    const tbody = document.getElementById('report-body');
    tbody.innerHTML = '';
    if (!result.data || result.data.length===0) { tbody.innerHTML='<tr><td colspan="3" class="text-center muted">No data found.</td></tr>'; return; }
    result.data.forEach(r => {
        const profit = parseFloat(r.sale_profit||0);
        const profitColor = profit < 0 ? 'color:#dc3545;' : 'color:#28a745;';
        tbody.innerHTML += `<tr><td>${e(r.user_name)}</td><td style="text-align:center;">${e(r.sale_count)}</td><td style="text-align:right;font-weight:600;${profitColor}">${profit.toFixed(2)}</td></tr>`;
    });
}
function e(v){if(v==null)return'';return(v+'').replace(/[&<"'>]/g,m=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m]));}
</script>
