<?php
// Job status map shared across templates
$JOB_STATUS = [
    1 => ['label' => 'In Repair', 'color' => '#ffc107'],
    2 => ['label' => 'Reopened', 'color' => '#fd7e14'],
    3 => ['label' => 'Tech Finished', 'color' => '#17a2b8'],
    4 => ['label' => 'Bill Created', 'color' => '#6f42c1'],
    5 => ['label' => 'Paid & Delivered', 'color' => '#28a745'],
    6 => ['label' => 'Return Money', 'color' => '#dc3545'],
];
?>
<div class="app-shell">
    <header class="topbar">
        <div>
            <div class="brand">Repair Job List</div>
            <div class="muted" style="color: #b8c6cf;">Migrated from `rpt_repair_joblist.php` + status filters</div>
        </div>
    </header>
    <div class="shell-grid">
        <?php require BASE_PATH . "/views/reports/_nav.php"; ?>
        <main class="page">
            <section class="card stack" style="margin-bottom:24px;">
                <div class="grid" style="grid-template-columns: repeat(4,1fr) auto; gap:16px; align-items:end;">
                    <div class="form-row" style="margin:0;">
                        <label for="from_date">From Date</label>
                        <input type="date" class="input" id="from_date" value="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="form-row" style="margin:0;">
                        <label for="to_date">To Date</label>
                        <input type="date" class="input" id="to_date" value="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="form-row" style="margin:0;">
                        <label for="shop_id">Shop</label>
                        <select class="input" id="shop_id">
                            <option value="-1">All Shops</option>
                            <?php foreach ($shops as $shop): ?>
                                <?php if ($shop['shopid'] > 0): ?>
                                    <option value="<?= (int)$shop['shopid'] ?>"><?= htmlspecialchars((string)$shop['shop_info_name'], ENT_QUOTES, 'UTF-8') ?></option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-row" style="margin:0;">
                        <label for="status_filter">Status Filter</label>
                        <select class="input" id="status_filter">
                            <option value="">All Statuses</option>
                            <option value="1">In Repair</option>
                            <option value="2">Reopened</option>
                            <option value="4">Bill Created</option>
                            <option value="5">Paid & Delivered</option>
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
                    <h2 style="margin-bottom:4px;">Repair Jobs</h2>
                    <div class="muted" id="report-subtitle"></div>
                </div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th style="width:10%;">Job ID</th>
                            <th style="width:18%;">Shop</th>
                            <th style="width:20%;">Customer</th>
                            <th style="width:15%;">IMEI No.</th>
                            <th style="width:22%;">Fault</th>
                            <th style="width:10%;">Received</th>
                            <th style="width:10%; text-align:center;">Status</th>
                        </tr>
                    </thead>
                    <tbody id="report-body"></tbody>
                </table>
            </section>
        </main>
    </div>
</div>
<script>
const JOB_STATUS = {1:{l:'In Repair',c:'#ffc107'},2:{l:'Reopened',c:'#fd7e14'},3:{l:'Tech Finished',c:'#17a2b8'},4:{l:'Bill Created',c:'#6f42c1'},5:{l:'Paid & Delivered',c:'#28a745'},6:{l:'Return Money',c:'#dc3545'}};
async function loadReport() {
    const from = document.getElementById('from_date').value;
    const to = document.getElementById('to_date').value;
    const shopId = document.getElementById('shop_id').value;
    const status = document.getElementById('status_filter').value;
    const fd = new FormData();
    fd.append('type','repair_jobs'); fd.append('from_date',from); fd.append('to_date',to); fd.append('shop_id',shopId);
    if (status !== '') fd.append('status_filter', status);
    fd.append('_token','<?= htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8") ?>');
    const res = await fetch('/reports/api/fetch',{method:'POST',body:fd});
    const result = await res.json();
    document.getElementById('report-container').style.display='block';
    document.getElementById('report-subtitle').textContent = `${from} to ${to}`;
    const tbody = document.getElementById('report-body');
    tbody.innerHTML = '';
    if (!result.data || result.data.length===0) { tbody.innerHTML='<tr><td colspan="7" class="text-center muted">No jobs found.</td></tr>'; return; }
    result.data.forEach(r => {
        const st = parseInt(r.job_status||1);
        const statusInfo = JOB_STATUS[st] || {l:'Unknown',c:'#666'};
        tbody.innerHTML += `<tr>
            <td>${e(r.job_number)}</td>
            <td>${e(r.shop_info_name||'—')}</td>
            <td>${e(r.job_cus_name)} (${e(r.job_cus_contac)})</td>
            <td>${e(r.job_cus_imei)}</td>
            <td>${e(r.job_fault)}</td>
            <td>${e(r.job_add_date)}</td>
            <td style="text-align:center;"><span style="color:${statusInfo.c};font-weight:600;">${statusInfo.l}</span></td>
        </tr>`;
    });
}
function e(v){if(v==null)return'';return(v+'').replace(/[&<"'>]/g,m=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m]));}
</script>
