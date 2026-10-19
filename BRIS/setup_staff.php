<?php
require_once 'config/functions.php';
require_once 'config/database.php';

$error = '';
$staffCount = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'staff'")->fetchColumn();
$setupOpen = $staffCount === 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_token();

    // Only allow initial setup while no staff account exists.
    $staffCount = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'staff'")->fetchColumn();
    $setupOpen = $staffCount === 0;

    if (!$setupOpen) {
        $error = 'Initial staff setup has already been completed. Please log in.';
    } else {
        $username = strtolower(trim((string) ($_POST['username'] ?? '')));
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $password = (string) ($_POST['password'] ?? '');
        $confirm = (string) ($_POST['confirm_password'] ?? '');

        if (!preg_match('/^[a-z0-9._-]{3,40}$/', $username)) {
            $error = 'Username must be 3–40 characters and use letters, numbers, dots, underscores, or hyphens.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 150) {
            $error = 'Enter a valid email address.';
        } elseif (strlen($password) < 8) {
            $error = 'Password must contain at least 8 characters.';
        } elseif ($password !== $confirm) {
            $error = 'The passwords do not match.';
        } else {
            $check = $pdo->prepare('SELECT id FROM users WHERE username = ? OR email = ? LIMIT 1');
            $check->execute([$username, $email]);
            if ($check->fetch()) {
                $error = 'That username or email is already registered.';
            } else {
                $insert = $pdo->prepare(
                    "INSERT INTO users (username, email, password_hash, role, resident_id, is_active)
                     VALUES (?, ?, ?, 'staff', NULL, 1)"
                );
                $insert->execute([$username, $email, password_hash($password, PASSWORD_DEFAULT)]);
                flash('First staff account created. You can now log in.');
                header('Location: login.php');
                exit;
            }
        }
    }
}

start_page('Initial Staff Setup');
?>
<main class="auth-wrap">
    <section class="auth-card">
        <div class="auth-logo"><img class="auth-logo-img" src="assets/BRISlogo.png" alt="BRIS logo"></div>
        <p class="kicker center">Administrator setup</p>
        <h1 class="center">Create the first staff account</h1>
        <p class="muted center">This page is available only before the first staff account is created.</p>

        <?php if ($error !== ''): ?>
            <div class="alert danger" role="alert"><?= e($error) ?></div>
        <?php endif; ?>

        <?php if ($setupOpen): ?>
            <form method="post" action="setup_staff.php" class="auth-form">
                <input type="hidden" name="csrf" value="<?= e(token()) ?>">
                <div class="field">
                    <label for="username">Username *</label>
                    <input id="username" name="username" autocomplete="username" minlength="3" maxlength="40" required>
                </div>
                <div class="field">
                    <label for="email">Email address *</label>
                    <input id="email" name="email" type="email" autocomplete="email" maxlength="150" required>
                </div>
                <div class="field">
                    <label for="password">Password *</label>
                    <input id="password" name="password" type="password" autocomplete="new-password" minlength="8" required>
                    <small>Use at least 8 characters.</small>
                </div>
                <div class="field">
                    <label for="confirm_password">Confirm password *</label>
                    <input id="confirm_password" name="confirm_password" type="password" autocomplete="new-password" minlength="8" required>
                </div>
                <button class="btn primary auth-submit" type="submit">Create staff account</button>
            </form>
        <?php else: ?>
            <div class="alert success" role="status">Initial setup is complete. The first staff account already exists.</div>
            <p class="center"><a class="btn primary" href="login.php">Go to log in</a></p>
        <?php endif; ?>
        <p class="auth-foot"><a href="index.php">Back to home</a></p>
    </section>
</main>
<?php end_page(); ?>
