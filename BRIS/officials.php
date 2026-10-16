<?php
require_once 'config/functions.php';
require_once 'config/database.php';
require_role('staff');

// Start a session if one has not already been started.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Create a form security token.
if (empty($_SESSION['officials_csrf_token'])) {
    $_SESSION['officials_csrf_token'] = bin2hex(random_bytes(32));
}

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_official'])) {
    $token = $_POST['csrf_token'] ?? '';

    if (
        !is_string($token) ||
        !hash_equals($_SESSION['officials_csrf_token'], $token)
    ) {
        $message = 'Invalid form submission. Please refresh and try again.';
        $messageType = 'error';
    } else {
        $fullName = trim($_POST['full_name'] ?? '');
        $position = trim($_POST['position'] ?? '');
        $termStart = $_POST['term_start'] ?? '';
        $termEnd = $_POST['term_end'] ?? '';

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
        } elseif (
            ($termStart !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $termStart)) ||
            ($termEnd !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $termEnd))
        ) {
            $message = 'Please enter valid term dates.';
            $messageType = 'error';
        } elseif (
            $termStart !== '' &&
            $termEnd !== '' &&
            $termEnd < $termStart
        ) {
            $message = 'The term end date cannot be before the start date.';
            $messageType = 'error';
        } else {
            try {
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
                $messageType = 'success';

                // Prevent accidental duplicate submissions.
                $_SESSION['officials_csrf_token'] = bin2hex(random_bytes(32));
            } catch (PDOException $exception) {
                $message = 'Unable to save the official. Please try again.';
                $messageType = 'error';
            }
        }
    }
}

// Get officials from the database.
$officials = $pdo->query("
    SELECT id, full_name, position, status, term_start, term_end
    FROM barangay_officials
    ORDER BY
        CASE position
            WHEN 'Barangay Captain' THEN 1
            WHEN 'Kagawad' THEN 2
            WHEN 'SK Chairperson' THEN 3
            ELSE 4
        END,
        full_name ASC
")->fetchAll();

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
    --navy: #172b4d;
    --blue: #2864dc;
    --sky: #edf4ff;
    --ink: #1c2940;
    --muted: #6e7b91;
    --line: #e5eaf2;
    --surface: #fff;
}

body {
    background: #f4f7fb;
    color: var(--ink);
}

/* Main layout - no sidebar */
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

/* Dashboard header */
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

.dash-top p {
    color: var(--muted);
    margin: 0;
    font-size: 13px;
}

.date-pill {
    background: #fff;
    border: 1px solid var(--line);
    padding: 10px 14px;
    border-radius: 12px;
    color: #52617a;
    font-size: 12px;
    white-space: nowrap;
}

/* Dashboard metrics */
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
    box-shadow: 0 5px 18px #1b315008;
}

.metric-icon {
    height: 45px;
    width: 45px;
    border-radius: 13px;
    background: var(--sky);
    color: var(--blue);
    display: grid;
    place-items: center;
    font-size: 21px;
    flex-shrink: 0;
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

/* Section heading */
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
    margin: 5px 0 0;
    color: var(--muted);
    font-size: 12px;
}

/* Officials cards */
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
    box-shadow: 0 7px 22px #1b315008;
    min-width: 0;
}

