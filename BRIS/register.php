<?php
require_once 'config/functions.php';
require_once 'config/database.php';

if (is_logged_in()) {
    header('Location: ' . role_home());
    exit;
}

$error = '';
$username = '';
$email = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
            $error = 'That username or email is already registered.';
        } else {
            try {
                $insert = $pdo->prepare("INSERT INTO users (username, email, password_hash, role, is_active) VALUES (?, ?, ?, 'user', 1)");
                $insert->execute([$username, $email, password_hash($password, PASSWORD_DEFAULT)]);
                flash('Your account has been created. You can now log in.');
                header('Location: login.php');
                exit;
            } catch (PDOException $exception) {
                $error = 'The account could not be created. Check whether the username or email is already used.';
            }
        }
    }
}

start_page('Create User Account');
?>
<main class="auth-wrap">
    <section class="auth-card">
        <div class="auth-mark">B</div>
        <p class="kicker center">Resident access</p>
        <h1 class="center">Create an account</h1>
        <p class="muted center">This form creates a standard user account, not a staff account.</p>
        <?php if ($error !== ''): ?><div class="alert danger" role="alert"><?= e($error) ?></div><?php endif; ?>
        <form method="post" action="register.php" class="auth-form">
            <input type="hidden" name="csrf" value="<?= e(token()) ?>">
            <div class="field"><label for="username">Username</label><input id="username" name="username" autocomplete="username" minlength="3" maxlength="40" required value="<?= e($username) ?>"></div>
            <div class="field"><label for="email">Email address</label><input id="email" name="email" type="email" autocomplete="email" maxlength="150" required value="<?= e($email) ?>"></div>
            <div class="field"><label for="password">Password</label><input id="password" name="password" type="password" autocomplete="new-password" minlength="8" required><small>Use at least 8 characters.</small></div>
            <div class="field"><label for="confirm_password">Confirm password</label><input id="confirm_password" name="confirm_password" type="password" autocomplete="new-password" minlength="8" required></div>
            <button class="btn primary auth-submit" type="submit">Create account</button>
        </form>
        <p class="auth-foot">Already registered? <a href="login.php">Log in</a></p>
    </section>
</main>
<?php end_page(); ?>
