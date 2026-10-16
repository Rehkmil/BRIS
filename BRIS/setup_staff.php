<?php
require 'config/functions.php';

start_page('Home');

// ---------------------------------------------------------------------------
// Page state
// ---------------------------------------------------------------------------
$user      = current_user();
$logged_in = is_logged_in();
$is_staff  = $logged_in && ($user['role'] ?? '') === 'staff';

// Quick-access cards depend on who is viewing the page.
// Each card: [link, icon, title, description]
if ($is_staff) {
    $quick_links = [
        ['residents.php',  '✓', 'Resident Information', 'Search and manage resident profiles.'],
        ['households.php', '⌂', 'Household Records',    'View households and members.'],
        ['reports.php',    '▤', 'Reports',              'Review and print summary reports.'],
        ['accounts.php',   '♙', 'Account Management',   'Create user and staff accounts.'],
    ];
} elseif ($logged_in) {
    $quick_links = [
        ['user_dashboard.php', '♙', 'My Account', 'View your account and linked profile.'],
    ];
} else {
    $quick_links = [
        ['register.php', '＋', 'Create User Account', 'Create a standard resident-user login.'],
        ['login.php',    '↪', 'Log In',              'Access your user or staff account.'],
    ];
}
?>
<main>

    <!-- ================= Hero ================= -->
    <section class="hero">
        <div class="wrap">
            <span>🏘 YOUR BARANGAY, CONNECTED</span>
            <h1>Your community, organized and connected.</h1>
            <p>
                Manage resident information, keep household records organized,
                and stay updated with barangay information—all in one place.
            </p>

            <div class="actions">
                <?php if ($is_staff): ?>
                    <a class="btn light"   href="residents.php">Open Staff Workspace →</a>
                    <a class="btn outline" href="accounts.php">Manage Accounts</a>
                <?php elseif ($logged_in): ?>
                    <a class="btn light"   href="user_dashboard.php">Open My Account →</a>
                    <a class="btn outline" href="#about">About BRIS</a>
                <?php else: ?>
                    <a class="btn light"   href="register.php">Create User Account →</a>
                    <a class="btn outline" href="login.php">Log In</a>
                <?php endif; ?>
            </div>

            <!-- Barangay hall illustration -->
            <svg viewBox="0 0 520 170" width="min(100%,430px)" style="margin-top:26px"
                 role="img" aria-label="Barangay hall illustration">
                <!-- Ground shadow -->
                <ellipse cx="260" cy="160" rx="210" ry="9" fill="#12377f" opacity=".3"/>
                <!-- Roof -->
                <path d="M90 90 260 15 430 90Z" fill="#f3f7ff"/>
                <!-- Building body -->
                <rect x="120" y="89" width="280" height="72" rx="4" fill="#fff"/>
                <!-- Windows -->
                <path d="M155 110h24v35h-24
                         m42-35h24v35h-24
                         m102-35h24v35h-24
                         m42-35h24v35h-24" fill="#c8dcff"/>
                <!-- Door -->
                <rect x="242" y="124" width="36" height="37" fill="#2463eb"/>
                <!-- Clock tower / sign -->
                <rect x="244" y="48" width="32" height="27" rx="3" fill="#2463eb"/>
                <path d="M260 52v19m-9-10h18" stroke="white" stroke-width="3"/>
            </svg>
        </div>
    </section>

    <!-- ================= Quick access ================= -->
    <section class="section lightbg">
        <div class="wrap center">
            <div class="kicker">Quick access</div>
            <h2>Everything your community needs</h2>
            <p class="muted">Choose the tools available for your account.</p>

            <div class="grid">
                <?php foreach ($quick_links as [$href, $icon, $title, $desc]): ?>
                    <a class="card center" href="<?= $href ?>">
                        <div class="icon"><?= $icon ?></div>
                        <h3><?= $title ?></h3>
                        <p class="muted"><?= $desc ?></p>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- ================= About ================= -->
    <section class="section" id="about">
        <div class="wrap">
            <div class="kicker">About the system</div>
            <h2>A simpler way to manage barangay records</h2>
            <p class="muted">
                BRIS helps authorized staff organize resident information and household records.
                User accounts have a separate portal, while staff accounts manage private record tools.
            </p>
        </div>
    </section>

    <!-- ================= Account roles ================= -->
    <section class="section lightbg">
        <div class="wrap center">
            <div class="kicker">Account roles</div>
            <h2>Different access for different responsibilities</h2>

            <div class="grid three">
                <article class="card">
                    <h3>Staff</h3>
                    <p class="muted">
                        Can access resident records, household summaries, reports,
                        officials information, and account management.
                    </p>
                </article>

                <article class="card">
                    <h3>User / Resident</h3>
                    <p class="muted">
                        Can log in to a personal account and view the resident profile
                        if staff links it to their account.
                    </p>
                </article>

                <article class="card">
                    <h3>Secure sign-out</h3>
                    <p class="muted">
                        Use the Log out button to end the current signed-in session.
                    </p>
                </article>
            </div>
        </div>
    </section>

</main>
<?php end_page(); ?>