.official-cover {
    height: 94px;
    background: linear-gradient(125deg, #214bb4, #4385f2);
    position: relative;
    padding: 17px 18px;
    color: #fff;
}

.official-cover.teal {
    background: linear-gradient(125deg, #087e8b, #32b8b5);
}

.official-cover.purple {
    background: linear-gradient(125deg, #5942b5, #9780f5);
}

.official-cover.orange {
    background: linear-gradient(125deg, #b75b23, #f3a34d);
}

.official-cover.slate {
    background: linear-gradient(125deg, #394960, #71829b);
}

.official-cover.green {
    background: linear-gradient(125deg, #167653, #42b68a);
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

/* Official avatars */
.avatar {
    width: 65px;
    height: 65px;
    border-radius: 20px;
    background: #eaf0fb;
    border: 4px solid #fff;
    display: grid;
    place-items: center;
    color: #284b91;
    font-size: 20px;
    font-weight: 800;
    position: absolute;
    left: 17px;
    bottom: -32px;
    box-shadow: 0 5px 12px #10264a18;
}

.avatar.a2 {
    background: #e3f8f4;
    color: #087e8b;
}

.avatar.a3 {
    background: #f0eaff;
    color: #5942b5;
}

.avatar.a4 {
    background: #fff0e2;
    color: #b75b23;
}

.avatar.a5 {
    background: #e9eef5;
    color: #394960;
}

.avatar.a6 {
    background: #e3f7ed;
    color: #167653;
}

/* Official details */
.official-body {
    padding: 42px 17px 17px;
}

.official-name {
    font-size: 15px;
    font-weight: 750;
    margin: 0 0 4px;
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
    gap: 8px;
    padding-top: 13px;
    border-top: 1px solid var(--line);
    font-size: 11px;
    color: var(--muted);
}

/* Official status */
.status-chip {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 5px 8px;
    border-radius: 20px;
    background: #e8f8ef;
    color: #16814a;
    font-weight: 700;
}

.status-chip:before {
    content: '';
    width: 6px;
    height: 6px;
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

/* Information note */
.placeholder-note {
    margin-top: 20px;
    padding: 14px 16px;
    background: #edf4ff;
    border: 1px solid #dbe8ff;
    color: #52698c;
    border-radius: 12px;
    font-size: 12px;
}

/* Quick links */
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


/* Add official form */
.add-official-panel {
    background: #fff;
    border: 1px solid var(--line);
    border-radius: 16px;
    padding: 22px;
    margin-bottom: 28px;
    box-shadow: 0 5px 18px #1b315008;
}

.add-official-panel h2 {
    margin: 0 0 6px;
    font-size: 18px;
}

.add-official-panel p {
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
}

.official-form .full-width {
    grid-column: 1 / -1;
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

.form-message {
    padding: 12px 14px;
    border-radius: 9px;
    margin-bottom: 16px;
    font-size: 13px;
}

.form-message.success {
    background: #e8f8ef;
    color: #16814a;
}

.form-message.error {
    background: #fff0ed;
    color: #b54735;
}

@media (max-width: 680px) {
    .official-form {
        grid-template-columns: 1fr;
    }

    .official-form .full-width {
        grid-column: auto;
    }
}


/* Tablet layout */
@media (max-width: 1000px) {
    .official-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .dashboard-main {
        padding: 22px;
    }

    .metric-grid {
        gap: 9px;
    }

    .metric {
        padding: 13px;
    }
}

/* Mobile layout */
@media (max-width: 680px) {
    .dashboard-main {
        padding: 18px 13px;
    }

    .dash-top {
        align-items: flex-start;
        flex-direction: column;
    }

    .date-pill {
        white-space: normal;
    }

    .metric-grid {
        grid-template-columns: 1fr;
    }

    .official-grid {
        grid-template-columns: 1fr;
    }

    .section-head {
        align-items: flex-start;
        flex-direction: column;
    }
}
</style>

<div class="dashboard-shell">
    <main class="dashboard-main">

        <!-- Page header -->
        <div class="dash-top">
            <div>
                <div class="eyebrow">Barangay directory</div>
                <h1>Meet your local officials</h1>
                <p>A simple overview of the people serving your community.</p>
            </div>

            <div class="date-pill">
                ● Demo workspace &nbsp; / &nbsp; Placeholder data
            </div>
        </div>

        
<!-- Add Official Form -->
<section class="add-official-panel">
    <h2>Add a Barangay Official</h2>
    <p>Enter the official's details to add them to the directory.</p>

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

        <div class="form-field">
            <label for="full_name">Full Name</label>
            <input
                type="text"
                id="full_name"
                name="full_name"
                maxlength="160"
                required
                placeholder="Enter the official's full name"
            >
        </div>

        <div class="form-field">
            <label for="position">Position</label>
            <select id="position" name="position" required>
                <option value="">Select a position</option>
                <option value="Barangay Captain">Barangay Captain</option>
                <option value="Kagawad">Kagawad</option>
                <option value="SK Chairperson">SK Chairperson</option>
                <option value="Secretary">Secretary</option>
                <option value="Treasurer">Treasurer</option>
            </select>
        </div>

        <div class="form-field">
            <label for="term_start">Term Start (optional)</label>
            <input type="date" id="term_start" name="term_start">
        </div>

        <div class="form-field">
            <label for="term_end">Term End (optional)</label>
            <input type="date" id="term_end" name="term_end">
        </div>

        <div class="full-width">
            <button type="submit" name="add_official" value="1">
                + Add Official
            </button>
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

        <!-- Officials directory heading -->
        <div class="section-head">
            <div>
                <h2>Official directory</h2>
                <p>
                    View the registered barangay officials and their current terms
                </p>
            </div>

            <div class="quick-links">
                <a href="residents.php">Browse residents ↗</a>
                <a href="reports.php">Open reports ↗</a>
            </div>
        </div>

        <!-- Officials cards -->
        <div class="official-grid">

            <?php
            $colors = ['', 'teal', 'purple', 'orange', 'slate', 'green'];
            $avatars = ['', 'a2', 'a3', 'a4', 'a5', 'a6'];

            foreach ($officials as $i => $official):

                $name = trim($official['full_name'] ?? 'Official Placeholder');
                $parts = preg_split('/\s+/', $name);

                $initials = strtoupper(
                    substr($parts[0] ?? 'O', 0, 1) .
                    (isset($parts[1]) ? substr($parts[1], 0, 1) : '')
                );

                $active = ($official['status'] ?? 'Active') === 'Active';

                $term = 'Term dates not set';

                if (
                    !empty($official['term_start']) ||
                    !empty($official['term_end'])
                ) {
                    $term =
                        (!empty($official['term_start'])
                            ? date('M Y', strtotime($official['term_start']))
                            : '—')
                        . ' – ' .
                        (!empty($official['term_end'])
                            ? date('M Y', strtotime($official['term_end']))
                            : 'Present');
                }
            ?>

                <article class="official-card">

                    <div class="official-cover <?= $colors[$i % count($colors)] ?>">

                        <div class="position-tag">
                            Community leadership
                        </div>

                        <h3>
                            <?= e($official['position'] ?? 'Official') ?>
                        </h3>

                        <div class="avatar <?= $avatars[$i % count($avatars)] ?>">
                            <?= e($initials) ?>
                        </div>

                    </div>

                    <div class="official-body">

                        <p class="official-name">
                            <?= e($name) ?>
                        </p>

                        <div class="official-term">
                            <?= e($term) ?>
                        </div>

                        <div class="official-meta">

                            <span class="status-chip <?= $active ? '' : 'inactive' ?>">
                                <?= e($official['status'] ?? 'Unknown') ?>
                            </span>

                            <a
                                class="view-link"
                                href="residents.php"
                                aria-label="Browse resident records"
                            >
                                Directory ↗
                            </a>

                        </div>

                    </div>

                </article>

            <?php endforeach; ?>

        </div>

        <?php if (empty($officials)): ?>
    <div class="placeholder-note">
        <strong>No officials added yet.</strong>
        Use the form above to add your first official.
    </div>
<?php endif; ?>

<?php end_page(); ?>
```