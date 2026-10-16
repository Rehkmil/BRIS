<?php
require_once 'config/functions.php';
require_once 'config/database.php';

$staffCount = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'staff'")->fetchColumn();
$error = '';
$username = '';
$email = '';

if ($staffCount > 0) {
    http_response_code(403);
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_token();
    $username = strtolower(trim($_POST['username'] ?? ''));
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $confirm = (string) ($_POST['confirm_password'] ?? '');

    if (!preg_match('/^[a-z0-9._-]{3,40}$/', $username)) {
        $error = 'Username must be 3–40 characters and may use letters, numbers, dots, underscores, or hyphens.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 150) {
        $error = 'Enter a valid email address.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must contain at least 8 characters.';
    } elseif ($password !== $confirm) {
        $error = 'The password confirmation does not match.';
    } else {
        $check = $pdo->prepare('SELECT id FROM users WHERE username = ? OR email = ? LIMIT 1');
        $check->execute([$username, $email]);
        if ($check->fetch()) {
            $error = 'That username or email is already in use.';
        } else {
            try {
                $insert = $pdo->prepare("INSERT INTO users (username, email, password_hash, role, is_active) VALUES (?, ?, ?, 'staff', 1)");
                $insert->execute([$username, $email, password_hash($password, PASSWORD_DEFAULT)]);
                flash('Initial staff account created. Log in using the credentials you just set.');
                header('Location: login.php');
                exit;
            } catch (PDOException $exception) {
                $error = 'Staff account could not be created. Check that this username/email is not already used.';
            }
        }
    }
}

start_page('First Staff Setup');
?>
<main class="auth-wrap">
    <section class="auth-card">
        <div class="auth-mark">B</div>
        <p class="kicker center">One-time setup</p>
        <h1 class="center">Create the first staff account</h1>
        <?php if ($staffCount > 0): ?>
            <div class="alert danger">Staff setup is already complete. For safety, this page can only create the first staff account.</div>
            <p class="center"><a class="btn primary" href="login.php">Go to login</a></p>
        <?php else: ?>
            <p class="muted center">This page is only available until the first staff account is created. Choose a strong password and keep it private.</p>
            <?php if ($error !== ''): ?><div class="alert danger" role="alert"><?= e($error) ?></div><?php endif; ?>
            <form method="post" action="setup_staff.php" class="auth-form">
                <input type="hidden" name="csrf" value="<?= e(token()) ?>">
                <div class="field"><label for="username">Staff username</label><input id="username" name="username" autocomplete="username" minlength="3" maxlength="40" required value="<?= e($username) ?>"></div>
                <div class="field"><label for="email">Staff email</label><input id="email" name="email" type="email" autocomplete="email" maxlength="150" required value="<?= e($email) ?>"></div>
                <div class="field"><label for="password">Password</label><input id="password" name="password" type="password" autocomplete="new-password" minlength="8" required><small>Use at least 8 characters.</small></div>
                <div class="field"><label for="confirm_password">Confirm password</label><input id="confirm_password" name="confirm_password" type="password" autocomplete="new-password" minlength="8" required></div>
                <button class="btn primary auth-submit" type="submit">Create staff account</button>
            </form>
        <?php endif; ?>
    </section>
</main>
<?php end_page(); ?>
