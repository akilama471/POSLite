<?php $currentPath = parse_url($_SERVER["REQUEST_URI"] ?? "/settings", PHP_URL_PATH) ?: "/settings"; ?>

<aside class="sidebar">
    <h3>Settings</h3>
    <div class="nav-group">
        <?php if (can("p_63")): ?>
            <a class="nav-link <?= $currentPath === "/settings" ? "active" : "" ?>" href="/settings">Overview</a>
        <?php endif; ?>
        <?php if (can("p_64")): ?>
            <a class="nav-link <?= $currentPath === "/settings/users/create" ? "active" : "" ?>" href="/settings/users/create">Add User</a>
        <?php endif; ?>
        <?php if (can("p_65")): ?>
            <a class="nav-link <?= $currentPath === "/settings/users" ? "active" : "" ?>" href="/settings/users">Manage Users</a>
        <?php endif; ?>
        <?php if (can("p_67")): ?>
            <a class="nav-link <?= $currentPath === "/settings/privileges" ? "active" : "" ?>" href="/settings/privileges">Function Permission</a>
        <?php endif; ?>
        <?php if (can("p_68")): ?>
            <a class="nav-link <?= $currentPath === "/settings/user-privileges" ? "active" : "" ?>" href="/settings/user-privileges">User Function Mapping</a>
        <?php endif; ?>
        <a class="nav-link <?= $currentPath === "/settings/profile" ? "active" : "" ?>" href="/settings/profile">My Account</a>
        <a class="nav-link" href="/dashboard">Back to Dashboard</a>
    </div>
</aside>
