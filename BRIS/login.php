<?php
require_once 'config/functions.php';
require_once 'config/database.php';

if (is_logged_in()) {
    header('Location: ' . role_home());
    exit;
}

$error = '';
$identity = '';
$now = time();
$_SESSION['login_attempts'] = array_values(array_filter(
    $_SESSION['login_attempts'] ?? [],
    static fn ($timestamp) => is_int($timestamp) && ($now - $timestamp) < 900
));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_token();
    $identity = trim($_POST['identity'] ?? '');
    $password = (string) ($_POST['password'] ?? '');

    if (count($_SESSION['login_attempts']) >= 5) {
        $error = 'Too many failed attempts. Wait 15 minutes before trying again.';
    } elseif ($identity === '' || $password === '') {
        $error = 'Enter your username or email and password.';
    } else {
        $stmt = $pdo->prepare('SELECT id, username, email, password_hash, role, resident_id, is_active FROM users WHERE username = ? OR email = ? LIMIT 1');
        $stmt->execute([$identity, $identity]);
        $account = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$account || !(bool) $account['is_active'] || !password_verify($password, $account['password_hash'])) {
            $_SESSION['login_attempts'][] = $now;
            $error = 'The username/email or password is incorrect, or the account is inactive.';
        } else {
            session_regenerate_id(true);
            unset($_SESSION['csrf'], $_SESSION['login_attempts']);
            $_SESSION['auth_user'] = [
                'id' => (int) $account['id'],
                'username' => $account['username'],
                'email' => $account['email'],
                'role' => $account['role'],
                'resident_id' => $account['resident_id'] === null ? null : (int) $account['resident_id'],
            ];
            $update = $pdo->prepare('UPDATE users SET last_login_at = NOW() WHERE id = ?');
            $update->execute([(int) $account['id']]);

            $target = 'user_dashboard.php';
            if ($account['role'] === 'staff') {
                $allowedTargets = ['residents.php', 'resident_form.php', 'households.php', 'reports.php', 'officials.php', 'accounts.php'];
                $requested = $_SESSION['after_login'] ?? '';
                $target = in_array($requested, $allowedTargets, true) ? $requested : 'residents.php';
            }
            unset($_SESSION['after_login']);
            flash('Welcome back, ' . $account['username'] . '.');
            header('Location: ' . $target);
            exit;
        }
    }
}

$staffCount = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'staff'")->fetchColumn();
start_page('Log In');
?>
<main class="auth-wrap">
    <section class="auth-card">
        <div class="auth-mark">B</div>
        <p class="kicker center">Secure account access</p>
        <h1 class="center">Welcome to BRIS</h1>
        <p class="muted center">Log in with your staff or user account.</p>
        <?php show_flash(); ?>
        <?php if ($error !== ''): ?><div class="alert danger" role="alert"><?= e($error) ?></div><?php endif; ?>
        <form method="post" action="login.php" class="auth-form">
            <input type="hidden" name="csrf" value="<?= e(token()) ?>">
            <div class="field">
                <label for="identity">Username or email</label>
                <input id="identity" name="identity" type="text" autocomplete="username" required maxlength="150" value="<?= e($identity) ?>">
            </div>
            <div class="field">
                <label for="password">Password</label>
                <input id="password" name="password" type="password" autocomplete="current-password" required>
            </div>
            <button class="btn primary auth-submit" type="submit">Log in</button>
        </form>
        <p class="auth-foot">Resident user? <a href="register.php">Create an account</a></p>
        <?php if ($staffCount === 0): ?>
            <p class="auth-foot">First-time setup: <a href="setup_staff.php">Create the first staff account</a></p>
        <?php endif; ?>
    </section>
</main>
<?php end_page(); ?>
