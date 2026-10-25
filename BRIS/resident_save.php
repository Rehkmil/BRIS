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

$allowedStatuses = ['Active', 'Inactive', 'Moved', 'Deceased'];

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
/*
 * Validate birth date.
 * Accept only real dates in YYYY-MM-DD format.
 */
if ($birth !== null) {
    $date = DateTime::createFromFormat('!Y-m-d', $birth);
    $dateErrors = DateTime::getLastErrors();

    if (
        !$date ||
        ($dateErrors !== false &&
            ($dateErrors['warning_count'] > 0 ||
             $dateErrors['error_count'] > 0)) ||
        $date->format('Y-m-d') !== $birth ||
        $date > new DateTime('today')
    ) {
        flash('Enter a valid birth date that is not in the future.', 'danger');

        header('Location: ' .
            ($id > 0
                ? 'resident_form.php?id=' . $id
                : 'resident_form.php'));

        exit;
    }
}



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

if (
    $phone !== null &&
    (
        !preg_match('/^[0-9+\-() ]+$/', $phone) ||
        strlen(preg_replace('/\D/', '', $phone)) < 7 ||
        strlen(preg_replace('/\D/', '', $phone)) > 15
    )
) {
    flash('Enter a valid phone number using 7–15 digits. You may use +, spaces, hyphens, or parentheses.', 'danger');
    header('Location: ' . ($id > 0 ? 'resident_form.php?id=' . $id : 'resident_form.php'));
    exit;
}

// Check whether another resident already uses this email.
if ($email !== null) {
    $checkEmail = $pdo->prepare(
        'SELECT id FROM residents WHERE email = ? AND id != ? LIMIT 1'
    );
    $checkEmail->execute([$email, $id]);

    if ($checkEmail->fetch(PDO::FETCH_ASSOC)) {
        flash('This email address is already assigned to another resident.', 'danger');
        header('Location: ' . ($id > 0 ? 'resident_form.php?id=' . $id : 'resident_form.php'));
        exit;
    }
}

if ($house !== null) {
    $checkHouse = $pdo->prepare(
        'SELECT id FROM households WHERE id = ? LIMIT 1'
    );
    $checkHouse->execute([$house]);

    if (!$checkHouse->fetch(PDO::FETCH_ASSOC)) {
        flash('The selected household does not exist. Please select a valid household.', 'danger');
        header('Location: ' . ($id > 0 ? 'resident_form.php?id=' . $id : 'resident_form.php'));
        exit;
    }
}

try {
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
} catch (PDOException $exception) {
    error_log('Resident save failed: ' . $exception->getMessage());

    flash('Unable to save the resident record. Please check the information and try again.', 'danger');
    header('Location: ' . ($id > 0 ? 'resident_form.php?id=' . $id : 'resident_form.php'));
    exit;
}


// 6. Return to resident list
header('Location: residents.php');
exit;
