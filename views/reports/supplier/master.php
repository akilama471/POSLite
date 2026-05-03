<div class="app-shell">
    <header class="topbar">
        <div>
            <div class="brand">Supplier Master List</div>
            <div class="muted" style="color: #b8c6cf;">Migrated from `rpt_supply_supplylist.php` & `rpt_supply_supplybalance.php`</div>
        </div>
    </header>
    <div class="shell-grid">
        <?php require BASE_PATH . "/views/reports/_nav.php"; ?>
        <main class="page">
            <section class="card stack print-area" style="min-height: 400px;">
                <div style="display:flex; justify-content: space-between; margin-bottom: 24px;">
                    <div>
                        <h2 style="margin-bottom: 4px;">All Suppliers</h2>
                        <div class="muted">Includes balances and contact info</div>
                    </div>
                    <div class="no-print">
                        <button class="btn btn-secondary" onclick="window.print()">Print Report</button>
                    </div>
                </div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th style="width:8%;">ID</th>
                            <th style="width:22%;">Supplier Name</th>
                            <th style="width:25%;">Address</th>
                            <th style="width:15%;">Mobile</th>
                            <th style="width:15%; text-align:right;">Cash Credit Bal.</th>
                            <th style="width:15%; text-align:right;">Account Bal.</th>
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
    fd.append('type', 'supplier_master');
    fd.append('_token', '<?= htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8") ?>');
    const res = await fetch('/reports/api/fetch', { method: 'POST', body: fd });
    const result = await res.json();
    const tbody = document.getElementById('report-body');
    tbody.innerHTML = '';
    if (!result.data || result.data.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center muted">No suppliers found.</td></tr>';
        return;
    }
    result.data.forEach(r => {
        const tr = document.createElement('tr');
        tr.innerHTML = `<td>${e(r.supplierid)}</td><td>${e(r.supplier_name)}</td><td>${e(r.supplier_address)}</td><td>${e(r.supplier_mobile)}</td><td style="text-align:right;">Rs. ${parseFloat(r.cash_credit_balance||0).toFixed(2)}</td><td style="text-align:right;">Rs. ${parseFloat(r.accbalance||0).toFixed(2)}</td>`;
        tbody.appendChild(tr);
    });
}
function e(v) { if (v == null) return ''; return (v+'').replace(/[&<"'>]/g, m=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m])); }
</script>
