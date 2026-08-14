<div class="app-shell">
    <header class="topbar">
        <div>
            <div class="brand">Add Company</div>
            <div class="muted" style="color: #b8c6cf;">Register a new company for multi-vendor support</div>
        </div>
    </header>

    <div class="shell-grid">
        <?php require BASE_PATH . "/views/settings/_nav.php"; ?>
        <main class="page">
            <?php require BASE_PATH . "/views/settings/_flash.php"; ?>

            <section class="card" style="max-width: 920px;">
                <h1 style="margin-top:0;">New Company</h1>
                <form method="POST" action="/settings/companies">
                    <input type="hidden" name="_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8") ?>">

                    <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));">
                        <div class="form-row">
                            <label for="company_name">Company Name <span style="color: var(--danger);">*</span></label>
                            <input class="input" id="company_name" name="company_name" placeholder="e.g. Acme Corporation" required autocomplete="off">
                        </div>
                        <div class="form-row">
                            <label for="company_phone">Telephone</label>
                            <input class="input" id="company_phone" name="company_phone" placeholder="e.g. +94 11 234 5678" autocomplete="off">
                        </div>
                        <div class="form-row">
                            <label for="company_email">Email Address</label>
                            <input class="input" type="email" id="company_email" name="company_email" placeholder="e.g. contact@acme.com" autocomplete="off">
                        </div>
                        <div class="form-row">
                            <label for="company_address">Company Address</label>
                            <input class="input" id="company_address" name="company_address" placeholder="e.g. 123 Main St, Colombo" autocomplete="off">
                        </div>
                    </div>

                    <button class="btn btn-primary" type="submit" style="margin-top: 16px;">Create Company</button>
                    <a class="btn btn-ghost" href="/settings/companies" style="margin-top: 16px; margin-left: 8px;">Cancel</a>
                </form>
            </section>
        </main>
    </div>
</div>
