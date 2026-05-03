<div class="app-shell">
    <header class="topbar">
        <div>
            <div class="brand">System Reports</div>
            <div class="muted" style="color: #b8c6cf;">Migrated from `sys_reports.php`</div>
        </div>
    </header>

    <div class="shell-grid">
        <?php require BASE_PATH . "/views/reports/_nav.php"; ?>
        <main class="page">
            <div class="card stack" style="align-items: center; justify-content: center; min-height: 400px; text-align: center;">
                <div style="font-size: 48px; color: #e1e8ed; margin-bottom: 16px;">
                    <i class="fad fa-chart-bar"></i>
                </div>
                <h2>Reporting Dashboard</h2>
                <p class="muted" style="max-width: 400px; margin-bottom: 24px;">
                    Welcome to the new Reporting Module. Select a report from the sidebar to view metrics, analyze sales, and export data.
                </p>
                <div style="display:flex; gap: 16px;">
                    <?php if (can("p_21")): ?>
                        <a href="/reports/sales/shop" class="btn btn-primary">View Shop Sales</a>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>
