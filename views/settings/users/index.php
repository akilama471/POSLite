<?php
$statusMap = [
    1 => "Active",
    2 => "Locked",
    3 => "Blocked",
    4 => "Deleted",
];
?>
<div class="app-shell">
    <header class="topbar">
        <div>
            <div class="brand">Manage Users</div>
            <div class="muted" style="color: #b8c6cf;">Legacy user status actions migrated into MVC</div>
        </div>
        <a class="btn btn-ghost" href="/settings/users/create">Add User</a>
    </header>

    <div class="shell-grid">
        <?php require BASE_PATH . "/views/settings/_nav.php"; ?>
        <main class="page">
            <?php require BASE_PATH . "/views/settings/_flash.php"; ?>

            <section class="panel" style="padding: 22px;">
                <h1 style="margin: 0 0 8px;">System users</h1>
                <p class="section-copy">Reset password, lock, unlock, and soft-delete are now handled through routed POST actions with CSRF protection.</p>
            </section>

            <section class="card" style="margin-top: 18px; overflow-x: auto;">
                <table style="width:100%; border-collapse: collapse;">
                    <thead>
                        <tr style="text-align:left; border-bottom:1px solid var(--border);">
                            <th style="padding:12px;">Username</th>
                            <th style="padding:12px;">Visible Name</th>
                            <th style="padding:12px;">Status</th>
                            <th style="padding:12px;">Shop</th>
                            <th style="padding:12px;">Privilege</th>
                            <th style="padding:12px;">Last Login</th>
                            <th style="padding:12px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr style="border-bottom:1px solid #edf1f4;">
                                <td style="padding:12px;"><?= htmlspecialchars($user["ankaya"], ENT_QUOTES, "UTF-8") ?></td>
                                <td style="padding:12px;"><?= htmlspecialchars((string) ($user["visibledata"] ?? ""), ENT_QUOTES, "UTF-8") ?></td>
                                <td style="padding:12px;"><?= htmlspecialchars($statusMap[(int) $user["statusu"]] ?? "Unknown", ENT_QUOTES, "UTF-8") ?></td>
                                <td style="padding:12px;"><?= htmlspecialchars((string) ($shops[(int) ($user["shop_id"] ?? 0)] ?? "Unassigned"), ENT_QUOTES, "UTF-8") ?></td>
                                <td style="padding:12px;"><?= htmlspecialchars((string) ($privileges[(int) ($user["privilageid"] ?? 0)] ?? "Unknown"), ENT_QUOTES, "UTF-8") ?></td>
                                <td style="padding:12px;"><?= htmlspecialchars((string) ($user["lastlogin"] ?? "-"), ENT_QUOTES, "UTF-8") ?></td>
                                <td style="padding:12px;">
                                    <div style="display:flex; gap:8px; flex-wrap:wrap;">
                                        <?php foreach ([
                                            "reset-password" => "Reset Password",
                                            "unlock" => "Unlock",
                                            "lock" => "Lock",
                                            "delete" => "Delete",
                                        ] as $action => $label): ?>
                                            <form method="POST" action="/settings/users/<?= (int) $user["myid"] ?>/status" onsubmit="return confirm('Proceed with <?= htmlspecialchars($label, ENT_QUOTES, "UTF-8") ?>?');">
                                                <input type="hidden" name="_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8") ?>">
                                                <input type="hidden" name="action" value="<?= htmlspecialchars($action, ENT_QUOTES, "UTF-8") ?>">
                                                <button class="btn btn-primary" type="submit" style="padding:8px 10px;"><?= htmlspecialchars($label, ENT_QUOTES, "UTF-8") ?></button>
                                            </form>
                                        <?php endforeach; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </section>
        </main>
    </div>
</div>
