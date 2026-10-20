
<?php
require_once 'config/functions.php';
require_once 'config/database.php';

// Only logged-in staff can delete residents.
require_role('staff');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: residents.php');
    exit;
}

// Verify the form's security token.
verify_token();

// Validate the resident ID.
$residentId = filter_input(INPUT_POST, 'resident_id', FILTER_VALIDATE_INT);

if (!$residentId || $residentId < 1) {
    flash('Invalid resident selected.');
    header('Location: residents.php');
    exit;
}

try {
    // Check that the resident exists.
    $check = $pdo->prepare('SELECT id FROM residents WHERE id = ? LIMIT 1');
    $check->execute([$residentId]);

    if (!$check->fetch(PDO::FETCH_ASSOC)) {
        flash('Resident not found.');
        header('Location: residents.php');
        exit;
    }

    // Delete using a prepared statement.
    $delete = $pdo->prepare('DELETE FROM residents WHERE id = ?');
    $delete->execute([$residentId]);

    flash('Resident deleted successfully.');
} catch (PDOException $exception) {
    // Related records or database constraints may prevent deletion.
    flash('Unable to delete this resident. They may be linked to other records.');
}

header('Location: residents.php');
exit;
