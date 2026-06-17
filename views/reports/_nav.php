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
            <a class="nav-link <?= $path === '/reports/sales/bestsale' ? 'active' : '' ?>" href="/reports/sales/bestsale">Best Selling Items</a>
            <a class="nav-link <?= $path === '/reports/sales/itemwise' ? 'active' : '' ?>" href="/reports/sales/itemwise">Item Wise Sale</a>
            <a class="nav-link <?= $path === '/reports/sales/itemcatwise' ? 'active' : '' ?>" href="/reports/sales/itemcatwise">Item + Category Wise Sale</a>
            <a class="nav-link <?= $path === '/reports/sales/overcost' ? 'active' : '' ?>" href="/reports/sales/overcost">Over-Cost Sales</a>
            <a class="nav-link <?= $path === '/reports/sales/undercost' ? 'active' : '' ?>" href="/reports/sales/undercost">Under-Cost Sales</a>
            <a class="nav-link <?= $path === '/reports/sales/phonesale' ? 'active' : '' ?>" href="/reports/sales/phonesale">Phone / IMEI Sale</a>
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
            <a class="nav-link <?= $path === '/reports/cashier/cashin' ? 'active' : '' ?>" href="/reports/cashier/cashin">Cash-In Report</a>
            <a class="nav-link <?= $path === '/reports/cashier/accwise_expenses' ? 'active' : '' ?>" href="/reports/cashier/accwise_expenses">Account-Wise Expenses</a>
            <a class="nav-link <?= $path === '/reports/cashier/operation' ? 'active' : '' ?>" href="/reports/cashier/operation">Cashier Operation Log</a>
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

        <div style="margin: 24px 0 4px; font-weight: bold; color: #a1b0b8; text-transform: uppercase; font-size: 0.8em; letter-spacing: 0.05em;">Supplier Reports</div>
        <?php if (can("p_40")): ?>
            <a class="nav-link <?= $path === '/reports/supplier/master' ? 'active' : '' ?>" href="/reports/supplier/master">Supplier Master List</a>
        <?php endif; ?>
        <?php if (can("p_41")): ?>
            <a class="nav-link <?= $path === '/reports/supplier/ledger' ? 'active' : '' ?>" href="/reports/supplier/ledger">Supplier Ledger Statement</a>
        <?php endif; ?>

        <div style="margin: 24px 0 4px; font-weight: bold; color: #a1b0b8; text-transform: uppercase; font-size: 0.8em; letter-spacing: 0.05em;">Customer Reports</div>
        <?php if (can("p_43")): ?>
            <a class="nav-link <?= $path === '/reports/customer/master' ? 'active' : '' ?>" href="/reports/customer/master">Customer Master List</a>
        <?php endif; ?>
        <?php if (can("p_44")): ?>
            <a class="nav-link <?= $path === '/reports/customer/ledger' ? 'active' : '' ?>" href="/reports/customer/ledger">Customer Ledger Statement</a>
        <?php endif; ?>

        <div style="margin: 24px 0 4px; font-weight: bold; color: #a1b0b8; text-transform: uppercase; font-size: 0.8em; letter-spacing: 0.05em;">User Reports</div>
        <?php if (can("p_50")): ?>
            <a class="nav-link <?= $path === '/reports/user/master' ? 'active' : '' ?>" href="/reports/user/master">System Users List</a>
        <?php endif; ?>
        <?php if (can("p_51")): ?>
            <a class="nav-link <?= $path === '/reports/user/sales' ? 'active' : '' ?>" href="/reports/user/sales">User Sales Report</a>
        <?php endif; ?>
        <?php if (can("p_52")): ?>
            <a class="nav-link <?= $path === '/reports/user/security' ? 'active' : '' ?>" href="/reports/user/security">Security Audit Log</a>
        <?php endif; ?>

        <div style="margin: 24px 0 4px; font-weight: bold; color: #a1b0b8; text-transform: uppercase; font-size: 0.8em; letter-spacing: 0.05em;">GRN Reports</div>
        <?php if (can("p_60")): ?>
            <a class="nav-link <?= $path === '/reports/grn/list' ? 'active' : '' ?>" href="/reports/grn/list">GRN List</a>
            <a class="nav-link <?= $path === '/reports/grn/detail' ? 'active' : '' ?>" href="/reports/grn/detail">GRN Detail Viewer</a>
            <a class="nav-link <?= $path === '/reports/grn/reorder' ? 'active' : '' ?>" href="/reports/grn/reorder">Reorder Alert Report</a>
            <a class="nav-link <?= $path === '/reports/grn/returns' ? 'active' : '' ?>" href="/reports/grn/returns">Stock Return List</a>
            <a class="nav-link <?= $path === '/reports/grn/return_detail' ? 'active' : '' ?>" href="/reports/grn/return_detail">Return Document Detail</a>
            <a class="nav-link <?= $path === '/reports/grn/discard' ? 'active' : '' ?>" href="/reports/grn/discard">Discard Log</a>
            <a class="nav-link <?= $path === '/reports/grn/transfer_bin' ? 'active' : '' ?>" href="/reports/grn/transfer_bin">Transfer Bin Items</a>
            <a class="nav-link <?= $path === '/reports/grn/sales_return_bin' ? 'active' : '' ?>" href="/reports/grn/sales_return_bin">Customer Sales Return Bin</a>
            <a class="nav-link <?= $path === '/reports/grn/supplier_wise' ? 'active' : '' ?>" href="/reports/grn/supplier_wise">Supplier-Wise GRN</a>
        <?php endif; ?>

        <div style="margin: 24px 0 4px; font-weight: bold; color: #a1b0b8; text-transform: uppercase; font-size: 0.8em; letter-spacing: 0.05em;">Inventory Logs</div>
        <?php if (can("p_61")): ?>
            <a class="nav-link <?= $path === '/reports/logs/price-edit' ? 'active' : '' ?>" href="/reports/logs/price-edit">Price Edit Log</a>
            <a class="nav-link <?= $path === '/reports/logs/stock-edit' ? 'active' : '' ?>" href="/reports/logs/stock-edit">Stock Edit Log</a>
            <a class="nav-link <?= $path === '/reports/logs/stock-delete' ? 'active' : '' ?>" href="/reports/logs/stock-delete">Stock Delete Log</a>
        <?php endif; ?>

        <div style="margin: 24px 0 4px; font-weight: bold; color: #a1b0b8; text-transform: uppercase; font-size: 0.8em; letter-spacing: 0.05em;">Repair Reports</div>
        <?php if (can("p_30")): ?>
            <a class="nav-link <?= $path === '/reports/repair/jobs' ? 'active' : '' ?>" href="/reports/repair/jobs">Repair Job List</a>
            <a class="nav-link <?= $path === '/reports/repair/detail' ? 'active' : '' ?>" href="/reports/repair/detail">Job Detail Viewer</a>
        <?php endif; ?>

        <div style="margin: 24px 0 4px; font-weight: bold; color: #a1b0b8; text-transform: uppercase; font-size: 0.8em; letter-spacing: 0.05em;">Stock Transfer</div>
        <?php if (can("p_63")): ?>
            <a class="nav-link <?= $path === '/reports/transfer/list' ? 'active' : '' ?>" href="/reports/transfer/list">Transfer List</a>
            <a class="nav-link <?= $path === '/reports/transfer/detail' ? 'active' : '' ?>" href="/reports/transfer/detail">Transfer Detail</a>
            <a class="nav-link <?= $path === '/reports/transfer/logcheck' ? 'active' : '' ?>" href="/reports/transfer/logcheck">Item Log Check</a>
        <?php endif; ?>

        <a class="nav-link" href="/dashboard" style="margin-top: 24px;">Back to Dashboard</a>
    </div>
</aside>
