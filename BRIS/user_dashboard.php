<?php
require_once 'config/functions.php';
require_once 'config/database.php';
require_role('user');

// ---------------------------------------------------------------------------
// Data: the signed-in user's account, resident profile and household
// ---------------------------------------------------------------------------
$user = current_user();

$stmt = $pdo->prepare(
    "SELECT u.username, u.email, u.created_at,
            r.id AS resident_id, r.first_name, r.middle_name, r.last_name,
            r.birth_date, r.sex, r.phone, r.email AS resident_email,
            r.address, r.status,
            h.household_code, h.address AS household_address, h.head_name
     FROM users u
     LEFT JOIN residents  r ON r.id = u.resident_id
     LEFT JOIN households h ON h.id = r.household_id
     WHERE u.id = ?
     LIMIT 1"
);
$stmt->execute([(int) $user['id']]);
$account = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

// ---------------------------------------------------------------------------
// View data: derived values and the lists the template loops over
// ---------------------------------------------------------------------------
$hasResident  = !empty($account['resident_id']);
$hasHousehold = !empty($account['household_code']);
$username     = $account['username'] ?? $user['username'];

// Summary cards: [label, heading, description]
$summaryCards = [
    [
        'Account status',
        'Active',
        'You are signed in to your BRIS account.',
    ],
    [
        'Resident profile',
        $hasResident ? 'Linked' : 'Not linked',
        $hasResident
            ? 'Your resident information is connected.'
            : 'Contact staff to connect your resident record.',
    ],
    [
        'Household',
        $hasHousehold ? $account['household_code'] : 'Not assigned',
        'Your currently linked household.',
    ],
];

// Resident and household fields: [label, value, style]
// Style is '' (normal), 'full' (full-width field) or 'badge' (shown as a badge).
// Account fields only need [label, value].
$accountFields = [
    ['Username',        $username],
    ['Account Email',   $account['email'] ?? $user['email'] ?? 'Not recorded'],
    ['Account Created', $account['created_at'] ?? 'Not available'],
];

$residentFields = [
    ['First Name',          $account['first_name']     ?? 'Not recorded', ''],
    ['Middle Name',         $account['middle_name']    ?? 'Not recorded', ''],
    ['Last Name',           $account['last_name']      ?? 'Not recorded', ''],
    ['Birth Date',          $account['birth_date']     ?? 'Not recorded', ''],
    ['Sex',                 $account['sex']            ?? 'Not recorded', ''],
    ['Contact Number',      $account['phone']          ?? 'Not recorded', ''],
    ['Resident Email',      $account['resident_email'] ?? 'Not recorded', ''],
    ['Resident Status',     $account['status']         ?? 'Unknown',      'badge'],
    ['Residential Address', $account['address']        ?? 'Not recorded', 'full'],
];

$householdFields = [
    ['Household Code',      $account['household_code']    ?? '',              ''],
    ['Head of Household',   $account['head_name']         ?? 'Not specified', ''],
    ['Household Address',   $account['household_address'] ?? 'Not recorded',  'full'],
];

start_page('My Account');
?>
<!-- ================= Page header ================= -->
<div class="pagehead">
    <div class="wrap">
        <h1>My Dashboard</h1>
        <p class="muted">
            A personal overview of your BRIS account, resident profile, and household information.
        </p>
    </div>
</div>

<main class="wrap">
    <?php show_flash(); ?>

    <!-- ================= Summary cards ================= -->
    <section class="grid three" style="margin-top:24px">
        <?php foreach ($summaryCards as [$label, $heading, $description]): ?>
            <article class="card">
                <span class="muted"><?= e($label) ?></span>
                <h2 style="font-size:22px"><?= e($heading) ?></h2>
                <p class="muted"><?= e($description) ?></p>
            </article>
        <?php endforeach; ?>
    </section>

    <!-- ================= Account details ================= -->
    <section class="panel">
        <p class="kicker">Signed in as user</p>
        <h2>Welcome, <?= e($username) ?>!</h2>
        <p class="muted">These are the details saved to your BRIS account and resident record.</p>

        <div class="formgrid">
            <?php foreach ($accountFields as [$label, $value]): ?>
                <div class="field">
                    <label><?= e($label) ?></label>
                    <p><?= e($value) ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- ================= Resident information ================= -->
    <section class="panel" id="resident-information">
        <h2>Basic / Resident Information</h2>

        <?php if ($hasResident): ?>
            <div class="formgrid">
                <?php foreach ($residentFields as [$label, $value, $style]): ?>
                    <div class="field <?= $style === 'full' ? 'full' : '' ?>">
                        <label><?= e($label) ?></label>
                        <?php if ($style === 'badge'): ?>
                            <p><span class="badge"><?= e($value) ?></span></p>
                        <?php else: ?>
                            <p><?= e($value) ?></p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="alert danger">
                Your login account is not linked to a resident record.
                Please contact barangay staff to link your account.
            </div>
        <?php endif; ?>
    </section>

    <!-- ================= Household information ================= -->
    <section class="panel" id="household-information">
        <h2>Household Information</h2>

        <?php if ($hasResident && $hasHousehold): ?>
            <div class="formgrid">
                <?php foreach ($householdFields as [$label, $value, $style]): ?>
                    <div class="field <?= $style === 'full' ? 'full' : '' ?>">
                        <label><?= e($label) ?></label>
                        <p><?= e($value) ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="muted">No household is linked to this resident record yet.</p>
        <?php endif; ?>
    </section>
</main>
<?php end_page(); ?>