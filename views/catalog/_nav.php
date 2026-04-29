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
        <?php if (can("p_18")): ?>
            <a class="nav-link <?= $currentPath === "/categories" ? "active" : "" ?>" href="/categories">Categories</a>
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
        <?php if (can("p_36")): ?>
            <a class="nav-link <?= $currentPath === "/customers/create" ? "active" : "" ?>" href="/customers/create">Add Customer</a>
        <?php endif; ?>
        <?php if (can("p_37")): ?>
            <a class="nav-link <?= $currentPath === "/customers" ? "active" : "" ?>" href="/customers">Manage Customers</a>
        <?php endif; ?>
        <a class="nav-link" href="/dashboard">Back to Dashboard</a>
    </div>
</aside>
