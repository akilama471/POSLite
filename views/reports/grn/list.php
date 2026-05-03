<div class="app-shell">
    <header class="topbar">
        <div>
            <div class="brand">GRN List</div>
            <div class="muted" style="color: #b8c6cf;">Migrated from `rpt_grn_list.php`</div>
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
                    <h2 style="margin-bottom:4px;">GRN Records</h2>
                    <div class="muted" id="report-subtitle"></div>
                </div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th style="width:18%;">GRN Number</th>
                            <th style="width:22%;">Supplier</th>
                            <th style="width:20%;">Shop</th>
                            <th style="width:16%;">Time</th>
                            <th style="width:12%; text-align:right;">GRN Amount</th>
                            <th style="width:12%; text-align:right;">Paid</th>
                        </tr>
                    </thead>
                    <tbody id="report-body"></tbody>
                    <tfoot style="font-weight:bold; border-top:2px solid #2c3e50;">
                        <tr>
                            <td colspan="4" style="text-align:right;">Totals:</td>
                            <td style="text-align:right;" id="total-grn">0.00</td>
                            <td style="text-align:right;" id="total-paid">0.00</td>
                        </tr>
                    </tfoot>
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
    fd.append('type','grn_list'); fd.append('from_date',from); fd.append('to_date',to); fd.append('shop_id',shopId);
    fd.append('_token','<?= htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8") ?>');
    const res = await fetch('/reports/api/fetch',{method:'POST',body:fd});
    const result = await res.json();
    document.getElementById('report-container').style.display='block';
    document.getElementById('report-subtitle').textContent = `${from} to ${to}`;
    const tbody = document.getElementById('report-body');
    tbody.innerHTML = '';
    let tGrn=0, tPaid=0;
    if (!result.data || result.data.length===0) { tbody.innerHTML='<tr><td colspan="6" class="text-center muted">No GRN records found.</td></tr>'; return; }
    result.data.forEach(r => {
        const amt = parseFloat(r.final_amount||0);
        const paid = parseFloat(r.cash_amount||0) + parseFloat(r.chq_amount||0);
        tGrn += amt; tPaid += paid;
        tbody.innerHTML += `<tr><td>${e(r.grn_refno)}</td><td>${e(r.suppler_name)}</td><td>${e(r.shop_info_name||'—')}</td><td>${e(r.operation_time)}</td><td style="text-align:right;">${amt.toFixed(2)}</td><td style="text-align:right;">${paid.toFixed(2)}</td></tr>`;
    });
    document.getElementById('total-grn').textContent = tGrn.toFixed(2);
    document.getElementById('total-paid').textContent = tPaid.toFixed(2);
}
function e(v){if(v==null)return'';return(v+'').replace(/[&<"'>]/g,m=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m]));}
</script>
