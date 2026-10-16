<?php
require_once 'config/functions.php';
require_role('staff');
require_once 'config/database.php';

// Summary figures are calculated from the current database records.
$totalResidents = (int) $pdo->query('SELECT COUNT(*) FROM residents')->fetchColumn();
$activeResidents = (int) $pdo->query("SELECT COUNT(*) FROM residents WHERE status = 'Active'")->fetchColumn();
$inactiveResidents = (int) $pdo->query("SELECT COUNT(*) FROM residents WHERE status <> 'Active'")->fetchColumn();
$totalHouseholds = (int) $pdo->query('SELECT COUNT(*) FROM households')->fetchColumn();
$totalOfficials = (int) $pdo->query("SELECT COUNT(*) FROM barangay_officials WHERE status = 'Active'")->fetchColumn();
$totalUserAccounts = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn();
$unlinkedAccounts = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user' AND resident_id IS NULL")->fetchColumn();
$recentResidents = $pdo->query("SELECT r.id, r.first_name, r.middle_name, r.last_name, r.status, r.created_at, h.household_code FROM residents r LEFT JOIN households h ON h.id = r.household_id ORDER BY r.created_at DESC, r.id DESC LIMIT 6")->fetchAll(PDO::FETCH_ASSOC);

start_page('Staff Dashboard');
?>
<style>
.staff-dash{padding:30px 0 12px}.dash-heading{display:flex;justify-content:space-between;align-items:flex-start;gap:16px;flex-wrap:wrap;margin-bottom:24px}.dash-heading h1{margin:6px 0;font-size:clamp(26px,4vw,36px)}.dash-heading p{margin:0}.dash-date{padding:10px 13px;border:1px solid #e2e9f4;border-radius:10px;background:#fff;color:#68758a;font-size:13px}.dash-metrics{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px}.dash-metric{padding:19px;background:#fff;border:1px solid #e5eaf2;border-radius:14px;box-shadow:0 5px 18px #152c5208}.dash-metric .metric-label{color:#68758a;font-size:13px}.dash-metric strong{display:block;font-size:30px;margin:8px 0}.dash-metric a{font-size:12px;font-weight:bold;text-decoration:none}.dash-lower{display:grid;grid-template-columns:minmax(0,1.6fr) minmax(260px,1fr);gap:18px;margin-top:20px}.dash-panel{background:#fff;border:1px solid #e5eaf2;border-radius:14px;padding:20px;min-width:0}.dash-panel h2{font-size:18px;margin:0 0 6px}.dash-panel .muted{font-size:13px;margin-top:0}.dash-shortcuts{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px;margin-top:16px}.dash-shortcut{display:block;padding:14px;border:1px solid #e5eaf2;border-radius:11px;text-decoration:none;color:#182338}.dash-shortcut:hover{border-color:#2463eb;background:#f7f9ff}.dash-shortcut strong{display:block;font-size:14px;margin-bottom:5px}.dash-shortcut span{font-size:12px;color:#68758a}.dash-note{margin-top:18px;padding:14px;border-radius:10px;background:#edf4ff;color:#415a80;font-size:13px;line-height:1.6}
@media(max-width:900px){.dash-metrics{grid-template-columns:repeat(2,minmax(0,1fr))}.dash-lower{grid-template-columns:1fr}}@media(max-width:480px){.dash-metrics,.dash-shortcuts{grid-template-columns:1fr}.dash-metric strong{font-size:26px}}
</style>
<main class="wrap staff-dash">
    <?php show_flash(); ?>
    <div class="dash-heading">
        <div><div class="kicker">Staff workspace</div><h1>Dashboard Overview</h1><p class="muted">A quick summary of the barangay records currently stored in BRIS.</p></div>
        <div class="dash-date">Today: <?= e(date('F j, Y')) ?></div>
    </div>
    <section class="dash-metrics" aria-label="Barangay record summaries">
        <article class="dash-metric"><div class="metric-label">Total Residents</div><strong><?= number_format($totalResidents) ?></strong><a href="residents.php">View resident records →</a></article>
        <article class="dash-metric"><div class="metric-label">Active Residents</div><strong><?= number_format($activeResidents) ?></strong><a href="residents.php?status=Active">View active residents →</a></article>
        <article class="dash-metric"><div class="metric-label">Households</div><strong><?= number_format($totalHouseholds) ?></strong><a href="households.php">View households →</a></article>
        <article class="dash-metric"><div class="metric-label">Active Officials</div><strong><?= number_format($totalOfficials) ?></strong><a href="officials.php">View officials →</a></article>
    </section>
    <div class="dash-lower">
        <section class="dash-panel">
            <h2>Recently added residents</h2><p class="muted">The six most recently added resident records.</p>
            <div class="tablewrap"><table><thead><tr><th>Name</th><th>Household</th><th>Status</th><th>Added</th></tr></thead><tbody>
            <?php if (!$recentResidents): ?><tr><td colspan="4">No resident records have been added yet.</td></tr><?php else: foreach ($recentResidents as $resident): ?>
                <tr><td><?= e(trim($resident['first_name'].' '.($resident['middle_name'] ?? '').' '.$resident['last_name'])) ?></td><td><?= e($resident['household_code'] ?? 'Not assigned') ?></td><td><span class="badge <?= $resident['status'] !== 'Active' ? 'inactive' : '' ?>"><?= e($resident['status']) ?></span></td><td><?= e(date('M j, Y', strtotime($resident['created_at']))) ?></td></tr>
            <?php endforeach; endif; ?>
            </tbody></table></div>
        </section>
        <section class="dash-panel">
            <h2>Quick actions</h2><p class="muted">Go directly to a common staff task.</p>
            <div class="dash-shortcuts">
                <a class="dash-shortcut" href="resident_form.php"><strong>＋ Register resident</strong><span>Add a resident profile</span></a>
                <a class="dash-shortcut" href="household_form.php"><strong>⌂ Add household</strong><span>Create a household record</span></a>
                <a class="dash-shortcut" href="reports.php"><strong>▤ Reports</strong><span>Review or print summaries</span></a>
                <a class="dash-shortcut" href="accounts.php"><strong>♙ Accounts</strong><span><?= number_format($totalUserAccounts) ?> user account(s)</span></a>
                <a class="dash-shortcut" href="officials.php"><strong>♧ Officials</strong><span>View the barangay directory</span></a>
                <a class="dash-shortcut" href="residents.php?status=Inactive"><strong>! Other statuses</strong><span><?= number_format($inactiveResidents) ?> non-active resident record(s)</span></a>
            </div>
            <?php if ($unlinkedAccounts > 0): ?><div class="dash-note"><strong>Account reminder:</strong> <?= number_format($unlinkedAccounts) ?> user account(s) are not linked to a resident profile. Visit Account Management if these accounts should have resident profiles.</div><?php else: ?><div class="dash-note">All user accounts currently have a resident profile linked, or there are no user accounts that need linking.</div><?php endif; ?>
        </section>
    </div>
</main>
<?php end_page(); ?>
