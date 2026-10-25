
<?php
require_once 'config/functions.php';
require_once 'config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_role('staff');

// Create a CSRF security token.
if (empty($_SESSION['officials_csrf_token'])) {
    $_SESSION['officials_csrf_token'] = bin2hex(random_bytes(32));
}

$message = '';
$messageType = '';

// Validate a date in YYYY-MM-DD format.
$isValidDate = static function ($date) {
    if ($date === '') {
        return true;
    }

    $dateObject = DateTime::createFromFormat('!Y-m-d', $date);

    return $dateObject !== false
        && $dateObject->format('Y-m-d') === $date;
};

// Handle adding or editing an official.
if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && (
        isset($_POST['add_official'])
        || isset($_POST['update_official'])
    )
) {
    $token = $_POST['csrf_token'] ?? '';

    if (
        !is_string($token)
        || !hash_equals($_SESSION['officials_csrf_token'], $token)
    ) {
        $message = 'Invalid form submission. Please refresh and try again.';
        $messageType = 'error';
    } else {
        $fullName = trim($_POST['full_name'] ?? '');
        $position = trim($_POST['position'] ?? '');
        $termStart = trim($_POST['term_start'] ?? '');
        $termEnd = trim($_POST['term_end'] ?? '');

        $isEditing = isset($_POST['update_official']);
        $officialId = filter_var(
            $_POST['official_id'] ?? null,
            FILTER_VALIDATE_INT
        );

        $allowedPositions = [
            'Barangay Captain',
            'Kagawad',
            'SK Chairperson',
            'Secretary',
            'Treasurer'
        ];

        if ($fullName === '' || mb_strlen($fullName) > 160) {
            $message = 'Please enter a name with no more than 160 characters.';
            $messageType = 'error';

        } elseif (!in_array($position, $allowedPositions, true)) {
            $message = 'Please select a valid official position.';
            $messageType = 'error';

        } elseif (!$isValidDate($termStart) || !$isValidDate($termEnd)) {
            $message = 'Please enter valid term dates.';
            $messageType = 'error';

        } elseif (
            $termStart !== ''
            && $termEnd !== ''
            && $termEnd < $termStart
        ) {
            $message = 'The term end date cannot be before the start date.';
            $messageType = 'error';

        } else {
            try {
                if ($isEditing) {
                    if (!$officialId || $officialId < 1) {
                        throw new InvalidArgumentException(
                            'Invalid official ID.'
                        );
                    }

                    // Make sure the official exists before updating.
                    $check = $pdo->prepare(
                        'SELECT id FROM barangay_officials WHERE id = ? LIMIT 1'
                    );
                    $check->execute([$officialId]);

                    if (!$check->fetchColumn()) {
                        throw new InvalidArgumentException(
                            'Official not found.'
                        );
                    }

                    $stmt = $pdo->prepare("
                        UPDATE barangay_officials
                        SET full_name = ?,
                            position = ?,
                            term_start = ?,
                            term_end = ?
                        WHERE id = ?
                    ");

                    $stmt->execute([
                        $fullName,
                        $position,
                        $termStart !== '' ? $termStart : null,
                        $termEnd !== '' ? $termEnd : null,
                        $officialId
                    ]);

                    $message = 'Official updated successfully!';

                } else {
                    $stmt = $pdo->prepare("
                        INSERT INTO barangay_officials
                            (full_name, position, term_start, term_end, status)
                        VALUES (?, ?, ?, ?, 'Active')
                    ");

                    $stmt->execute([
                        $fullName,
                        $position,
                        $termStart !== '' ? $termStart : null,
                        $termEnd !== '' ? $termEnd : null
                    ]);

                    $message = 'Official added successfully!';
                }

                $messageType = 'success';

                // Use a fresh token after a successful submission.
                $_SESSION['officials_csrf_token'] = bin2hex(random_bytes(32));

            } catch (InvalidArgumentException $exception) {
                $message = 'The selected official could not be found. Please try again.';
                $messageType = 'error';

            } catch (PDOException $exception) {
                error_log('Official save failed: ' . $exception->getMessage());
                $message = 'Unable to save the official. Please try again.';
                $messageType = 'error';
            }
        }
    }
}

// Load the official being edited, if an edit ID is provided.
$editOfficial = null;

if (isset($_GET['edit'])) {
    $editId = filter_var($_GET['edit'], FILTER_VALIDATE_INT);

    if ($editId && $editId > 0) {
        $stmt = $pdo->prepare("
            SELECT id, full_name, position, term_start, term_end
            FROM barangay_officials
            WHERE id = ?
            LIMIT 1
        ");

        $stmt->execute([$editId]);
        $editOfficial = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$editOfficial) {
            $message = 'Official not found.';
            $messageType = 'error';
        }
    } else {
        $message = 'Invalid official ID.';
        $messageType = 'error';
    }
}

// Get the officials directory.
$officials = $pdo->query("
    SELECT id, full_name, position, status, term_start, term_end
    FROM barangay_officials
    ORDER BY
        CASE position
            WHEN 'Barangay Captain' THEN 1
            WHEN 'Kagawad' THEN 2
            WHEN 'SK Chairperson' THEN 3
            WHEN 'Secretary' THEN 4
            WHEN 'Treasurer' THEN 5
            ELSE 6
        END,
        full_name ASC
")->fetchAll(PDO::FETCH_ASSOC);

// Get dashboard counts.
$residentCount = (int) $pdo->query(
    "SELECT COUNT(*) FROM residents"
)->fetchColumn();

$householdCount = (int) $pdo->query(
    "SELECT COUNT(*) FROM households"
)->fetchColumn();

$activeCount = (int) $pdo->query(
    "SELECT COUNT(*) FROM residents WHERE status = 'Active'"
)->fetchColumn();

start_page('Barangay Officials');
?>

<style>
:root {
    --navy: #1c3023;
    --blue: #16804a;
    --sky: #e5f6eb;
    --ink: #1c3023;
    --muted: #6e7b91;
    --line: #dfe9e1;
}

body {
    background: #f3f8f4;
    color: var(--ink);
}

.dashboard-shell {
    display: block;
    min-height: calc(100vh - 70px);
    width: 100%;
}

.dashboard-main {
    padding: 28px;
    min-width: 0;
    max-width: 1400px;
    margin: 0 auto;
}

.dash-top {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 18px;
    margin-bottom: 26px;
}

.eyebrow {
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 1.5px;
    color: var(--blue);
    text-transform: uppercase;
}

.dash-top h1 {
    font-size: 27px;
    margin: 5px 0 7px;
    letter-spacing: -.7px;
}

.dash-top p,
.section-head p {
    color: var(--muted);
    margin: 5px 0 0;
    font-size: 13px;
}

.metric-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 14px;
    margin-bottom: 27px;
}

.metric {
    background: #fff;
    border: 1px solid var(--line);
    border-radius: 15px;
    padding: 18px;
    display: flex;
    align-items: center;
    gap: 14px;
    box-shadow: 0 5px 18px #174b2a0a;
    min-width: 0;
}

.metric-icon {
    height: 45px;
    width: 45px;
    flex-shrink: 0;
    border-radius: 13px;
    background: var(--sky);
    color: var(--blue);
    display: grid;
    place-items: center;
    font-size: 21px;
}

.metric span {
    display: block;
    color: var(--muted);
    font-size: 12px;
}

.metric strong {
    display: block;
    font-size: 25px;
    line-height: 1.4;
}

.section-head {
    display: flex;
    justify-content: space-between;
    align-items: end;
    gap: 15px;
    margin-bottom: 15px;
}

.section-head h2 {
    margin: 0;
    font-size: 18px;
}

.section-head p {
    font-size: 12px;
}

.official-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 16px;
}

.official-card {
    background: #fff;
    border: 1px solid var(--line);
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 7px 22px #174b2a0a;
    min-width: 0;
}

.official-cover {
    height: 94px;
    background: linear-gradient(125deg, #16804a, #2a9a60);
    position: relative;
    padding: 17px 18px;
    color: #fff;
}

.official-cover.teal {
    background: linear-gradient(125deg, #16804a, #e87924);
}

.official-cover.purple {
    background: linear-gradient(125deg, #e87924, #f3a34d);
}

.official-cover.orange {
    background: linear-gradient(125deg, #b75b23, #f3a34d);
}

.official-cover.slate {
    background: linear-gradient(125deg, #394960, #71829b);
}

.official-cover.green {
    background: linear-gradient(125deg, #17623b, #2a9a60);
}

.position-tag {
    font-size: 10px;
    letter-spacing: 1px;
    text-transform: uppercase;
    opacity: .85;
}

.official-cover h3 {
    font-size: 16px;
    margin: 7px 0 0;
    font-weight: 650;
}

.avatar {
    width: 65px;
    height: 65px;
    box-sizing: border-box;
    border-radius: 20px;
    background: #edf6ef;
    border: 4px solid #fff;
    display: grid;
    place-items: center;
    color: #0d5c35;
    font-size: 20px;
    font-weight: 800;
    position: absolute;
    left: 17px;
    bottom: -32px;
    box-shadow: 0 5px 12px #174b2a12;
}

.avatar.a2 { background: #e5f6eb; color: #16804a; }
.avatar.a3 { background: #fff0df; color: #e87924; }
.avatar.a4 { background: #fff0e2; color: #b75b23; }
.avatar.a5 { background: #f3f8f4; color: #394960; }
.avatar.a6 { background: #e3f7ed; color: #17623b; }

.official-body {
    padding: 42px 17px 17px;
}

.official-name {
    font-size: 15px;
    font-weight: 750;
    margin: 0 0 4px;
    overflow-wrap: anywhere;
}

.official-term {
    font-size: 11px;
    color: var(--muted);
    margin-bottom: 15px;
}

.official-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 8px;
    padding-top: 13px;
    border-top: 1px solid var(--line);
    font-size: 11px;
    color: var(--muted);
}

.status-chip {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 5px 8px;
    border-radius: 20px;
    background: #e5f6eb;
    color: #16804a;
    font-weight: 700;
}

.status-chip:before {
    content: '';
    width: 6px;
    height: 6px;
    flex-shrink: 0;
    border-radius: 50%;
    background: #20a464;
}

.status-chip.inactive {
    background: #fff0ed;
    color: #b54735;
}

.status-chip.inactive:before {
    background: #d75a49;
}

.view-link {
    font-weight: 700;
    color: var(--blue);
    text-decoration: none;
}

.view-link:hover {
    text-decoration: underline;
}

.quick-links {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.quick-links a {
    font-size: 12px;
    text-decoration: none;
    color: var(--blue);
    background: #fff;
    border: 1px solid var(--line);
    padding: 9px 12px;
    border-radius: 10px;
    font-weight: 650;
}

.add-official-panel {
    background: #fff;
    border: 1px solid var(--line);
    border-radius: 16px;
    padding: 22px;
    margin-bottom: 28px;
    box-shadow: 0 5px 18px #174b2a0a;
}

.add-official-panel h2 {
    margin: 0 0 6px;
    font-size: 18px;
}

.add-official-panel > p {
    margin: 0 0 18px;
    color: var(--muted);
    font-size: 13px;
}

.official-form {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 15px;
}

.official-form .form-field {
    display: flex;
    flex-direction: column;
    gap: 7px;
    min-width: 0;
}

.official-form label {
    font-size: 12px;
    font-weight: 700;
}

.official-form input,
.official-form select {
    width: 100%;
    box-sizing: border-box;
    padding: 11px;
    border: 1px solid var(--line);
    border-radius: 9px;
    font: inherit;
    background: #fff;
    color: var(--ink);
}

.official-form .full-width {
    grid-column: 1 / -1;
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 12px;
}

.official-form button {
    border: 0;
    border-radius: 9px;
    padding: 12px 16px;
    background: var(--blue);
    color: #fff;
    font-weight: 700;
    cursor: pointer;
}

.official-form button:hover {
    background: #10683b;
}

.cancel-link {
    color: var(--muted);
    font-size: 13px;
    text-decoration: none;
}

.cancel-link:hover {
    text-decoration: underline;
}

.form-message {
    padding: 12px 14px;
    border-radius: 9px;
    margin-bottom: 16px;
    font-size: 13px;
}

.form-message.success {
    background: #e5f6eb;
    color: #16804a;
}

.form-message.error {
    background: #fff0ed;
    color: #b54735;
}

.empty-state {
    grid-column: 1 / -1;
    padding: 28px;
    text-align: center;
    background: #fff;
    border: 1px solid var(--line);
    border-radius: 16px;
    color: var(--muted);
}

@media (max-width: 1000px) {
    .official-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .dashboard-main {
        padding: 22px;
    }

    .metric {
        padding: 13px;
    }
}

@media (max-width: 680px) {
    .dashboard-main {
        padding: 18px 13px;
    }

    .dash-top,
    .section-head {
        align-items: flex-start;
        flex-direction: column;
    }

    .metric-grid,
    .official-grid,
    .official-form {
        grid-template-columns: 1fr;
    }

    .official-form .full-width {
        grid-column: auto;
    }
}
</style>

<div class="dashboard-shell">
    <main class="dashboard-main">

        <div class="dash-top">
            <div>
                <div class="eyebrow">Barangay directory</div>
                <h1>Meet your local officials</h1>
                <p>A simple overview of the people serving your community.</p>
            </div>
        </div>

        <!-- Add or edit official form -->
        <section class="add-official-panel">
            <h2>
                <?= $editOfficial ? 'Edit Barangay Official' : 'Add a Barangay Official' ?>
            </h2>

            <p>
                <?= $editOfficial
                    ? 'Update the official’s details below.'
                    : 'Enter the official’s details to add them to the directory.' ?>
            </p>

            <?php if ($message !== ''): ?>
                <div class="form-message <?= e($messageType) ?>">
                    <?= e($message) ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="official-form">
                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= e($_SESSION['officials_csrf_token']) ?>"
                >

                <?php if ($editOfficial): ?>
                    <input
                        type="hidden"
                        name="official_id"
                        value="<?= (int) $editOfficial['id'] ?>"
                    >
                <?php endif; ?>

                <div class="form-field">
                    <label for="full_name">Full Name</label>
                    <input
                        type="text"
                        id="full_name"
                        name="full_name"
                        maxlength="160"
                        required
                        placeholder="Enter the official's full name"
                        value="<?= e($editOfficial['full_name'] ?? '') ?>"
                    >
                </div>

                <div class="form-field">
                    <label for="position">Position</label>
                    <select id="position" name="position" required>
                        <option value="">Select a position</option>

                        <?php
                        $positions = [
                            'Barangay Captain',
                            'Kagawad',
                            'SK Chairperson',
                            'Secretary',
                            'Treasurer'
                        ];
                        ?>

                        <?php foreach ($positions as $positionOption): ?>
                            <option
                                value="<?= e($positionOption) ?>"
                                <?= ($editOfficial['position'] ?? '') === $positionOption
                                    ? 'selected'
                                    : '' ?>
                            >
                                <?= e($positionOption) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-field">
                    <label for="term_start">Term Start (optional)</label>
                    <input
                        type="date"
                        id="term_start"
                        name="term_start"
                        value="<?= e($editOfficial['term_start'] ?? '') ?>"
                    >
                </div>

                <div class="form-field">
                    <label for="term_end">Term End (optional)</label>
                    <input
                        type="date"
                        id="term_end"
                        name="term_end"
                        value="<?= e($editOfficial['term_end'] ?? '') ?>"
                    >
                </div>

                <div class="full-width">
                    <button
                        type="submit"
                        name="<?= $editOfficial ? 'update_official' : 'add_official' ?>"
                        value="1"
                    >
                        <?= $editOfficial ? 'Save Changes' : '+ Add Official' ?>
                    </button>

                    <?php if ($editOfficial): ?>
                        <a class="cancel-link" href="officials.php">
                            Cancel Editing
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </section>

        <!-- Summary cards -->
        <div class="metric-grid">
            <div class="metric">
                <div class="metric-icon">♙</div>
                <div>
                    <span>Directory entries</span>
                    <strong><?= number_format(count($officials)) ?></strong>
                </div>
            </div>

            <div class="metric">
                <div class="metric-icon">♧</div>
                <div>
                    <span>Resident records</span>
                    <strong><?= number_format($residentCount) ?></strong>
                </div>
            </div>

            <div class="metric">
                <div class="metric-icon">⌂</div>
                <div>
                    <span>Households</span>
                    <strong><?= number_format($householdCount) ?></strong>
                </div>
            </div>
        </div>

        <!-- Directory heading -->
        <div class="section-head">
            <div>
                <h2>Official directory</h2>
                <p>View registered barangay officials and their current terms.</p>
            </div>

            <div class="quick-links">
                <a href="residents.php">Browse residents ↗</a>
                <a href="reports.php">Open reports ↗</a>
            </div>
        </div>

        <!-- Official cards -->
        <div class="official-grid">
            <?php if (empty($officials)): ?>
                <div class="empty-state">
                    No barangay officials have been added yet.
                    Use the form above to add the first official.
                </div>
            <?php else: ?>

                <?php
                $colors = ['', 'teal', 'purple', 'orange', 'slate', 'green'];
                $avatars = ['', 'a2', 'a3', 'a4', 'a5', 'a6'];
                ?>

                <?php foreach ($officials as $i => $official): ?>
                    <?php
                    $name = trim($official['full_name'] ?? 'Official');
                    $parts = preg_split('/\s+/', $name);

                    $initials = strtoupper(
                        substr($parts[0] ?? 'O', 0, 1)
                        . (isset($parts[1]) ? substr($parts[1], 0, 1) : '')
                    );

                    // The term is considered ended the day after its end date.
                    $termExpired = !empty($official['term_end'])
                        && $official['term_end'] < date('Y-m-d');

                    $displayStatus = $termExpired
                        ? 'Term Ended'
                        : ($official['status'] ?? 'Unknown');

                    $active = !$termExpired
                        && ($official['status'] ?? '') === 'Active';

                    $term = 'Term dates not set';

                    if (
                        !empty($official['term_start'])
                        || !empty($official['term_end'])
                    ) {
                        $term =
                            (!empty($official['term_start'])
                                ? date('M Y', strtotime($official['term_start']))
                                : '—')
                            . ' – '
                            . (!empty($official['term_end'])
                                ? date('M Y', strtotime($official['term_end']))
                                : 'Present');
                    }
                    ?>

                    <article class="official-card">
                        <div class="official-cover <?= $colors[$i % count($colors)] ?>">
                            <div class="position-tag">Community leadership</div>

                            <h3><?= e($official['position'] ?? 'Official') ?></h3>

                            <div class="avatar <?= $avatars[$i % count($avatars)] ?>">
                                <?= e($initials) ?>
                            </div>
                        </div>

                        <div class="official-body">
                            <p class="official-name"><?= e($name) ?></p>

                            <div class="official-term">
                                <?= e($term) ?>
                            </div>

                            <div class="official-meta">
                                <span class="status-chip <?= $active ? '' : 'inactive' ?>">
                                    <?= e($displayStatus) ?>
                                </span>

                                <a
                                    class="view-link"
                                    href="officials.php?edit=<?= (int) $official['id'] ?>"
                                    aria-label="Edit <?= e($name) ?>"
                                >
                                    Edit Official ↗
                                </a>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>

            <?php endif; ?>
        </div>

    </main>
</div>

<?php end_page(); ?>
