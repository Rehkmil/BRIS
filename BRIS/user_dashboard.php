<?php
require_once 'config/functions.php';
require_once 'config/database.php';
require_role('user');

$user = current_user();
$stmt = $pdo->prepare("SELECT u.username, u.email, u.created_at, r.id AS resident_id, r.first_name, r.middle_name, r.last_name, r.birth_date, r.phone, r.email AS resident_email, r.address, r.status, h.household_code FROM users u LEFT JOIN residents r ON r.id = u.resident_id LEFT JOIN households h ON h.id = r.household_id WHERE u.id = ? LIMIT 1");
$stmt->execute([(int) $user['id']]);
$account = $stmt->fetch(PDO::FETCH_ASSOC);

start_page('My Account');
?>
<div class="pagehead"><div class="wrap"><h1>My Account</h1><p class="muted">Your BRIS user account and resident profile.</p></div></div>
<main class="wrap">
    <?php show_flash(); ?>
    <section class="panel">
        <p class="kicker">Signed in as user</p>
        <h2>Welcome, <?= e($account['username'] ?? $user['username']) ?>!</h2>
        <p class="muted">This is your user portal. Resident record-management pages are only available to authorized staff.</p>
        <div class="formgrid">
            <div class="field"><label>Username</label><p><?= e($account['username'] ?? '') ?></p></div>
            <div class="field"><label>Account email</label><p><?= e($account['email'] ?? '') ?></p></div>
        </div>
    </section>
    <section class="panel">
        <h2>Resident Profile</h2>
        <?php if (!empty($account['resident_id'])): ?>
            <div class="formgrid">
                <div class="field"><label>Full name</label><p><?= e(trim(($account['first_name'] ?? '') . ' ' . ($account['middle_name'] ?? '') . ' ' . ($account['last_name'] ?? ''))) ?></p></div>
                <div class="field"><label>Resident status</label><p><span class="badge"><?= e($account['status'] ?? '') ?></span></p></div>
                <div class="field"><label>Address</label><p><?= e($account['address'] ?? '') ?></p></div>
                <div class="field"><label>Household</label><p><?= e($account['household_code'] ?? 'Not assigned') ?></p></div>
                <div class="field"><label>Birth date</label><p><?= e($account['birth_date'] ?? 'Not recorded') ?></p></div>
                <div class="field"><label>Contact number</label><p><?= e($account['phone'] ?? 'Not recorded') ?></p></div>
            </div>
        <?php else: ?>
            <div class="alert success">Your login account is active, but it is not linked to a resident record yet. Please contact barangay staff if this account should be connected to your resident profile.</div>
        <?php endif; ?>
    </section>
</main>
<?php end_page(); ?>
