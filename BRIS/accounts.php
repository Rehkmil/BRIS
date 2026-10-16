<?php
require_once 'config/functions.php';
require_once 'config/database.php';
require_role('staff');

// Roles a staff member can assign: [value => label shown in the dropdown]
const ACCOUNTS_ROLES = [
    'user'  => 'User / Resident',
    'staff' => 'Staff',
];

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

/** True when a resident record with this id exists. */
function accounts_resident_exists(PDO $pdo, int $residentId): bool
{
    $stmt = $pdo->prepare('SELECT id FROM residents WHERE id = ?');
    $stmt->execute([$residentId]);
    return (bool) $stmt->fetch();
}

/**
 * Links (or unlinks, when $residentId is null) a user account to a resident.
 * Returns an error message, or '' on success.
 */
function accounts_link_resident(PDO $pdo, int $accountId, ?int $residentId): string
{
    $stmt = $pdo->prepare('SELECT id, role FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$accountId]);
    $target = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$target || $target['role'] !== 'user') {
        return 'Only an existing user account can be linked to a resident profile.';
    }
    if ($residentId !== null && !accounts_resident_exists($pdo, $residentId)) {
        return 'Select a resident record that exists.';
    }

    try {
        $update = $pdo->prepare("UPDATE users SET resident_id = ? WHERE id = ? AND role = 'user'");
        $update->execute([$residentId, $accountId]);
    } catch (PDOException $exception) {
        return 'This resident record may already be linked to another account.';
    }

    return '';
}

/**
 * Validates and creates a new login account.
 * $new needs: username, email, password, role, resident_id (int or null).
 * Returns an error message, or '' on success.
 */
function accounts_create(PDO $pdo, array $new): string
{
    if (!preg_match('/^[a-z0-9._-]{3,40}$/', $new['username'])) {
        return 'Username must be 3–40 characters and may use letters, numbers, dots, underscores, or hyphens.';
    }
    if (!filter_var($new['email'], FILTER_VALIDATE_EMAIL) || strlen($new['email']) > 150) {
        return 'Enter a valid email address.';
    }
    if (strlen($new['password']) < 8) {
        return 'Password must contain at least 8 characters.';
    }
    if (!in_array($new['role'], array_keys(ACCOUNTS_ROLES), true)) {
        return 'Select a valid account role.';
    }
    if ($new['role'] === 'staff' && $new['resident_id'] !== null) {
        return 'Staff accounts cannot be linked to resident profiles.';
    }
    if ($new['resident_id'] !== null && !accounts_resident_exists($pdo, $new['resident_id'])) {
        return 'Select a resident record that exists.';
    }

    $check = $pdo->prepare('SELECT id FROM users WHERE username = ? OR email = ? LIMIT 1');
    $check->execute([$new['username'], $new['email']]);
    if ($check->fetch()) {
        return 'That username or email is already registered.';
    }

    try {
        $insert = $pdo->prepare(
            'INSERT INTO users (username, email, password_hash, role, resident_id, is_active)
             VALUES (?, ?, ?, ?, ?, 1)'
        );
        $insert->execute([
            $new['username'],
            $new['email'],
            password_hash($new['password'], PASSWORD_DEFAULT),
            $new['role'],
            $new['resident_id'],
        ]);
    } catch (PDOException $exception) {
        return 'Account could not be created. The username, email, or resident profile may already be linked.';
    }

    return '';
}

// ---------------------------------------------------------------------------
// Form submission
// ---------------------------------------------------------------------------
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_token();
    $action = $_POST['action'] ?? 'create';

    if ($action === 'link_resident') {
        // Save or remove the resident link on an existing account.
        $accountId  = (int) ($_POST['account_id'] ?? 0);
        $residentId = (int) ($_POST['resident_id'] ?? 0) ?: null;

        $error = accounts_link_resident($pdo, $accountId, $residentId);

        if ($error === '') {
            flash($residentId === null ? 'Resident profile link removed.' : 'Resident profile linked to account.');
            header('Location: accounts.php');
            exit;
        }
    } else {
        // Create a new account.
        $error = accounts_create($pdo, [
            'username'    => strtolower(trim($_POST['username'] ?? '')),
            'email'       => strtolower(trim($_POST['email'] ?? '')),
            'password'    => (string) ($_POST['password'] ?? ''),
            'role'        => $_POST['role'] ?? 'user',
            'resident_id' => (int) ($_POST['resident_id'] ?? 0) ?: null,
        ]);

        if ($error === '') {
            flash('Account created successfully.');
            header('Location: accounts.php');
            exit;
        }
    }
}

// ---------------------------------------------------------------------------
// Data for the page
// ---------------------------------------------------------------------------
$residents = $pdo->query(
    'SELECT id, first_name, middle_name, last_name
     FROM residents
     ORDER BY last_name, first_name'
)->fetchAll(PDO::FETCH_ASSOC);

