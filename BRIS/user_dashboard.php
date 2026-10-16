<?php
require_once 'config/functions.php';
require_once 'config/database.php';
require_role('user');

$user = current_user();
$stmt = $pdo->prepare("SELECT u.username, u.email, u.created_at, r.id AS resident_id, r.first_name, r.middle_name, r.last_name, r.birth_date, r.sex, r.phone, r.email AS resident_email, r.address, r.status, h.household_code, h.address AS household_address, h.head_name FROM users u LEFT JOIN residents r ON r.id = u.resident_id LEFT JOIN households h ON h.id = r.household_id WHERE u.id = ? LIMIT 1");
$stmt->execute([(int) $user['id']]);
$account = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

start_page('My Account');
?>
<div class="pagehead"><div class="wrap"><h1>My Dashboard</h1><p class="muted">A personal overview of your BRIS account, resident profile, and household information.</p></div></div>
<main class="wrap">
    <?php show_flash(); ?>
    <section class="grid three" style="margin-top:24px">
        <article class="card"><span class="muted">Account status</span><h2 style="font-size:22px">Active</h2><p class="muted">You are signed in to your BRIS account.</p></article>
        <article class="card"><span class="muted">Resident profile</span><h2 style="font-size:22px"><?= !empty($account['resident_id']) ? 'Linked' : 'Not linked' ?></h2><p class="muted"><?= !empty($account['resident_id']) ? 'Your resident information is connected.' : 'Contact staff to connect your resident record.' ?></p></article>
        <article class="card"><span class="muted">Household</span><h2 style="font-size:22px"><?= !empty($account['household_code']) ? e($account['household_code']) : 'Not assigned' ?></h2><p class="muted">Your currently linked household.</p></article>
    </section>
    <section class="panel">
        <p class="kicker">Signed in as user</p>
        <h2>Welcome, <?= e($account['username'] ?? $user['username']) ?>!</h2>
        <p class="muted">These are the details saved to your BRIS account and resident record.</p>
        <div class="formgrid">
            <div class="field"><label>Username</label><p><?= e($account['username'] ?? $user['username']) ?></p></div>
            <div class="field"><label>Account Email</label><p><?= e($account['email'] ?? $user['email'] ?? 'Not recorded') ?></p></div>
            <div class="field"><label>Account Created</label><p><?= e($account['created_at'] ?? 'Not available') ?></p></div>
        </div>
    </section>
    <section class="panel" id="resident-information">
        <h2>Basic / Resident Information</h2>
        <?php if (!empty($account['resident_id'])): ?>
            <div class="formgrid">
                <div class="field"><label>First Name</label><p><?= e($account['first_name'] ?? 'Not recorded') ?></p></div>
                <div class="field"><label>Middle Name</label><p><?= e($account['middle_name'] ?? 'Not recorded') ?></p></div>
                <div class="field"><label>Last Name</label><p><?= e($account['last_name'] ?? 'Not recorded') ?></p></div>
                <div class="field"><label>Birth Date</label><p><?= e($account['birth_date'] ?? 'Not recorded') ?></p></div>
                <div class="field"><label>Sex</label><p><?= e($account['sex'] ?? 'Not recorded') ?></p></div>
                <div class="field"><label>Contact Number</label><p><?= e($account['phone'] ?? 'Not recorded') ?></p></div>
                <div class="field"><label>Resident Email</label><p><?= e($account['resident_email'] ?? 'Not recorded') ?></p></div>
                <div class="field"><label>Resident Status</label><p><span class="badge"><?= e($account['status'] ?? 'Unknown') ?></span></p></div>
                <div class="field full"><label>Residential Address</label><p><?= e($account['address'] ?? 'Not recorded') ?></p></div>
            </div>
        <?php else: ?>
            <div class="alert danger">Your login account is not linked to a resident record. Please contact barangay staff to link your account.</div>
        <?php endif; ?>
    </section>
    <section class="panel" id="household-information">
        <h2>Household Information</h2>
        <?php if (!empty($account['resident_id']) && !empty($account['household_code'])): ?>
            <div class="formgrid">
                <div class="field"><label>Household Code</label><p><?= e($account['household_code']) ?></p></div>
                <div class="field"><label>Head of Household</label><p><?= e($account['head_name'] ?? 'Not specified') ?></p></div>
                <div class="field full"><label>Household Address</label><p><?= e($account['household_address'] ?? 'Not recorded') ?></p></div>
            </div>
        <?php else: ?>
            <p class="muted">No household is linked to this resident record yet.</p>
        <?php endif; ?>
    </section>
</main>
<?php end_page(); ?>
