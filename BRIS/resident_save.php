<?php
require_once 'config/functions.php';
require_role('staff');
// 1. Configuration
require_once 'config/database.php';

// 2. Allow form submissions only
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: residents.php');
    exit;
}

verify_token();

// 3. Collect form data
$id = (int) ($_POST['id'] ?? 0);
$first = trim($_POST['first_name'] ?? '');
$middle = trim($_POST['middle_name'] ?? '');
$last = trim($_POST['last_name'] ?? '');
$birth = trim($_POST['birth_date'] ?? '') ?: null;
$sex = trim($_POST['sex'] ?? '') ?: null;
$phone = trim($_POST['phone'] ?? '') ?: null;
$email = trim($_POST['email'] ?? '') ?: null;
$address = trim($_POST['address'] ?? '');
$house = (int) ($_POST['household_id'] ?? 0) ?: null;
$status = $_POST['status'] ?? 'Active';

// 4. Validate input
$allowedStatuses = ['Active', 'Inactive', 'Moved', 'Deceased'];

if (
    $first === '' ||
    $last === '' ||
    $address === '' ||
    !in_array($status, $allowedStatuses, true) ||
    ($email !== null && !filter_var($email, FILTER_VALIDATE_EMAIL))
) {
    flash('Check required fields and email address.', 'danger');
    header('Location: ' . ($id > 0 ? 'resident_form.php?id=' . $id : 'resident_form.php'));
    exit;
}

// 5. Update or register resident
if ($id > 0) {
    $sql = '
        UPDATE residents SET
            first_name = ?, middle_name = ?, last_name = ?, birth_date = ?,
            sex = ?, phone = ?, email = ?, address = ?, household_id = ?, status = ?
        WHERE id = ?
    ';

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $first, $middle ?: null, $last, $birth, $sex,
        $phone, $email, $address, $house, $status, $id
    ]);

    flash('Resident record updated.');
} else {
    $sql = '
        INSERT INTO residents (
            first_name, middle_name, last_name, birth_date, sex,
            phone, email, address, household_id, status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ';

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $first, $middle ?: null, $last, $birth, $sex,
        $phone, $email, $address, $house, $status
    ]);

    flash('Resident registered successfully.');
}

// 6. Return to resident list
header('Location: residents.php');
exit;
