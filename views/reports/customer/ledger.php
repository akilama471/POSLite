<div class="app-shell">
    <header class="topbar">
        <div>
            <div class="brand">Customer Ledger Statement</div>
            <div class="muted" style="color: #b8c6cf;">Migrated from `rpt_customer_payment.php`</div>
        </div>
    </header>
    <div class="shell-grid">
        <?php require BASE_PATH . "/views/reports/_nav.php"; ?>
        <main class="page">
            <section class="card stack" style="margin-bottom:24px;">
                <div class="grid" style="grid-template-columns: repeat(3,1fr) auto; gap:16px; align-items:end;">
                    <div class="form-row" style="margin:0;">
                        <label for="customer_id">Select Customer</label>
                        <select class="input" id="customer_id">
                            <option value="0" disabled selected>Choose...</option>
                            <?php foreach ($customers as $c): ?>
                                <option value="<?= (int)$c['recordid'] ?>"><?= htmlspecialchars((string)$c['cus_name'], ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
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
            <section class="card stack print-area" id="report-container" style="display:none;">
                <div style="text-align:center; margin-bottom:24px;">
                    <h2 id="report-title">Customer Ledger</h2>
                    <div class="muted" id="report-subtitle"></div>
                </div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th style="width:20%;">Record Time</th>
                            <th style="width:50%;">Details</th>
                            <th style="width:15%; text-align:right;">Debit</th>
                            <th style="width:15%; text-align:right;">Credit</th>
                        </tr>
                    </thead>
                    <tbody id="report-body"></tbody>
                    <tfoot style="font-weight:bold; border-top:2px solid #2c3e50;">
                        <tr>
                            <td colspan="2" style="text-align:right;">Totals:</td>
                            <td style="text-align:right;" id="total-debit">0.00</td>
                            <td style="text-align:right;" id="total-credit">0.00</td>
                        </tr>
                    </tfoot>
                </table>
            </section>
        </main>
    </div>
</div>
<script>
async function loadReport() {
    const cusId = document.getElementById('customer_id').value;
    if (cusId == 0) { alert('Please select a customer.'); return; }
    const from = document.getElementById('from_date').value;
    const to = document.getElementById('to_date').value;
    const selEl = document.getElementById('customer_id');
    const fd = new FormData();
    fd.append('type','customer_ledger'); fd.append('customer_id',cusId);
    fd.append('from_date',from); fd.append('to_date',to);
    fd.append('_token','<?= htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8") ?>');
    const res = await fetch('/reports/api/fetch',{method:'POST',body:fd});
    const result = await res.json();
    document.getElementById('report-container').style.display='block';
    document.getElementById('report-title').textContent = selEl.options[selEl.selectedIndex].text + ' - Account Ledger';
    document.getElementById('report-subtitle').textContent = `${from} to ${to}`;
    const tbody = document.getElementById('report-body');
    tbody.innerHTML = '';
    let td=0, tc=0;
    if (!result.data || result.data.length===0) { tbody.innerHTML='<tr><td colspan="4" class="text-center muted">No records found.</td></tr>'; return; }
    result.data.forEach(r => {
        td += parseFloat(r.debit||0); tc += parseFloat(r.credit||0);
        tbody.innerHTML += `<tr><td>${e(r.recordtime)}</td><td>${e(r.details)}</td><td style="text-align:right;">${parseFloat(r.debit||0).toFixed(2)}</td><td style="text-align:right;">${parseFloat(r.credit||0).toFixed(2)}</td></tr>`;
    });
    document.getElementById('total-debit').textContent = td.toFixed(2);
    document.getElementById('total-credit').textContent = tc.toFixed(2);
}
function e(v){if(v==null)return'';return(v+'').replace(/[&<"'>]/g,m=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m]));}
</script>
