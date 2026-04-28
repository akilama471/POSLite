<div class="app-shell">
    <header class="topbar">
        <div>
            <div class="brand">My Account</div>
            <div class="muted" style="color: #b8c6cf;">Profile and password management migrated from the legacy account page</div>
        </div>
    </header>

    <div class="shell-grid">
        <?php require BASE_PATH . "/views/settings/_nav.php"; ?>
        <main class="page">
            <?php require BASE_PATH . "/views/settings/_flash.php"; ?>

            <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));">
                <section class="card">
                    <h1 style="margin-top:0;">Profile details</h1>
                    <form method="POST" action="/settings/profile">
                        <input type="hidden" name="_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8") ?>">

                        <div class="form-row">
                            <label>Login ID</label>
                            <input class="input" value="<?= htmlspecialchars((string) ($user["ankaya"] ?? ""), ENT_QUOTES, "UTF-8") ?>" readonly>
                        </div>

                        <div class="form-row">
                            <label>Shop</label>
                            <input class="input" value="<?= htmlspecialchars((string) (($shop["shop_info_name"] ?? $shop["shopname"] ?? "Unknown")), ENT_QUOTES, "UTF-8") ?>" readonly>
                        </div>

                        <div class="form-row">
                            <label for="display_name">Visible Name</label>
                            <input class="input" id="display_name" name="display_name" value="<?= htmlspecialchars((string) ($user["visibledata"] ?? ""), ENT_QUOTES, "UTF-8") ?>" required>
                        </div>

                        <div class="form-row">
                            <label for="email">Email</label>
                            <input class="input" id="email" name="email" value="<?= htmlspecialchars((string) ($user["email"] ?? ""), ENT_QUOTES, "UTF-8") ?>">
                        </div>

                        <div class="form-row">
                            <label for="mobile">Phone</label>
                            <input class="input" id="mobile" name="mobile" value="<?= htmlspecialchars((string) ($user["mobile"] ?? ""), ENT_QUOTES, "UTF-8") ?>">
                        </div>

                        <button class="btn btn-primary" type="submit">Update Details</button>
                    </form>
                </section>

                <section class="card">
                    <h1 style="margin-top:0;">Change password</h1>
                    <form method="POST" action="/settings/profile/password">
                        <input type="hidden" name="_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8") ?>">

                        <div class="form-row">
                            <label for="current_password">Current Password</label>
                            <input class="input" id="current_password" name="current_password" type="password" required>
                        </div>

                        <div class="form-row">
                            <label for="new_password">New Password</label>
                            <input class="input" id="new_password" name="new_password" type="password" required>
                        </div>

                        <div class="form-row">
                            <label for="confirm_password">Confirm Password</label>
                            <input class="input" id="confirm_password" name="confirm_password" type="password" required>
                        </div>

                        <button class="btn btn-primary" type="submit">Update Password</button>
                    </form>
                </section>
            </div>
        </main>
    </div>
</div>
