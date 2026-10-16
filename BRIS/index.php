<?php require 'config/functions.php'; start_page('Home'); $user = current_user(); ?>
<main>
<section class="hero"><div class="wrap"><span>🏘 YOUR BARANGAY, CONNECTED</span><h1>Your community, organized and connected.</h1><p>Manage resident information, keep household records organized, and stay updated with barangay information—all in one place.</p><div class="actions">
<?php if (is_logged_in() && ($user['role'] ?? '') === 'staff'): ?>
<a class="btn light" href="residents.php">Open Staff Workspace →</a><a class="btn outline" href="accounts.php">Manage Accounts</a>
<?php elseif (is_logged_in()): ?>
<a class="btn light" href="user_dashboard.php">Open My Account →</a><a class="btn outline" href="#about">About BRIS</a>
<?php else: ?>
<a class="btn light" href="register.php">Create User Account →</a><a class="btn outline" href="login.php">Log In</a>
<?php endif; ?>
</div>
<svg viewBox="0 0 520 170" width="min(100%,430px)" style="margin-top:26px" role="img" aria-label="Barangay hall illustration"><ellipse cx="260" cy="160" rx="210" ry="9" fill="#12377f" opacity=".3"/><path d="M90 90 260 15 430 90Z" fill="#f3f7ff"/><rect x="120" y="89" width="280" height="72" rx="4" fill="#fff"/><path d="M155 110h24v35h-24m42-35h24v35h-24m102-35h24v35h-24m42-35h24v35h-24" fill="#c8dcff"/><rect x="242" y="124" width="36" height="37" fill="#2463eb"/><rect x="244" y="48" width="32" height="27" rx="3" fill="#2463eb"/><path d="M260 52v19m-9-10h18" stroke="white" stroke-width="3"/></svg></div></section>
<section class="section lightbg"><div class="wrap center"><div class="kicker">Quick access</div><h2>Everything your community needs</h2><p class="muted">Choose the tools available for your account.</p><div class="grid">
<?php if (is_logged_in() && ($user['role'] ?? '') === 'staff'): ?>
<a class="card center" href="residents.php"><div class="icon">✓</div><h3>Resident Information</h3><p class="muted">Search and manage resident profiles.</p></a><a class="card center" href="households.php"><div class="icon">⌂</div><h3>Household Records</h3><p class="muted">View households and members.</p></a><a class="card center" href="reports.php"><div class="icon">▤</div><h3>Reports</h3><p class="muted">Review and print summary reports.</p></a><a class="card center" href="accounts.php"><div class="icon">♙</div><h3>Account Management</h3><p class="muted">Create user and staff accounts.</p></a>
<?php elseif (is_logged_in()): ?>
<a class="card center" href="user_dashboard.php"><div class="icon">♙</div><h3>My Account</h3><p class="muted">View your account and linked profile.</p></a>
<?php else: ?>
<a class="card center" href="register.php"><div class="icon">＋</div><h3>Create User Account</h3><p class="muted">Create a standard resident-user login.</p></a><a class="card center" href="login.php"><div class="icon">↪</div><h3>Log In</h3><p class="muted">Access your user or staff account.</p></a><?php endif; ?>
</div></div></section>
<section class="section" id="about"><div class="wrap"><div class="kicker">About the system</div><h2>A simpler way to manage barangay records</h2><p class="muted">BRIS helps authorized staff organize resident information and household records. User accounts have a separate portal, while staff accounts manage private record tools.</p></div></section>
<section class="section lightbg"><div class="wrap center"><div class="kicker">Account roles</div><h2>Different access for different responsibilities</h2><div class="grid three"><article class="card"><h3>Staff</h3><p class="muted">Can access resident records, household summaries, reports, officials information, and account management.</p></article><article class="card"><h3>User / Resident</h3><p class="muted">Can log in to a personal account and view the resident profile if staff links it to their account.</p></article><article class="card"><h3>Secure sign-out</h3><p class="muted">Use the Log out button to end the current signed-in session.</p></article></div></div></section>
</main><?php end_page(); ?>
