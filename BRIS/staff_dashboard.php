<?php
require_once 'config/functions.php';
require_role('staff');
require_once 'config/database.php';

// ---------------------------------------------------------------------------
// Data: summary figures are calculated from the current database records
// ---------------------------------------------------------------------------

// Runs a COUNT-style query and returns the result as an integer.
$count = function (string $sql) use ($pdo): int {
    return (int) $pdo->query($sql)->fetchColumn();
};

$totalResidents    = $count('SELECT COUNT(*) FROM residents');
$activeResidents   = $count("SELECT COUNT(*) FROM residents WHERE status = 'Active'");
$inactiveResidents = $count("SELECT COUNT(*) FROM residents WHERE status <> 'Active'");
$totalHouseholds   = $count('SELECT COUNT(*) FROM households');
$totalOfficials    = $count("SELECT COUNT(*) FROM barangay_officials WHERE status = 'Active'");
$totalUserAccounts = $count("SELECT COUNT(*) FROM users WHERE role = 'user'");
$unlinkedAccounts  = $count("SELECT COUNT(*) FROM users WHERE role = 'user' AND resident_id IS NULL");

$recentResidents = $pdo->query(
    "SELECT r.id, r.first_name, r.middle_name, r.last_name, r.status, r.created_at,
            h.household_code
     FROM residents r
     LEFT JOIN households h ON h.id = r.household_id
     ORDER BY r.created_at DESC, r.id DESC
     LIMIT 6"
)->fetchAll(PDO::FETCH_ASSOC);

// ---------------------------------------------------------------------------
// View data: what the template loops over
// ---------------------------------------------------------------------------

// Top metric cards: [label, value, link, link text]
$metrics = [
    ['Total Residents',  $totalResidents,  'residents.php',               'View resident records →'],
    ['Active Residents', $activeResidents, 'residents.php?status=Active', 'View active residents →'],
    ['Households',       $totalHouseholds, 'households.php',              'View households →'],
    ['Active Officials', $totalOfficials,  'officials.php',               'View officials →'],
];

// Quick action shortcuts: [link, title, description]
$shortcuts = [
    ['resident_form.php',             '＋ Register resident', 'Add a resident profile'],
    ['household_form.php',            '⌂ Add household',      'Create a household record'],
    ['reports.php',                   '▤ Reports',            'Review or print summaries'],
    ['accounts.php',                  '♙ Accounts',           number_format($totalUserAccounts) . ' user account(s)'],
    ['officials.php',                 '♧ Officials',          'View the barangay directory'],
    ['residents.php?status=Inactive', '! Other statuses',     number_format($inactiveResidents) . ' non-active resident record(s)'],
];

