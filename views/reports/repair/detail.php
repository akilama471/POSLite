<div class="app-shell">
    <header class="topbar">
        <div>
            <div class="brand">Repair Job Detail Viewer</div>
            <div class="muted" style="color: #b8c6cf;">Migrated from `rpt_repair_fulllog.php`</div>
        </div>
    </header>
    <div class="shell-grid">
        <?php require BASE_PATH . "/views/reports/_nav.php"; ?>
        <main class="page">
            <section class="card stack" style="margin-bottom:24px;">
                <div class="grid" style="grid-template-columns: 1fr auto; gap:16px; align-items:end;">
                    <div class="form-row" style="margin:0;">
                        <label for="job_number">Repair Job Number</label>
                        <input type="text" class="input" id="job_number" placeholder="e.g. JOB-20260501-001">
                    </div>
                    <div style="display:flex; gap:8px;">
                        <button class="btn btn-primary" onclick="loadReport()">View Detail</button>
                        <button class="btn btn-secondary" onclick="window.print()">Print</button>
                    </div>
                </div>
            </section>
            <section class="card stack print-area" id="report-container" style="display:none;">
                <div id="job-header" style="margin-bottom:24px;"></div>
                <h3 style="margin-bottom:12px;">Parts & Operations Log</h3>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th style="width:25%;">Operation / Part</th>
                            <th style="width:15%;">Stock ID</th>
                            <th style="width:15%;">Warranty</th>
                            <th style="width:15%; text-align:right;">Sell Price</th>
                            <th style="width:15%;">Operator</th>
                            <th style="width:15%;">Time</th>
                        </tr>
                    </thead>
                    <tbody id="report-body"></tbody>
                </table>
            </section>
        </main>
    </div>
</div>
<script>
const JOB_STATUS = {1:'In Repair',2:'Reopened',3:'Tech Finished',4:'Bill Created',5:'Paid & Delivered',6:'Return Money'};
async function loadReport() {
    const jobNo = document.getElementById('job_number').value.trim();
    if (!jobNo) { alert('Please enter a Job Number.'); return; }
    const fd = new FormData();
    fd.append('type','repair_job_detail'); fd.append('job_number',jobNo);
    fd.append('_token','<?= htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8") ?>');
    const res = await fetch('/reports/api/fetch',{method:'POST',body:fd});
    const result = await res.json();
    document.getElementById('report-container').style.display='block';
    if (!result.data || !result.data.header) {
        document.getElementById('job-header').innerHTML = '<p class="muted">Job not found.</p>';
        return;
    }
    const h = result.data.header;
    const st = JOB_STATUS[parseInt(h.job_status||1)] || 'Unknown';
    document.getElementById('job-header').innerHTML = `
        <div class="grid" style="grid-template-columns:1fr 1fr; gap:8px; margin-bottom:16px;">
            <div><strong>Job ID:</strong> ${e(h.job_number)}</div><div><strong>Status:</strong> ${e(st)}</div>
            <div><strong>Customer:</strong> ${e(h.job_cus_name)}</div><div><strong>Contact:</strong> ${e(h.job_cus_contac)}</div>
            <div><strong>IMEI:</strong> ${e(h.job_cus_imei)}</div><div><strong>Fault:</strong> ${e(h.job_fault)}</div>
            <div><strong>Received:</strong> ${e(h.job_add_date)}</div><div><strong>Handover:</strong> ${e(h.handover_time||'—')}</div>
            <div><strong>Advance:</strong> Rs. ${parseFloat(h.job_payment_adv||0).toFixed(2)}</div><div><strong>Total Bill:</strong> Rs. ${parseFloat(h.job_payment_total||0).toFixed(2)}</div>
            <div><strong>Parts Cost:</strong> Rs. ${parseFloat(h.job_partcost||0).toFixed(2)}</div><div><strong>Repair Charge:</strong> Rs. ${parseFloat(h.job_repaircost||0).toFixed(2)}</div>
            <div><strong>Shop:</strong> ${e(h.shop_info_name||'—')}</div>
        </div><hr style="margin:16px 0;">`;
    const tbody = document.getElementById('report-body');
    tbody.innerHTML = '';
    if (!result.data.log || result.data.log.length===0) { tbody.innerHTML='<tr><td colspan="6" class="text-center muted">No parts/operations logged.</td></tr>'; return; }
    result.data.log.forEach(r => {
        tbody.innerHTML += `<tr><td>${e(r.a_item_name)}</td><td>${e(r.a_item_gen_refno)}</td><td>${e(r.warranty_span)} ${e(r.warranty_type)}</td><td style="text-align:right;">${parseFloat(r.item_sell_price||0).toFixed(2)}</td><td>${e(r.operator_name||'—')}</td><td>${e(r.record_time)}</td></tr>`;
    });
}
function e(v){if(v==null)return'';return(v+'').replace(/[&<"'>]/g,m=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m]));}
</script>
