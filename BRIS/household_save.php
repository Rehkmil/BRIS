<?php
require_once 'config/functions.php';
require_role('staff');
require_once 'config/database.php';

// Only accept submissions from the household form.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: households.php');
    exit;
}

verify_token();

$code = strtoupper(trim($_POST['household_code'] ?? ''));
$address = trim($_POST['address'] ?? '');
$headName = trim($_POST['head_name'] ?? '') ?: null;

if ($code === '' || $address === '') {
    flash('Household code and complete address are required.', 'danger');
    header('Location: household_form.php');
    exit;
}

try {
    // Check whether this code is already being used.
    $check = $pdo->prepare('SELECT id FROM households WHERE household_code = ? LIMIT 1');
    $check->execute([$code]);

    if ($check->fetch()) {
        flash('That household code already exists. Please enter a different code.', 'danger');
        header('Location: household_form.php');
        exit;
    }

    $stmt = $pdo->prepare(
        'INSERT INTO households (household_code, address, head_name) VALUES (?, ?, ?)'
    );
    $stmt->execute([$code, $address, $headName]);

    flash('Household added successfully. You can now assign residents to it.');
    header('Location: households.php');
    exit;
} catch (PDOException $error) {
    // Avoid showing database details to the user.
    flash('The household could not be saved. Check the household code and try again.', 'danger');
    header('Location: household_form.php');
    exit;
}
