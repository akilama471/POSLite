<div class="app-shell">
    <header class="topbar">
        <div>
            <div class="brand">Stock Edit Log</div>
            <div class="muted" style="color: #b8c6cf;">Migrated from `rpt_logs_stockedit.php`</div>
        </div>
    </header>
    <div class="shell-grid">
        <?php require BASE_PATH . "/views/reports/_nav.php"; ?>
        <main class="page">
            <section class="card stack" style="margin-bottom:24px;">
                <div class="grid" style="grid-template-columns: 1fr 1fr auto; gap:16px; align-items:end;">
                    <div class="form-row" style="margin:0;">
                        <label for="from_date">From Date</label>
                        <input type="date" class="input" id="from_date" value="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="form-row" style="margin:0;">
                        <label for="to_date">To Date</label>
                        <input type="date" class="input" id="to_date" value="<?= date('Y-m-d') ?>">
                    </div>
                    <div style="display:flex; gap:8px;">
                        <button class="btn btn-primary" onclick="loadReport()">Load</button>
                        <button class="btn btn-secondary" onclick="window.print()">Print</button>
                    </div>
                </div>
            </section>
            <section class="card stack print-area" id="report-container" style="display:none; min-height:400px;">
                <div style="text-align:center; margin-bottom:24px;">
                    <h2 style="margin-bottom:4px;">Stock Edit History</h2>
                    <div class="muted" id="report-subtitle"></div>
                </div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th style="width:20%;">Shop</th>
                            <th style="width:15%;">Operator</th>
                            <th style="width:35%;">System Remark</th>
                            <th style="width:15%;">Reason</th>
                            <th style="width:15%; text-align:center;">Time</th>
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
    const fd = new FormData();
    fd.append('type','logs_stock_edit'); fd.append('from_date',from); fd.append('to_date',to);
    fd.append('_token','<?= htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8") ?>');
    const res = await fetch('/reports/api/fetch',{method:'POST',body:fd});
    const result = await res.json();
    document.getElementById('report-container').style.display='block';
    document.getElementById('report-subtitle').textContent = `Stock edits from ${from} to ${to}`;
    const tbody = document.getElementById('report-body');
    tbody.innerHTML = '';
    if (!result.data || result.data.length===0) { tbody.innerHTML='<tr><td colspan="5" class="text-center muted">No stock edits found.</td></tr>'; return; }
    result.data.forEach(r => {
        tbody.innerHTML += `<tr><td>${e(r.shop_info_name||'—')}</td><td>${e(r.operator_name||'—')}</td><td>${e(r.sys_remark)}</td><td>${e(r.reason)}</td><td style="text-align:center;">${e(r.operation_time)}</td></tr>`;
    });
}
function e(v){if(v==null)return'';return(v+'').replace(/[&<"'>]/g,m=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m]));}
</script>
