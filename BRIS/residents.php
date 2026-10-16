<?php
require_once 'config/functions.php';
require_role('staff');
// 1. Configuration
require_once 'config/database.php';

// 2. Search and filter values
$q = trim($_GET['q'] ?? '');
$status = $_GET['status'] ?? '';
$allowedStatuses = ['Active', 'Inactive', 'Moved', 'Deceased'];

if (!in_array($status, $allowedStatuses, true)) {
    $status = '';
}

// 3. Build query
$sql = "
    SELECT r.*, h.household_code
    FROM residents AS r
    LEFT JOIN households AS h ON h.id = r.household_id
    WHERE 1 = 1
";
$params = [];

if ($q !== '') {
    $sql .= "
        AND (
            r.first_name LIKE ?
            OR r.middle_name LIKE ?
            OR r.last_name LIKE ?
            OR r.address LIKE ?
            OR r.email LIKE ?
        )
    ";

    $search = "%{$q}%";
    $params = [$search, $search, $search, $search, $search];
}

if ($status !== '') {
    $sql .= ' AND r.status = ?';
    $params[] = $status;
}

$sql .= ' ORDER BY r.last_name ASC, r.first_name ASC';

// 4. Execute query
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 5. Render page
start_page('Residents');
?>

<div class="pagehead">
    <div class="wrap">
        <h1>Resident Information</h1>
        <p class="muted">Search residents, filter by status, and update records.</p>
    </div>
</div>

<main class="wrap">
    <div class="panel">
        <?php show_flash(); ?>

        <div class="toolbar no-print">
            <form method="get" action="residents.php">
                <input
                    class="search"
                    type="search"
                    name="q"
                    placeholder="Search name, address, or email"
                    value="<?= e($q) ?>"
                >

                <select class="search" name="status" style="width:auto">
                    <option value="">All statuses</option>
                    <?php foreach ($allowedStatuses as $option): ?>
                        <option value="<?= e($option) ?>" <?= $status === $option ? 'selected' : '' ?>>
                            <?= e($option) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <button type="submit" class="btn primary">Search</button>
                <a class="btn" style="background:#edf1f7" href="residents.php">Reset</a>
            </form>

            <a class="btn primary" href="resident_form.php">+ Add Resident</a>
        </div>

        <p class="muted"><?= count($rows) ?> record(s) found.</p>

        <div class="tablewrap">
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Address</th>
                        <th>Household</th>
                        <th>Status</th>
                        <th class="no-print">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$rows): ?>
                        <tr><td colspan="5">No resident records found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($rows as $resident): ?>
                            <tr>
                                <td><?= e(trim(($resident['first_name'] ?? '') . ' ' . ($resident['middle_name'] ?? '') . ' ' . ($resident['last_name'] ?? ''))) ?></td>
                                <td><?= e($resident['address'] ?? '') ?></td>
                                <td><?= e($resident['household_code'] ?? 'Not assigned') ?></td>
                                <td>
                                    <span class="badge <?= ($resident['status'] ?? '') !== 'Active' ? 'inactive' : '' ?>">
                                        <?= e($resident['status'] ?? 'Unknown') ?>
                                    </span>
                                </td>
                                <td class="no-print">
                                    <a href="resident_form.php?id=<?= (int) $resident['id'] ?>">Edit</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<?php end_page(); ?>
