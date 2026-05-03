<div class="app-shell">
    <header class="topbar">
        <div>
            <div class="brand">GRN Detail Viewer</div>
            <div class="muted" style="color: #b8c6cf;">Migrated from `rpt_grn_detail.php`</div>
        </div>
    </header>
    <div class="shell-grid">
        <?php require BASE_PATH . "/views/reports/_nav.php"; ?>
        <main class="page">
            <section class="card stack" style="margin-bottom:24px;">
                <div class="grid" style="grid-template-columns: 1fr auto; gap:16px; align-items:end;">
                    <div class="form-row" style="margin:0;">
                        <label for="grn_refno">GRN Reference Number</label>
                        <input type="text" class="input" id="grn_refno" placeholder="e.g. GRN-20260501-001">
                    </div>
                    <div style="display:flex; gap:8px;">
                        <button class="btn btn-primary" onclick="loadReport()">View Detail</button>
                        <button class="btn btn-secondary" onclick="window.print()">Print</button>
                    </div>
                </div>
            </section>
            <section class="card stack print-area" id="report-container" style="display:none;">
                <div id="grn-header" style="margin-bottom:24px;"></div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Category</th>
                            <th>Item Name</th>
                            <th>IMEI / Part No.</th>
                            <th style="text-align:center;">Qty</th>
                            <th style="text-align:right;">Cost</th>
                            <th style="text-align:right;">Low Price</th>
                            <th style="text-align:right;">Sale Price</th>
                            <th>Stock Shop</th>
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
    const grnRefNo = document.getElementById('grn_refno').value.trim();
    if (!grnRefNo) { alert('Please enter a GRN Reference Number.'); return; }
    const fd = new FormData();
    fd.append('type','grn_detail'); fd.append('grn_refno',grnRefNo);
    fd.append('_token','<?= htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8") ?>');
    const res = await fetch('/reports/api/fetch',{method:'POST',body:fd});
    const result = await res.json();
    document.getElementById('report-container').style.display='block';
    if (!result.data || !result.data.header) { document.getElementById('report-body').innerHTML='<tr><td colspan="8" class="text-center muted">GRN not found.</td></tr>'; return; }
    const h = result.data.header;
    document.getElementById('grn-header').innerHTML = `
        <div class="grid" style="grid-template-columns:1fr 1fr; gap:8px;">
            <div><strong>GRN ID:</strong> ${e(h.grn_refno)}</div><div><strong>Time:</strong> ${e(h.operation_time)}</div>
            <div><strong>Supplier:</strong> ${e(h.suppler_name)}</div><div><strong>Shop:</strong> ${e(h.shop_info_name)}</div>
            <div><strong>Amount:</strong> Rs. ${parseFloat(h.amount||0).toFixed(2)}</div><div><strong>Discount:</strong> Rs. ${parseFloat(h.discount_mny||0).toFixed(2)}</div>
            <div><strong>Net Amount:</strong> Rs. ${parseFloat(h.final_amount||0).toFixed(2)}</div><div><strong>Cash Paid:</strong> Rs. ${parseFloat(h.cash_amount||0).toFixed(2)}</div>
        </div><hr style="margin:16px 0;">`;
    const tbody = document.getElementById('report-body');
    tbody.innerHTML = '';
    (result.data.items||[]).forEach(r => {
        tbody.innerHTML += `<tr><td>${e(r.item_category)}</td><td>${e(r.item_name)}</td><td>${e(r.imei_no)}</td><td style="text-align:center;">${e(r.item_qty)}</td><td style="text-align:right;">${e(r.item_costpri)}</td><td style="text-align:right;">${e(r.item_lowpri)}</td><td style="text-align:right;">${e(r.item_sellpri)}</td><td>${e(r.stock_shop_name||'—')}</td></tr>`;
    });
}
function e(v){if(v==null)return'';return(v+'').replace(/[&<"'>]/g,m=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m]));}
</script>
