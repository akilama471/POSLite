<div class="app-shell">
    <header class="topbar">
        <div>
            <div class="brand">Customer Master List</div>
            <div class="muted" style="color: #b8c6cf;">Migrated from `rpt_customer_custlist.php` & `rpt_customer_custbalance.php`</div>
        </div>
    </header>
    <div class="shell-grid">
        <?php require BASE_PATH . "/views/reports/_nav.php"; ?>
        <main class="page">
            <section class="card stack print-area" style="min-height:400px;">
                <div style="display:flex; justify-content:space-between; margin-bottom:24px;">
                    <div>
                        <h2 style="margin-bottom:4px;">All Customers</h2>
                        <div class="muted">Includes contact info and account balances</div>
                    </div>
                    <div class="no-print">
                        <button class="btn btn-secondary" onclick="window.print()">Print Report</button>
                    </div>
                </div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th style="width:8%;">ID</th>
                            <th style="width:22%;">Customer Name</th>
                            <th style="width:28%;">Address</th>
                            <th style="width:15%;">Mobile</th>
                            <th style="width:12%;">Registered</th>
                            <th style="width:15%; text-align:right;">Account Balance</th>
                        </tr>
                    </thead>
                    <tbody id="report-body">
                        <tr><td colspan="6" class="text-center muted">Loading...</td></tr>
                    </tbody>
                </table>
            </section>
        </main>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', () => { loadReport(); });
async function loadReport() {
    const fd = new FormData();
    fd.append('type','customer_master');
    fd.append('_token','<?= htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8") ?>');
    const res = await fetch('/reports/api/fetch',{method:'POST',body:fd});
    const result = await res.json();
    const tbody = document.getElementById('report-body');
    tbody.innerHTML = '';
    if (!result.data || result.data.length===0) { tbody.innerHTML='<tr><td colspan="6" class="text-center muted">No customers found.</td></tr>'; return; }
    result.data.forEach(r => {
        const bal = parseFloat(r.accbalance||0);
        const balColor = bal < 0 ? 'color:#dc3545;' : '';
        tbody.innerHTML += `<tr><td>${e(r.recordid)}</td><td>${e(r.cus_name)}</td><td>${e(r.cus_addr)}</td><td>${e(r.cus_mobile)}</td><td>${e(r.add_time)}</td><td style="text-align:right;${balColor}">Rs. ${bal.toFixed(2)}</td></tr>`;
    });
}
function e(v){if(v==null)return'';return(v+'').replace(/[&<"'>]/g,m=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m]));}
</script>
