<div class="app-shell">
    <header class="topbar">
        <div>
            <div class="brand">System Users List</div>
            <div class="muted" style="color: #b8c6cf;">Migrated from `rpt_user_rpt.php`</div>
        </div>
    </header>
    <div class="shell-grid">
        <?php require BASE_PATH . "/views/reports/_nav.php"; ?>
        <main class="page">
            <section class="card stack print-area" style="min-height:400px;">
                <div style="display:flex; justify-content:space-between; margin-bottom:24px;">
                    <div>
                        <h2 style="margin-bottom:4px;">All System Users</h2>
                        <div class="muted">Users, their assigned shops, roles and current status</div>
                    </div>
                    <div class="no-print">
                        <button class="btn btn-secondary" onclick="window.print()">Print Report</button>
                    </div>
                </div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th style="width:8%;">ID</th>
                            <th style="width:22%;">Username</th>
                            <th style="width:20%;">Shop</th>
                            <th style="width:20%;">Permission Role</th>
                            <th style="width:18%;">Last Login</th>
                            <th style="width:12%; text-align:center;">Status</th>
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
const STATUS = {1:'Active',2:'Locked',3:'Blocked',4:'Deleted'};
const STATUS_COLOR = {1:'#28a745',2:'#ffc107',3:'#dc3545',4:'#6c757d'};
document.addEventListener('DOMContentLoaded', () => { loadReport(); });
async function loadReport() {
    const fd = new FormData();
    fd.append('type','user_master');
    fd.append('_token','<?= htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8") ?>');
    const res = await fetch('/reports/api/fetch',{method:'POST',body:fd});
    const result = await res.json();
    const tbody = document.getElementById('report-body');
    tbody.innerHTML = '';
    if (!result.data || result.data.length===0) { tbody.innerHTML='<tr><td colspan="6" class="text-center muted">No users found.</td></tr>'; return; }
    result.data.forEach(r => {
        const st = parseInt(r.statusu||1);
        tbody.innerHTML += `<tr><td>${e(r.myid)}</td><td>${e(r.ankaya)}</td><td>${e(r.shop_info_name||'—')}</td><td>${e(r.privilegename||'—')}</td><td>${e(r.lastlogin||'Never')}</td><td style="text-align:center;"><span style="color:${STATUS_COLOR[st]||'#666'}; font-weight:600;">${STATUS[st]||'Unknown'}</span></td></tr>`;
    });
}
function e(v){if(v==null)return'';return(v+'').replace(/[&<"'>]/g,m=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m]));}
</script>
