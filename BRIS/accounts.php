<?php
require_once 'config/functions.php';
require_once 'config/database.php';
require_role('staff');

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_token();
    $action = $_POST['action'] ?? 'create';

    if ($action === 'link_resident') {
        $accountId = (int) ($_POST['account_id'] ?? 0);
        $residentId = (int) ($_POST['resident_id'] ?? 0) ?: null;
        $accountCheck = $pdo->prepare("SELECT id, role FROM users WHERE id = ? LIMIT 1");
        $accountCheck->execute([$accountId]);
        $targetAccount = $accountCheck->fetch(PDO::FETCH_ASSOC);

        if (!$targetAccount || $targetAccount['role'] !== 'user') {
            $error = 'Only an existing user account can be linked to a resident profile.';
        } elseif ($residentId !== null) {
            $residentCheck = $pdo->prepare('SELECT id FROM residents WHERE id = ?');
            $residentCheck->execute([$residentId]);
            if (!$residentCheck->fetch()) {
                $error = 'Select a resident record that exists.';
            }
        }

        if ($error === '') {
            try {
                $update = $pdo->prepare('UPDATE users SET resident_id = ? WHERE id = ? AND role = \'user\'');
                $update->execute([$residentId, $accountId]);
                flash($residentId === null ? 'Resident profile link removed.' : 'Resident profile linked to account.');
                header('Location: accounts.php');
                exit;
            } catch (PDOException $exception) {
                $error = 'This resident record may already be linked to another account.';
            }
        }
    } else {
        $username = strtolower(trim($_POST['username'] ?? ''));
        $email = strtolower(trim($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $role = $_POST['role'] ?? 'user';
        $residentId = (int) ($_POST['resident_id'] ?? 0) ?: null;

        if (!preg_match('/^[a-z0-9._-]{3,40}$/', $username)) {
            $error = 'Username must be 3–40 characters and may use letters, numbers, dots, underscores, or hyphens.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 150) {
            $error = 'Enter a valid email address.';
        } elseif (strlen($password) < 8) {
            $error = 'Password must contain at least 8 characters.';
        } elseif (!in_array($role, ['staff', 'user'], true)) {
            $error = 'Select a valid account role.';
        } elseif ($role === 'staff' && $residentId !== null) {
            $error = 'Staff accounts cannot be linked to resident profiles.';
        } else {
            if ($residentId !== null) {
                $residentCheck = $pdo->prepare('SELECT id FROM residents WHERE id = ?');
                $residentCheck->execute([$residentId]);
                if (!$residentCheck->fetch()) {
                    $error = 'Select a resident record that exists.';
                }
            }
            if ($error === '') {
                $check = $pdo->prepare('SELECT id FROM users WHERE username = ? OR email = ? LIMIT 1');
                $check->execute([$username, $email]);
                if ($check->fetch()) {
                    $error = 'That username or email is already registered.';
                } else {
                    try {
                        $insert = $pdo->prepare('INSERT INTO users (username, email, password_hash, role, resident_id, is_active) VALUES (?, ?, ?, ?, ?, 1)');
                        $insert->execute([$username, $email, password_hash($password, PASSWORD_DEFAULT), $role, $residentId]);
                        flash('Account created successfully.');
                        header('Location: accounts.php');
                        exit;
                    } catch (PDOException $exception) {
                        $error = 'Account could not be created. The username, email, or resident profile may already be linked.';
                    }
                }
            }
        }
    }
}

$residents = $pdo->query('SELECT id, first_name, middle_name, last_name FROM residents ORDER BY last_name, first_name')->fetchAll(PDO::FETCH_ASSOC);
$accounts = $pdo->query('SELECT u.id, u.username, u.email, u.role, u.is_active, u.created_at, u.resident_id, r.first_name, r.middle_name, r.last_name FROM users u LEFT JOIN residents r ON r.id = u.resident_id ORDER BY u.created_at DESC, u.id DESC')->fetchAll(PDO::FETCH_ASSOC);

start_page('Account Management');
?>
<div class="pagehead"><div class="wrap"><h1>Account Management</h1><p class="muted">Create user or staff login accounts and link user accounts to resident records.</p></div></div>
<main class="wrap">
    <section class="panel">
        <?php show_flash(); ?>
        <?php if ($error !== ''): ?><div class="alert danger" role="alert"><?= e($error) ?></div><?php endif; ?>
        <h2>Create Account</h2>
        <form method="post" action="accounts.php">
            <input type="hidden" name="csrf" value="<?= e(token()) ?>">
            <input type="hidden" name="action" value="create">
            <div class="formgrid">
                <div class="field"><label for="username">Username</label><input id="username" name="username" minlength="3" maxlength="40" required></div>
                <div class="field"><label for="email">Email</label><input id="email" name="email" type="email" maxlength="150" required></div>
                <div class="field"><label for="password">Initial password</label><input id="password" name="password" type="password" minlength="8" required><small>At least 8 characters. Share it privately with the account holder.</small></div>
                <div class="field"><label for="role">Role</label><select id="role" name="role" required><option value="user">User / Resident</option><option value="staff">Staff</option></select></div>
                <div class="field full"><label for="resident_id">Link to resident record (optional, user accounts only)</label><select id="resident_id" name="resident_id"><option value="">Do not link</option><?php foreach ($residents as $resident): ?><option value="<?= (int) $resident['id'] ?>"><?= e(trim($resident['last_name'] . ', ' . $resident['first_name'] . ' ' . ($resident['middle_name'] ?? ''))) ?> — ID <?= (int) $resident['id'] ?></option><?php endforeach; ?></select></div>
            </div>
            <button class="btn primary" type="submit">Create account</button>
        </form>
    </section>
    <section class="panel">
        <h2>Existing Accounts</h2>
        <p class="muted">Passwords and password hashes are never displayed. A resident record can be linked to one user account.</p>
        <div class="tablewrap"><table><thead><tr><th>Username</th><th>Email</th><th>Role</th><th>Linked resident</th><th>Status</th><th>Created</th><th>Manage link</th></tr></thead><tbody>
        <?php if (!$accounts): ?><tr><td colspan="7">No accounts found.</td></tr><?php else: foreach ($accounts as $account): ?>
            <tr>
                <td><?= e($account['username']) ?></td><td><?= e($account['email']) ?></td><td><?= e(ucfirst($account['role'])) ?></td>
                <td><?= !empty($account['first_name']) ? e(trim($account['first_name'] . ' ' . ($account['middle_name'] ?? '') . ' ' . $account['last_name'])) : '—' ?></td>
                <td><span class="badge <?= !(bool) $account['is_active'] ? 'inactive' : '' ?>"><?= (bool) $account['is_active'] ? 'Active' : 'Inactive' ?></span></td><td><?= e($account['created_at']) ?></td>
                <td>
                    <?php if ($account['role'] === 'user'): ?>
                    <form method="post" action="accounts.php" class="link-account-form">
                        <input type="hidden" name="csrf" value="<?= e(token()) ?>"><input type="hidden" name="action" value="link_resident"><input type="hidden" name="account_id" value="<?= (int) $account['id'] ?>">
                        <select name="resident_id" aria-label="Resident profile for <?= e($account['username']) ?>"><option value="">No linked profile</option><?php foreach ($residents as $resident): ?><option value="<?= (int) $resident['id'] ?>" <?= (string)($account['resident_id'] ?? '') === (string)$resident['id'] ? 'selected' : '' ?>><?= e($resident['last_name'] . ', ' . $resident['first_name']) ?> (ID <?= (int) $resident['id'] ?>)</option><?php endforeach; ?></select>
                        <button class="btn primary" type="submit">Save link</button>
                    </form>
                    <?php else: ?>—<?php endif; ?>
                </td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody></table></div>
    </section>
</main>
<?php end_page(); ?>
