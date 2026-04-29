<?php $currentPath = parse_url($_SERVER["REQUEST_URI"] ?? "/dashboard", PHP_URL_PATH) ?: "/dashboard"; ?>

<aside class="sidebar">
    <h3>Catalog</h3>
    <div class="nav-group">
        <?php if (can("p_15")): ?>
            <a class="nav-link <?= $currentPath === "/items/create" ? "active" : "" ?>" href="/items/create">Add Item</a>
        <?php endif; ?>
        <?php if (can("p_16")): ?>
            <a class="nav-link <?= $currentPath === "/items" || str_starts_with($currentPath, "/items/") ? "active" : "" ?>" href="/items">Edit Items</a>
        <?php endif; ?>
        <?php if (can("p_17")): ?>
            <a class="nav-link <?= $currentPath === "/items/search" ? "active" : "" ?>" href="/items/search">Search Items</a>
        <?php endif; ?>
        <?php if (can("p_18")): ?>
            <a class="nav-link <?= $currentPath === "/categories" ? "active" : "" ?>" href="/categories">Categories</a>
        <?php endif; ?>
        <?php if (can("p_19")): ?>
            <a class="nav-link <?= $currentPath === "/item-colors" ? "active" : "" ?>" href="/item-colors">Item Colors</a>
        <?php endif; ?>
        <?php if (can("p_22")): ?>
            <a class="nav-link <?= $currentPath === "/operators" ? "active" : "" ?>" href="/operators">Manage Operators</a>
        <?php endif; ?>
        <?php if (can("p_25")): ?>
            <a class="nav-link <?= $currentPath === "/suppliers/create" ? "active" : "" ?>" href="/suppliers/create">Add Supplier</a>
        <?php endif; ?>
        <?php if (can("p_26")): ?>
            <a class="nav-link <?= $currentPath === "/suppliers" ? "active" : "" ?>" href="/suppliers">Supplier List</a>
        <?php endif; ?>
        <?php if (can("p_27")): ?>
            <a class="nav-link <?= $currentPath === "/supplier-accounts" ? "active" : "" ?>" href="/supplier-accounts">Supplier Accounts</a>
        <?php endif; ?>
        <?php if (can("p_28")): ?>
            <a class="nav-link <?= $currentPath === "/supplier-credit-balances" ? "active" : "" ?>" href="/supplier-credit-balances">Supplier Credit Balance</a>
        <?php endif; ?>
        <?php if (can("p_29")): ?>
            <a class="nav-link <?= $currentPath === "/supplier-payments" ? "active" : "" ?>" href="/supplier-payments">Supplier Payment</a>
        <?php endif; ?>
        <?php if (can("r_15")): ?>
            <a class="nav-link <?= $currentPath === "/reports/supplier-payments" ? "active" : "" ?>" href="/reports/supplier-payments">Supplier Payment Report</a>
        <?php endif; ?>
        <?php if (can("p_36")): ?>
            <a class="nav-link <?= $currentPath === "/customers/create" ? "active" : "" ?>" href="/customers/create">Add Customer</a>
        <?php endif; ?>
        <?php if (can("p_37")): ?>
            <a class="nav-link <?= $currentPath === "/customers" ? "active" : "" ?>" href="/customers">Manage Customers</a>
        <?php endif; ?>
        <?php if (can("p_39")): ?>
            <a class="nav-link <?= $currentPath === "/customer-accounts" ? "active" : "" ?>" href="/customer-accounts">Customer Accounts</a>
        <?php endif; ?>
        <?php if (can("p_40")): ?>
            <a class="nav-link <?= $currentPath === "/customer-credit-balances" ? "active" : "" ?>" href="/customer-credit-balances">Customer Credit Balance</a>
        <?php endif; ?>
        <?php if (can("p_41")): ?>
            <a class="nav-link <?= $currentPath === "/customer-payments" ? "active" : "" ?>" href="/customer-payments">Customer Payment</a>
        <?php endif; ?>
        <?php if (can("r_19")): ?>
            <a class="nav-link <?= $currentPath === "/reports/customer-payments" ? "active" : "" ?>" href="/reports/customer-payments">Customer Payment Report</a>
        <?php endif; ?>
        <?php if (can("p_52")): ?>
            <a class="nav-link <?= $currentPath === "/item-alerts" ? "active" : "" ?>" href="/item-alerts">Item Alert Config</a>
        <?php endif; ?>
        <?php if (can("p_59") || can("p_58")): ?>
            <a class="nav-link <?= $currentPath === "/cashier" ? "active" : "" ?>" href="/cashier">Cashier Duty</a>
        <?php endif; ?>
        <a class="nav-link" href="/dashboard">Back to Dashboard</a>
    </div>
</aside>
