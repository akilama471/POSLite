<div class="app-shell">
    <header class="topbar">
        <div>
            <div class="brand">Product Category List</div>
            <div class="muted" style="color: #b8c6cf;">Migrated from `rpt_product_allcatrpt.php`</div>
        </div>
    </header>

    <div class="shell-grid">
        <?php require BASE_PATH . "/views/reports/_nav.php"; ?>
        <main class="page">
            <section class="card stack print-area" style="min-height: 400px;">
                <div style="display:flex; justify-content: space-between; margin-bottom: 24px;">
                    <div>
                        <h2 style="margin-bottom: 4px;">System Product Categories</h2>
                        <div class="muted">All registered item categories</div>
                    </div>
                    <div class="no-print">
                        <button class="btn btn-secondary" onclick="window.print()">Print Report</button>
                    </div>
                </div>
                
                <table class="data-table">
                    <thead>
                        <tr>
                            <th style="width: 20%;">Category ID</th>
                            <th style="width: 50%;">Category Name</th>
                            <th style="width: 30%;">Added Date</th>
                        </tr>
                    </thead>
                    <tbody id="report-body">
                        <tr><td colspan="3" class="text-center muted">Loading...</td></tr>
                    </tbody>
                </table>
            </section>
        </main>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    loadReport();
});

async function loadReport() {
    try {
        const formData = new FormData();
        formData.append('type', 'product_categories');
        formData.append('_token', '<?= htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8") ?>');

        const response = await fetch('/reports/api/fetch', {
            method: 'POST',
            body: formData
        });
        
        if (response.ok) {
            const result = await response.json();
            if (result.status === "error") {
                alert(result.message);
                return;
            }

            renderTable(result.data);
        }
    } catch (e) {
        console.error('Failed to load report data', e);
    }
}

function renderTable(data) {
    const tbody = document.getElementById('report-body');
    tbody.innerHTML = '';

    if (data.length === 0) {
        tbody.innerHTML = '<tr><td colspan="3" class="text-center muted">No categories found.</td></tr>';
        return;
    }

    data.forEach(row => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>${escapeHtml(row.catid)}</td>
            <td>${escapeHtml(row.catname)}</td>
            <td>${escapeHtml(row.eff_date)}</td>
        `;
        tbody.appendChild(tr);
    });
}

function escapeHtml(unsafe) {
    if (unsafe == null) return '';
    return (unsafe + '').replace(/[&<"'>]/g, function (m) {
        return {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        }[m];
    });
}
</script>
