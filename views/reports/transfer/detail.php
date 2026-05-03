<div class="app-shell">
    <header class="topbar">
        <div>
            <div class="brand">Transfer Detail Viewer</div>
            <div class="muted" style="color: #b8c6cf;">Migrated from `rpt_stocktrans_detail.php`</div>
        </div>
    </header>
    <div class="shell-grid">
        <?php require BASE_PATH . "/views/reports/_nav.php"; ?>
        <main class="page">
            <section class="card stack" style="margin-bottom:24px;">
                <div class="grid" style="grid-template-columns: 1fr auto; gap:16px; align-items:end;">
                    <div class="form-row" style="margin:0;">
                        <label for="trans_id">Transfer ID</label>
                        <input type="text" class="input" id="trans_id" placeholder="e.g. TRANS-20260501-001">
                    </div>
                    <div style="display:flex; gap:8px;">
                        <button class="btn btn-primary" onclick="loadReport()">View Detail</button>
                        <button class="btn btn-secondary" onclick="window.print()">Print</button>
                    </div>
                </div>
            </section>
            <section class="card stack print-area" id="report-container" style="display:none;">
                <div id="trans-header" style="margin-bottom:24px;"></div>
                <h3 style="margin-bottom:12px;">Transferred Items</h3>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th style="width:35%;">Item Name</th>
                            <th style="width:15%;">Code / IMEI</th>
                            <th style="width:10%; text-align:center;">Qty</th>
                            <th style="width:12%; text-align:right;">Cost</th>
                            <th style="width:12%; text-align:right;">Value</th>
                            <th style="width:16%; text-align:right;">Transfer To</th>
                        </tr>
                    </thead>
                    <tbody id="report-body"></tbody>
                    <tfoot style="font-weight:bold; border-top:2px solid #2c3e50;">
                        <tr>
                            <td colspan="4" style="text-align:right;">Total Transfer Value:</td>
                            <td style="text-align:right;" id="total-value">0.00</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </section>
        </main>
    </div>
</div>
<script>
const TRANS_STATUS = {1:'Prepared',2:'Transferred',3:'Error',4:'Finished',5:'Error Finished'};
async function loadReport() {
    const transId = document.getElementById('trans_id').value.trim();
    if (!transId) { alert('Please enter a Transfer ID.'); return; }
    const fd = new FormData();
    fd.append('type','transfer_detail'); fd.append('trans_id',transId);
    fd.append('_token','<?= htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8") ?>');
    const res = await fetch('/reports/api/fetch',{method:'POST',body:fd});
    const result = await res.json();
    document.getElementById('report-container').style.display='block';
    if (!result.data || !result.data.header) {
        document.getElementById('trans-header').innerHTML='<p class="muted">Transfer not found.</p>';
        return;
    }
    const h = result.data.header;
    const st = TRANS_STATUS[parseInt(h.trans_status||1)]||'Unknown';
    document.getElementById('trans-header').innerHTML = `
        <div class="grid" style="grid-template-columns:1fr 1fr; gap:8px;">
            <div><strong>Transfer ID:</strong> ${e(h.trans_id)}</div><div><strong>Status:</strong> ${e(st)}</div>
            <div><strong>From Shop:</strong> ${e(h.from_shop_name||'—')}</div><div><strong>Record Time:</strong> ${e(h.record_time)}</div>
            <div><strong>Apply User:</strong> ${e(h.operator_name||'—')}</div><div><strong>Transfer User:</strong> ${e(h.transfer_user_name||'—')}</div>
            <div><strong>Sending Time:</strong> ${e(h.sending_time||'—')}</div><div><strong>Total Value:</strong> Rs. ${parseFloat(h.total_cost||0).toFixed(2)}</div>
        </div><hr style="margin:16px 0;">`;
    const tbody = document.getElementById('report-body');
    tbody.innerHTML = '';
    let total = 0;
    if (!result.data.items || result.data.items.length===0) { tbody.innerHTML='<tr><td colspan="6" class="text-center muted">No items found.</td></tr>'; return; }
    result.data.items.forEach(r => {
        const val = parseFloat(r.transfer_value||0);
        total += val;
        tbody.innerHTML += `<tr><td>${e(r.Item_name)}</td><td>${e(r.code)}</td><td style="text-align:center;">${e(r.stock_count)}</td><td style="text-align:right;">${parseFloat(r.part_cost||0).toFixed(2)}</td><td style="text-align:right;">${val.toFixed(2)}</td><td style="text-align:right;">${e(r.to_shop_name||'—')}</td></tr>`;
    });
    document.getElementById('total-value').textContent = total.toFixed(2);
}
function e(v){if(v==null)return'';return(v+'').replace(/[&<"'>]/g,m=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m]));}
</script>
