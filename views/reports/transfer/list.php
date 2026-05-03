<div class="app-shell">
    <header class="topbar">
        <div>
            <div class="brand">Stock Transfer List</div>
            <div class="muted" style="color: #b8c6cf;">Migrated from `rpt_stocktrans_list.php`</div>
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
                        <label for="shop_id">From Shop</label>
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
                    <h2 style="margin-bottom:4px;">Stock Transfer Records</h2>
                    <div class="muted" id="report-subtitle"></div>
                </div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th style="width:18%;">Transfer ID</th>
                            <th style="width:18%;">Record Time</th>
                            <th style="width:20%;">From Shop</th>
                            <th style="width:20%;">Operator</th>
                            <th style="width:12%; text-align:right;">Value</th>
                            <th style="width:12%; text-align:center;">Status</th>
                        </tr>
                    </thead>
                    <tbody id="report-body"></tbody>
                </table>
            </section>
        </main>
    </div>
</div>
<script>
const TRANS_STATUS = {1:'Prepared',2:'Transferred',3:'Error',4:'Finished',5:'Error Finished'};
const TRANS_COLOR = {1:'#ffc107',2:'#17a2b8',3:'#dc3545',4:'#28a745',5:'#6c757d'};
async function loadReport() {
    const from = document.getElementById('from_date').value;
    const to = document.getElementById('to_date').value;
    const shopId = document.getElementById('shop_id').value;
    const fd = new FormData();
    fd.append('type','transfer_list'); fd.append('from_date',from); fd.append('to_date',to); fd.append('shop_id',shopId);
    fd.append('_token','<?= htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8") ?>');
    const res = await fetch('/reports/api/fetch',{method:'POST',body:fd});
    const result = await res.json();
    document.getElementById('report-container').style.display='block';
    document.getElementById('report-subtitle').textContent = `${from} to ${to}`;
    const tbody = document.getElementById('report-body');
    tbody.innerHTML = '';
    if (!result.data || result.data.length===0) { tbody.innerHTML='<tr><td colspan="6" class="text-center muted">No transfer records found.</td></tr>'; return; }
    result.data.forEach(r => {
        const st = parseInt(r.trans_status||1);
        tbody.innerHTML += `<tr>
            <td>${e(r.trans_id)}</td>
            <td>${e(r.record_time)}</td>
            <td>${e(r.from_shop_name||'—')}</td>
            <td>${e(r.operator_name||'—')}</td>
            <td style="text-align:right;">${parseFloat(r.total_cost||0).toFixed(2)}</td>
            <td style="text-align:center;"><span style="color:${TRANS_COLOR[st]||'#666'};font-weight:600;">${TRANS_STATUS[st]||'Unknown'}</span></td>
        </tr>`;
    });
}
function e(v){if(v==null)return'';return(v+'').replace(/[&<"'>]/g,m=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m]));}
</script>
