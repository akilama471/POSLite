<?php
$path = parse_url((string) $_SERVER['REQUEST_URI'], PHP_URL_PATH);
?>
<aside class="sidebar">
    <h3>Cashier</h3>
    <div class="nav-group">
        <a class="nav-link <?= $path === '/cashier' ? 'active' : '' ?>" href="/cashier">Duty On / Off</a>
        <?php if (can("p_57")): ?>
            <a class="nav-link <?= $path === '/cashier/expenses' ? 'active' : '' ?>" href="/cashier/expenses">Add Expense</a>
            <a class="nav-link <?= $path === '/cashier/cash-in' ? 'active' : '' ?>" href="/cashier/cash-in">Add Cash In</a>
        <?php endif; ?>
        <a class="nav-link" href="/dashboard">Back to Dashboard</a>
    </div>
</aside>
