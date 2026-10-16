<?php
require_once 'config/functions.php';
require_role('staff');
require_once 'config/database.php';

// Get households and count residents in each household.
$sql = "
    SELECT
        h.*,
        COUNT(r.id) AS member_count
    FROM households AS h
    LEFT JOIN residents AS r ON r.household_id = h.id
    GROUP BY h.id
    ORDER BY h.household_code ASC
";

$households = $pdo->query($sql)->fetchAll();

start_page('Households');
?>

<div class="pagehead">
    <div class="wrap">
        <h1>Household Records</h1>
        <p class="muted">Household records and their registered residents.</p>
        <p><a class="btn primary" href="household_form.php">+ Add Household</a></p>
    </div>
</div>

<main class="wrap">
    <div class="panel">
        <p class="muted">
            <?= count($households) ?> household(s) recorded.
        </p>

        <div class="tablewrap">
            <table>
                <thead>
                    <tr>
                        <th>Household Code</th>
                        <th>Address</th>
                        <th>Head of Household</th>
                        <th>Members</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($households)): ?>
                        <tr>
                            <td colspan="4">
                                No households found yet. Click “Add Household” to register the first household.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($households as $household): ?>
                            <tr>
                                <td><?= e($household['household_code']) ?></td>
                                <td><?= e($household['address']) ?></td>
                                <td><?= e($household['head_name'] ?? 'Not specified') ?></td>
                                <td><?= (int) $household['member_count'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div>
</main>

<?php end_page(); ?>
