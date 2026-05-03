<?php
$path = parse_url((string) $_SERVER['REQUEST_URI'], PHP_URL_PATH);
?>
<aside class="sidebar">
    <h3>System Reports</h3>
    <div class="nav-group">
        
        <div style="margin: 12px 0 4px; font-weight: bold; color: #a1b0b8; text-transform: uppercase; font-size: 0.8em; letter-spacing: 0.05em;">Sales Reports</div>
        <?php if (can("p_21")): ?>
            <a class="nav-link <?= $path === '/reports/sales/shop' ? 'active' : '' ?>" href="/reports/sales/shop">Shop Sale Report</a>
        <?php endif; ?>
        <?php if (can("p_24")): ?>
            <a class="nav-link <?= $path === '/reports/sales/category' ? 'active' : '' ?>" href="/reports/sales/category">Category Wise Sale Report</a>
        <?php endif; ?>

        <!-- Other categories will be added here in future phases -->

        <a class="nav-link" href="/dashboard" style="margin-top: 24px;">Back to Dashboard</a>
    </div>
</aside>