$accounts = $pdo->query(
    'SELECT u.id, u.username, u.email, u.role, u.is_active, u.created_at, u.resident_id,
            r.first_name, r.middle_name, r.last_name
     FROM users u
     LEFT JOIN residents r ON r.id = u.resident_id
     ORDER BY u.created_at DESC, u.id DESC'
)->fetchAll(PDO::FETCH_ASSOC);

// Resident dropdown choices, built once and reused by both forms:
//   'label' - used in the "Create Account" form
//   'short' - used in the per-account "Manage link" dropdowns
$residentChoices = array_map(function (array $r): array {
    $id = (int) $r['id'];
    return [
        'id'    => $id,
        'label' => trim($r['last_name'] . ', ' . $r['first_name'] . ' ' . ($r['middle_name'] ?? '')) . ' — ID ' . $id,
        'short' => $r['last_name'] . ', ' . $r['first_name'] . ' (ID ' . $id . ')',
    ];
}, $residents);

start_page('Account Management');
?>
<!-- ================= Page header ================= -->
<div class="pagehead">
    <div class="wrap">
        <h1>Account Management</h1>
        <p class="muted">Create user or staff login accounts and link user accounts to resident records.</p>
    </div>
</div>

<main class="wrap">

    <!-- ================= Create account ================= -->
    <section class="panel">
        <?php show_flash(); ?>
        <?php if ($error !== ''): ?>
            <div class="alert danger" role="alert"><?= e($error) ?></div>
        <?php endif; ?>

        <h2>Create Account</h2>
        <form method="post" action="accounts.php">
            <input type="hidden" name="csrf" value="<?= e(token()) ?>">
            <input type="hidden" name="action" value="create">

            <div class="formgrid">
                <div class="field">
                    <label for="username">Username</label>
                    <input id="username" name="username" minlength="3" maxlength="40" required>
                </div>
                <div class="field">
                    <label for="email">Email</label>
                    <input id="email" name="email" type="email" maxlength="150" required>
                </div>
                <div class="field">
                    <label for="password">Initial password</label>
                    <input id="password" name="password" type="password" minlength="8" required>
                    <small>At least 8 characters. Share it privately with the account holder.</small>
                </div>
                <div class="field">
                    <label for="role">Role</label>
                    <select id="role" name="role" required>
                        <?php foreach (ACCOUNTS_ROLES as $value => $label): ?>
                            <option value="<?= e($value) ?>"><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field full">
                    <label for="resident_id">Link to resident record (optional, user accounts only)</label>
                    <select id="resident_id" name="resident_id">
                        <option value="">Do not link</option>
                        <?php foreach ($residentChoices as $choice): ?>
                            <option value="<?= $choice['id'] ?>"><?= e($choice['label']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <button class="btn primary" type="submit">Create account</button>
        </form>
    </section>

    <!-- ================= Existing accounts ================= -->
    <section class="panel">
        <h2>Existing Accounts</h2>
        <p class="muted">
            Passwords and password hashes are never displayed.
            A resident record can be linked to one user account.
        </p>

        <div class="tablewrap">
            <table>
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Linked resident</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Manage link</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$accounts): ?>
                        <tr>
                            <td colspan="7">No accounts found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($accounts as $account):
                            $isActive   = (bool) $account['is_active'];
                            $linkedName = !empty($account['first_name'])
                                ? trim($account['first_name'] . ' ' . ($account['middle_name'] ?? '') . ' ' . $account['last_name'])
                                : '—';
                        ?>
                            <tr>
                                <td><?= e($account['username']) ?></td>
                                <td><?= e($account['email']) ?></td>
                                <td><?= e(ucfirst($account['role'])) ?></td>
                                <td><?= e($linkedName) ?></td>
                                <td>
                                    <span class="badge <?= $isActive ? '' : 'inactive' ?>">
                                        <?= $isActive ? 'Active' : 'Inactive' ?>
                                    </span>
                                </td>
                                <td><?= e($account['created_at']) ?></td>
                                <td>
                                    <?php if ($account['role'] === 'user'): ?>
                                        <form method="post" action="accounts.php" class="link-account-form">
                                            <input type="hidden" name="csrf" value="<?= e(token()) ?>">
                                            <input type="hidden" name="action" value="link_resident">
                                            <input type="hidden" name="account_id" value="<?= (int) $account['id'] ?>">

                                            <select name="resident_id" aria-label="Resident profile for <?= e($account['username']) ?>">
                                                <option value="">No linked profile</option>
                                                <?php foreach ($residentChoices as $choice): ?>
                                                    <option value="<?= $choice['id'] ?>"
                                                        <?= (string) ($account['resident_id'] ?? '') === (string) $choice['id'] ? 'selected' : '' ?>>
                                                        <?= e($choice['short']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>

                                            <button class="btn primary" type="submit">Save link</button>
                                        </form>
                                    <?php else: ?>
                                        —
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>
<?php end_page(); ?>