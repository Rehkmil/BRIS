<?php
require_once 'config/functions.php';
require_role('staff');
// 1. Configuration
require_once 'config/database.php';

// 2. Default form values
$id = (int) ($_GET['id'] ?? 0);
$resident = [
    'first_name' => '',
    'middle_name' => '',
    'last_name' => '',
    'birth_date' => '',
    'sex' => '',
    'phone' => '',
    'email' => '',
    'address' => '',
    'household_id' => '',
    'status' => 'Active'
];

// 3. Load resident when editing
if ($id > 0) {
    $stmt = $pdo->prepare('SELECT * FROM residents WHERE id = ?');
    $stmt->execute([$id]);
    $savedResident = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$savedResident) {
        http_response_code(404);
        exit('Resident not found.');
    }

    $resident = $savedResident;
}

// 4. Load household options
$households = $pdo->query(
    'SELECT id, household_code, address FROM households ORDER BY household_code'
)->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = $id > 0 ? 'Edit Resident' : 'Resident Registration';
start_page($pageTitle);
?>

<div class="pagehead">
    <div class="wrap">
        <h1><?= e($pageTitle) ?></h1>
        <p class="muted">Enter resident information below.</p>
    </div>
</div>

<main class="wrap">
    <div class="panel">
        <?php show_flash(); ?>

        <form action="resident_save.php" method="post">
            <input type="hidden" name="csrf" value="<?= e(token()) ?>">
            <input type="hidden" name="id" value="<?= $id ?>">

            <div class="formgrid">
                <div class="field">
                    <label for="first_name">First Name *</label>
                    <input id="first_name" type="text" name="first_name" maxlength="80" required value="<?= e($resident['first_name'] ?? '') ?>">
                </div>

                <div class="field">
                    <label for="middle_name">Middle Name</label>
                    <input id="middle_name" type="text" name="middle_name" maxlength="80" value="<?= e($resident['middle_name'] ?? '') ?>">
                </div>

                <div class="field">
                    <label for="last_name">Last Name *</label>
                    <input id="last_name" type="text" name="last_name" maxlength="80" required value="<?= e($resident['last_name'] ?? '') ?>">
                </div>

                <div class="field">
                    <label for="birth_date">Birth Date</label>
                    <input id="birth_date" type="date" name="birth_date" value="<?= e($resident['birth_date'] ?? '') ?>">
                </div>

                <div class="field">
                    <label for="sex">Sex</label>
                    <select id="sex" name="sex">
                        <option value="">Select</option>
                        <?php foreach (['Female', 'Male', 'Prefer not to say'] as $option): ?>
                            <option value="<?= e($option) ?>" <?= ($resident['sex'] ?? '') === $option ? 'selected' : '' ?>>
                                <?= e($option) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="field">
                    <label for="phone">Phone Number</label>
                    <input id="phone" type="tel" name="phone" maxlength="30" value="<?= e($resident['phone'] ?? '') ?>">
                </div>

                <div class="field">
                    <label for="email">Email Address</label>
                    <input id="email" type="email" name="email" maxlength="150" value="<?= e($resident['email'] ?? '') ?>">
                </div>

                <div class="field">
                    <label for="household_id">Household</label>
                    <select id="household_id" name="household_id">
                        <option value="">Not assigned</option>
                        <?php foreach ($households as $household): ?>
                            <option
                                value="<?= (int) $household['id'] ?>"
                                <?= (string) ($resident['household_id'] ?? '') === (string) $household['id'] ? 'selected' : '' ?>
                            >
                                <?= e($household['household_code'] . ' — ' . $household['address']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="field full">
                    <label for="address">Complete Address *</label>
                    <textarea id="address" name="address" maxlength="255" required><?= e($resident['address'] ?? '') ?></textarea>
                </div>

                <div class="field">
                    <label for="status">Resident Status</label>
                    <select id="status" name="status">
                        <?php foreach (['Active', 'Inactive', 'Moved', 'Deceased'] as $option): ?>
                            <option value="<?= e($option) ?>" <?= ($resident['status'] ?? 'Active') === $option ? 'selected' : '' ?>>
                                <?= e($option) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="actions" style="justify-content:flex-start">
                <button type="submit" class="btn primary">Save Resident</button>
                <a class="btn" style="background:#edf1f7" href="residents.php">Cancel</a>
            </div>
        </form>
    </div>
</main>

<?php end_page(); ?>
