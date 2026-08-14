<div class="app-shell">
    <header class="topbar">
        <div>
            <div class="brand">Company List</div>
            <div class="muted" style="color: #b8c6cf;">Manage multi-vendor companies and suppliers</div>
        </div>
        <a class="btn btn-ghost" href="/settings/companies/create">Add Company</a>
    </header>

    <div class="shell-grid">
        <?php require BASE_PATH . "/views/settings/_nav.php"; ?>
        <main class="page">
            <?php require BASE_PATH . "/views/settings/_flash.php"; ?>

            <section class="card" style="overflow-x:auto;">
                <table style="width:100%; border-collapse: collapse;">
                    <thead>
                        <tr style="text-align:left; border-bottom:1px solid var(--border);">
                            <th style="padding:12px;">Company ID</th>
                            <th style="padding:12px;">Company Name</th>
                            <th style="padding:12px;">Address</th>
                            <th style="padding:12px;">Telephone</th>
                            <th style="padding:12px;">Email</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($companies)): ?>
                            <tr>
                                <td colspan="5" style="padding:24px; text-align:center; color: #b8c6cf;">No companies registered.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($companies as $company): ?>
                                <tr style="border-bottom:1px solid #edf1f4;">
                                    <td style="padding:12px;"><?= (int) $company["id"] ?></td>
                                    <td style="padding:12px; font-weight: 600;"><?= htmlspecialchars((string) $company["company_name"], ENT_QUOTES, "UTF-8") ?></td>
                                    <td style="padding:12px;"><?= htmlspecialchars((string) ($company["company_address"] ?? '—'), ENT_QUOTES, "UTF-8") ?></td>
                                    <td style="padding:12px;"><?= htmlspecialchars((string) ($company["company_phone"] ?? '—'), ENT_QUOTES, "UTF-8") ?></td>
                                    <td style="padding:12px;"><?= htmlspecialchars((string) ($company["company_email"] ?? '—'), ENT_QUOTES, "UTF-8") ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </section>
        </main>
    </div>
</div>
