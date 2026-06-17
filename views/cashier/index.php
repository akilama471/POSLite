<?php
$context        = is_array($context ?? null) ? $context : [];
$slot           = $context['slot']      ?? null;
$activeLog      = $context['activeLog'] ?? null;
$latestLog      = $context['latestLog'] ?? null;
$isActive       = (bool)($context['isActive'] ?? false);
$canStart       = (bool)($context['canStart'] ?? false);
$summary        = $sessionSummary ?? null;
$shopUsers      = $shopUsers ?? [];

$fmt = static fn(mixed $v): string => 'Rs. ' . number_format((float)$v, 2, '.', ',');
$payTypeLabel = [1 => 'Cash', 2 => 'Card', 3 => 'Cheque'];
?>

<div class="app-shell">
    <header class="topbar">
        <div>
            <div class="brand">Cashier Duty</div>
            <div class="muted" style="color:#b8c6cf;">Migrated from <code>cashier_onoff.php</code> &amp; <code>shop_close.php</code></div>
        </div>
    </header>

    <div class="shell-grid">
        <?php require BASE_PATH . '/views/cashier/_nav.php'; ?>

        <main class="page">
            <?php require BASE_PATH . '/views/settings/_flash.php'; ?>

            {{-- ── Status card ────────────────────────────── --}}
            <section class="card" style="margin-bottom:18px;">
                <div class="grid" style="grid-template-columns:repeat(auto-fit,minmax(220px,1fr));">
                    <div>
                        <div class="muted">Shop</div>
                        <strong><?= htmlspecialchars((string)($auth['shop_name'] ?? 'Unknown'), ENT_QUOTES, 'UTF-8') ?></strong>
                    </div>
                    <div>
                        <div class="muted">Cashier Slot</div>
                        <strong><?= $slot ? '#' . (int)$slot['recordid'] : 'Not Assigned' ?></strong>
                    </div>
                    <div>
                        <div class="muted">Status</div>
                        <strong style="color:<?= $isActive ? '#4ade80' : '#f87171' ?>">
                            <?= $isActive ? '● Active' : '○ Closed' ?>
                        </strong>
                    </div>
                </div>
                <?php if ($slot === null): ?>
                    <p class="section-copy" style="margin:16px 0 0;">You are not assigned to a cashier point. An administrator must configure a slot.</p>
                <?php elseif ($isActive): ?>
                    <p class="section-copy" style="margin:16px 0 0;">Cashier duty is active. Review your balance summary below and close the slot before leaving.</p>
                <?php else: ?>
                    <p class="section-copy" style="margin:16px 0 0;">Check your drawer and sign in to cashier duty before using payment or POS operations.</p>
                <?php endif; ?>
            </section>

            {{-- ── Open Duty ──────────────────────────────── --}}
            <?php if ($slot !== null && $canDutyOn && $canStart): ?>
            <section class="card" style="margin-bottom:18px;">
                <h2 class="section-title">Open Duty</h2>
                <form method="POST" action="/cashier/start">
                    <input type="hidden" name="_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
                    <div class="grid" style="grid-template-columns:repeat(auto-fit,minmax(220px,1fr));align-items:end;">
                        <div class="form-row">
                            <label for="cash_open_balance">User Cash Open Balance</label>
                            <input class="input" id="cash_open_balance" name="cash_open_balance"
                                   type="number" min="0" step="0.01" required
                                   value="<?= htmlspecialchars((string)($latestLog['cash_closebal'] ?? '0'), ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                        <div class="form-row">
                            <label for="card_open_balance">User Card Open Balance</label>
                            <input class="input" id="card_open_balance" name="card_open_balance"
                                   type="number" min="0" step="0.01" required
                                   value="<?= htmlspecialchars((string)($latestLog['card_closebal'] ?? '0'), ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                        <div>
                            <button class="btn btn-primary" type="submit">Update Cash Details &amp; Sign In</button>
                        </div>
                    </div>
                </form>
            </section>
            <?php endif; ?>

            {{-- ── Session Balance Summary (shown when active) ── --}}
            <?php if ($isActive && $summary !== null): ?>
            <section class="card" style="margin-bottom:18px;">
                <h2 class="section-title">Session Balance Summary</h2>
                <div class="grid" style="grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:12px;margin-bottom:16px;">
                    <div><div class="muted">Cash Open Balance</div><strong><?= $fmt($summary['cash_open_bal']) ?></strong></div>
                    <div><div class="muted">Card Open Balance</div><strong><?= $fmt($summary['card_open_bal']) ?></strong></div>
                    <div><div class="muted">Income / Sales (Cash)</div><strong><?= $fmt($summary['inc_cash']) ?></strong></div>
                    <div><div class="muted">Sales (Card)</div><strong><?= $fmt($summary['inc_card']) ?></strong></div>
                    <div><div class="muted">Cheques Received</div><strong><?= $fmt($summary['inc_cheq']) ?></strong></div>
                    <div><div class="muted">Total Expenses</div><strong><?= $fmt($summary['exp_total']) ?></strong></div>
                    <div><div class="muted" style="color:#4ade80;">System Close Cash</div><strong style="color:#4ade80;"><?= $fmt($summary['sys_close_cash']) ?></strong></div>
                    <div><div class="muted" style="color:#60a5fa;">System Close Card</div><strong style="color:#60a5fa;"><?= $fmt($summary['sys_close_card']) ?></strong></div>
                </div>

                <?php if (!empty($summary['transactions'])): ?>
                <details style="margin-top:8px;">
                    <summary style="cursor:pointer;color:#94a3b8;font-size:0.9rem;">▶ Show All My Transactions (<?= count($summary['transactions']) ?>)</summary>
                    <div style="overflow:auto;max-height:320px;margin-top:10px;">
                        <table class="data-table">
                            <thead><tr><th>Type</th><th>Remark</th><th style="text-align:right;">Credit</th><th style="text-align:right;">Debit</th><th>Time</th></tr></thead>
                            <tbody>
                            <?php foreach ($summary['transactions'] as $tx): ?>
                                <tr>
                                    <td><?= htmlspecialchars($payTypeLabel[(int)($tx['pay_type'] ?? 0)] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars((string)($tx['remark'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td style="text-align:right;"><?= (float)$tx['cash_in'] > 0 ? $fmt($tx['cash_in']) : '—' ?></td>
                                    <td style="text-align:right;"><?= (float)$tx['cash_out'] > 0 ? $fmt($tx['cash_out']) : '—' ?></td>
                                    <td class="muted"><?= htmlspecialchars((string)($tx['op_date'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </details>
                <?php endif; ?>
            </section>
            <?php endif; ?>

            {{-- ── Close Duty ─────────────────────────────── --}}
            <?php if ($slot !== null && $isActive && $canDutyOff): ?>
            <section class="card" style="margin-bottom:18px;">
                <h2 class="section-title">Close Duty</h2>
                <form method="POST" action="/cashier/close" id="close-form">
                    <input type="hidden" name="_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">

                    <div class="grid" style="grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:16px;margin-bottom:16px;">
                        <div class="form-row">
                            <label for="cash_close_balance">User Cash Close Balance</label>
                            <input class="input" id="cash_close_balance" name="cash_close_balance"
                                   type="number" min="0" step="0.01" required
                                   value="<?= htmlspecialchars((string)($summary['sys_close_cash'] ?? $activeLog['cash_openbal'] ?? '0'), ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                        <div class="form-row">
                            <label for="card_close_balance">User Card Close Balance</label>
                            <input class="input" id="card_close_balance" name="card_close_balance"
                                   type="number" min="0" step="0.01" required
                                   value="<?= htmlspecialchars((string)($summary['sys_close_card'] ?? $activeLog['card_openbal'] ?? '0'), ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                    </div>

                    {{-- Close Method --}}
                    <div class="form-row" style="margin-bottom:12px;">
                        <label style="display:block;margin-bottom:8px;">Account Close Method</label>
                        <label style="display:inline-flex;align-items:center;gap:8px;margin-right:24px;">
                            <input type="radio" name="close_type" id="ct_keep" value="1" checked onchange="toggleTransfer()">
                            Close and keep with me
                        </label>
                        <label style="display:inline-flex;align-items:center;gap:8px;">
                            <input type="radio" name="close_type" id="ct_transfer" value="2" onchange="toggleTransfer()">
                            Close and transfer to another operator
                        </label>
                    </div>

                    <div id="transfer-user-row" class="form-row" style="display:none;margin-bottom:16px;">
                        <label for="transfer_to_user" style="color:#f87171;">Select User to Transfer Slot *</label>
                        <select class="input" id="transfer_to_user" name="transfer_to_user">
                            <option value="">— Select Operator —</option>
                            <?php foreach ($shopUsers as $u): ?>
                                <option value="<?= (int)$u['myid'] ?>"><?= htmlspecialchars((string)$u['visibledata'], ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <button class="btn btn-primary" type="submit">Close Cashier Duty</button>
                    </div>
                </form>
            </section>
            <?php endif; ?>

            {{-- ── Latest Duty Record ─────────────────────── --}}
            <?php if ($latestLog !== null): ?>
            <section class="card">
                <h2 class="section-title">Latest Duty Record</h2>
                <div class="grid" style="grid-template-columns:repeat(auto-fit,minmax(180px,1fr));">
                    <div><div class="muted">Opened</div><strong><?= htmlspecialchars((string)($latestLog['recordtime'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong></div>
                    <div><div class="muted">Closed</div><strong><?= htmlspecialchars((string)($latestLog['close_time'] ?? 'Still Open'), ENT_QUOTES, 'UTF-8') ?></strong></div>
                    <div><div class="muted">Cash Open</div><strong><?= $fmt($latestLog['cash_openbal'] ?? 0) ?></strong></div>
                    <div><div class="muted">Card Open</div><strong><?= $fmt($latestLog['card_openbal'] ?? 0) ?></strong></div>
                    <?php if (isset($latestLog['cash_closebal'])): ?>
                    <div><div class="muted">Cash Close</div><strong><?= $fmt($latestLog['cash_closebal'] ?? 0) ?></strong></div>
                    <div><div class="muted">Card Close</div><strong><?= $fmt($latestLog['card_closebal'] ?? 0) ?></strong></div>
                    <?php endif; ?>
                </div>
            </section>
            <?php endif; ?>
        </main>
    </div>
</div>

<script>
function toggleTransfer() {
    var row = document.getElementById('transfer-user-row');
    var sel = document.getElementById('transfer_to_user');
    var checked = document.getElementById('ct_transfer').checked;
    row.style.display = checked ? 'block' : 'none';
    sel.required = checked;
}
</script>
