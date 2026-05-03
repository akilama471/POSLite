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
        <div style="margin: 24px 0 4px; font-weight: bold; color: #a1b0b8; text-transform: uppercase; font-size: 0.8em; letter-spacing: 0.05em;">Cashier Reports</div>
        <?php if (can("p_34")): ?>
            <a class="nav-link <?= $path === '/reports/cashier/transactions' ? 'active' : '' ?>" href="/reports/cashier/transactions">Shop Transaction List</a>
        <?php endif; ?>
        <?php if (can("p_35")): ?>
            <a class="nav-link <?= $path === '/reports/cashier/openclose' ? 'active' : '' ?>" href="/reports/cashier/openclose">Shop Open Close Balance</a>
        <?php endif; ?>
        <?php if (can("p_36")): ?>
            <a class="nav-link <?= $path === '/reports/cashier/expenses' ? 'active' : '' ?>" href="/reports/cashier/expenses">Shop Expenses</a>
        <?php endif; ?>
        <?php if (can("p_38")): ?>
            <a class="nav-link <?= $path === '/reports/cashier/profit' ? 'active' : '' ?>" href="/reports/cashier/profit">Shop Sale Profit</a>
        <?php endif; ?>

        <div style="margin: 24px 0 4px; font-weight: bold; color: #a1b0b8; text-transform: uppercase; font-size: 0.8em; letter-spacing: 0.05em;">Product Reports</div>
        <?php if (can("p_6")): ?>
            <a class="nav-link <?= $path === '/reports/product/categories' ? 'active' : '' ?>" href="/reports/product/categories">Product Category List</a>
        <?php endif; ?>
        <?php if (can("p_7")): ?>
            <a class="nav-link <?= $path === '/reports/product/items' ? 'active' : '' ?>" href="/reports/product/items">Product Master List</a>
        <?php endif; ?>
        <?php if (can("p_8")): ?>
            <a class="nav-link <?= $path === '/reports/product/stock' ? 'active' : '' ?>" href="/reports/product/stock">Comprehensive Stock Report</a>
        <?php endif; ?>

        <a class="nav-link" href="/dashboard" style="margin-top: 24px;">Back to Dashboard</a>
    </div>
</aside>
