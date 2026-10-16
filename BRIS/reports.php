<?php
require_once 'config/functions.php';
require_role('staff');
require_once 'config/database.php';

// Collect report totals.
$totalResidents = (int) $pdo->query(
    'SELECT COUNT(*) FROM residents'
)->fetchColumn();

$totalHouseholds = (int) $pdo->query(
    'SELECT COUNT(*) FROM households'
)->fetchColumn();

// Load residents with their household codes.
$sql = "
    SELECT r.*, h.household_code
    FROM residents AS r
    LEFT JOIN households AS h ON h.id = r.household_id
    ORDER BY r.last_name ASC, r.first_name ASC
";

$residents = $pdo->query($sql)->fetchAll();

start_page('Reports');
?>

<div class="pagehead">
    <div class="wrap">
        <h1>Resident Summary Report</h1>
        <p class="muted">
            Print this page or choose Save as PDF in the print dialog.
        </p>
        <button class="btn primary no-print" type="button" onclick="window.print()">
            Print Report
        </button>
    </div>
</div>

<main class="wrap">
    <div class="panel">
        <div class="grid three">
            <div class="card">
                <span class="muted">Total Residents</span>
                <h2><?= $totalResidents ?></h2>
            </div>

            <div class="card">
                <span class="muted">Total Households</span>
                <h2><?= $totalHouseholds ?></h2>
            </div>

            <div class="card">
                <span class="muted">Report Date</span>
                <h2 style="font-size: 20px"><?= e(date('M d, Y')) ?></h2>
            </div>
        </div>

        <h2>Resident Records</h2>

        <div class="tablewrap">
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Address</th>
                        <th>Household</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($residents)): ?>
                        <tr>
                            <td colspan="4">No resident records available.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($residents as $resident): ?>
                            <tr>
                                <td>
                                    <?= e(trim(
                                        $resident['first_name'] . ' ' .
                                        ($resident['middle_name'] ?? '') . ' ' .
                                        $resident['last_name']
                                    )) ?>
                                </td>
                                <td><?= e($resident['address']) ?></td>
                                <td><?= e($resident['household_code'] ?? 'Not assigned') ?></td>
                                <td><?= e($resident['status']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<?php end_page(); ?>