start_page('Staff Dashboard');
?>
<style>
    .staff-dash {
        padding: 30px 0 12px;
    }

    /* ----- Heading ----- */
    .dash-heading {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 16px;
        flex-wrap: wrap;
        margin-bottom: 24px;
    }
    .dash-heading h1 {
        margin: 6px 0;
        font-size: clamp(26px, 4vw, 36px);
    }
    .dash-heading p {
        margin: 0;
    }
    .dash-date {
        padding: 10px 13px;
        border: 1px solid #e2e9f4;
        border-radius: 10px;
        background: #fff;
        color: #68758a;
        font-size: 13px;
    }

    /* ----- Metric cards ----- */
    .dash-metrics {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 14px;
    }
    .dash-metric {
        padding: 19px;
        background: #fff;
        border: 1px solid #e5eaf2;
        border-radius: 14px;
        box-shadow: 0 5px 18px #152c5208;
    }
    .dash-metric .metric-label {
        color: #68758a;
        font-size: 13px;
    }
    .dash-metric strong {
        display: block;
        font-size: 30px;
        margin: 8px 0;
    }
    .dash-metric a {
        font-size: 12px;
        font-weight: bold;
        text-decoration: none;
    }

    /* ----- Lower panels ----- */
    .dash-lower {
        display: grid;
        grid-template-columns: minmax(0, 1.6fr) minmax(260px, 1fr);
        gap: 18px;
        margin-top: 20px;
    }
    .dash-panel {
        background: #fff;
        border: 1px solid #e5eaf2;
        border-radius: 14px;
        padding: 20px;
        min-width: 0;
    }
    .dash-panel h2 {
        font-size: 18px;
        margin: 0 0 6px;
    }
    .dash-panel .muted {
        font-size: 13px;
        margin-top: 0;
    }

    /* ----- Quick actions ----- */
    .dash-shortcuts {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 10px;
        margin-top: 16px;
    }
    .dash-shortcut {
        display: block;
        padding: 14px;
        border: 1px solid #e5eaf2;
        border-radius: 11px;
        text-decoration: none;
        color: #182338;
    }
    .dash-shortcut:hover {
        border-color: #2463eb;
        background: #f7f9ff;
    }
    .dash-shortcut strong {
        display: block;
        font-size: 14px;
        margin-bottom: 5px;
    }
    .dash-shortcut span {
        font-size: 12px;
        color: #68758a;
    }
    .dash-note {
        margin-top: 18px;
        padding: 14px;
        border-radius: 10px;
        background: #edf4ff;
        color: #415a80;
        font-size: 13px;
        line-height: 1.6;
    }

    /* ----- Responsive ----- */
    @media (max-width: 900px) {
        .dash-metrics {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
        .dash-lower {
            grid-template-columns: 1fr;
        }
    }
    @media (max-width: 480px) {
        .dash-metrics,
        .dash-shortcuts {
            grid-template-columns: 1fr;
        }
        .dash-metric strong {
            font-size: 26px;
        }
    }
</style>

<main class="wrap staff-dash">
    <?php show_flash(); ?>

    <!-- ================= Heading ================= -->
    <div class="dash-heading">
        <div>
            <div class="kicker">Staff workspace</div>
            <h1>Dashboard Overview</h1>
            <p class="muted">A quick summary of the barangay records currently stored in BRIS.</p>
        </div>
        <div class="dash-date">Today: <?= e(date('F j, Y')) ?></div>
    </div>

    <!-- ================= Metric cards ================= -->
    <section class="dash-metrics" aria-label="Barangay record summaries">
        <?php foreach ($metrics as [$label, $value, $href, $linkText]): ?>
            <article class="dash-metric">
                <div class="metric-label"><?= e($label) ?></div>
                <strong><?= number_format($value) ?></strong>
                <a href="<?= e($href) ?>"><?= e($linkText) ?></a>
            </article>
        <?php endforeach; ?>
    </section>

    <div class="dash-lower">

        <!-- ================= Recently added residents ================= -->
        <section class="dash-panel">
            <h2>Recently added residents</h2>
            <p class="muted">The six most recently added resident records.</p>

            <div class="tablewrap">
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Household</th>
                            <th>Status</th>
                            <th>Added</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!$recentResidents): ?>
                            <tr>
                                <td colspan="4">No resident records have been added yet.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($recentResidents as $resident):
                                $fullName   = trim($resident['first_name'] . ' ' . ($resident['middle_name'] ?? '') . ' ' . $resident['last_name']);
                                $badgeClass = $resident['status'] !== 'Active' ? 'inactive' : '';
                            ?>
                                <tr>
                                    <td><?= e($fullName) ?></td>
                                    <td><?= e($resident['household_code'] ?? 'Not assigned') ?></td>
                                    <td><span class="badge <?= $badgeClass ?>"><?= e($resident['status']) ?></span></td>
                                    <td><?= e(date('M j, Y', strtotime($resident['created_at']))) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- ================= Quick actions ================= -->
        <section class="dash-panel">
            <h2>Quick actions</h2>
            <p class="muted">Go directly to a common staff task.</p>

            <div class="dash-shortcuts">
                <?php foreach ($shortcuts as [$href, $title, $description]): ?>
                    <a class="dash-shortcut" href="<?= e($href) ?>">
                        <strong><?= e($title) ?></strong>
                        <span><?= e($description) ?></span>
                    </a>
                <?php endforeach; ?>
            </div>

            <?php if ($unlinkedAccounts > 0): ?>
                <div class="dash-note">
                    <strong>Account reminder:</strong>
                    <?= number_format($unlinkedAccounts) ?> user account(s) are not linked to a resident profile.
                    Visit Account Management if these accounts should have resident profiles.
                </div>
            <?php else: ?>
                <div class="dash-note">
                    All user accounts currently have a resident profile linked,
                    or there are no user accounts that need linking.
                </div>
            <?php endif; ?>
        </section>

    </div>
</main>
<?php end_page(); ?>