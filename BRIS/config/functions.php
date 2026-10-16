<?php
// Configure safer session defaults before starting the session.
if (session_status() !== PHP_SESSION_ACTIVE) {
    ini_set('session.use_strict_mode', '1');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function e($value)
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function token()
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf'];
}

function verify_token()
{
    $submittedToken = $_POST['csrf'] ?? '';

    if (!is_string($submittedToken) || !hash_equals(token(), $submittedToken)) {
        http_response_code(403);
        exit('Invalid form token. Go back and try again.');
    }
}

function flash($message, $type = 'success')
{
    $_SESSION['flash'] = [$message, $type];
}

function show_flash()
{
    if (empty($_SESSION['flash'])) {
        return;
    }

    [$message, $type] = $_SESSION['flash'];
    $allowedTypes = ['success', 'danger'];
    if (!in_array($type, $allowedTypes, true)) {
        $type = 'success';
    }

    echo '<div class="alert ' . e($type) . '">' . e($message) . '</div>';
    unset($_SESSION['flash']);
}

function current_user()
{
    return $_SESSION['auth_user'] ?? null;
}

function is_logged_in()
{
    $user = current_user();
    return is_array($user) && !empty($user['id']) && in_array($user['role'] ?? '', ['staff', 'user'], true);
}

function require_login()
{
    if (is_logged_in()) {
        return;
    }

    // Save only a local page name; do not accept an external redirect URL.
    $requestedPage = basename($_SERVER['PHP_SELF'] ?? 'index.php');
    $allowedPages = ['residents.php', 'resident_form.php', 'resident_save.php', 'households.php', 'reports.php', 'officials.php', 'accounts.php'];
    if (in_array($requestedPage, $allowedPages, true)) {
        $_SESSION['after_login'] = $requestedPage;
    }

    flash('Please log in to open that page.', 'danger');
    header('Location: login.php');
    exit;
}

function require_role($requiredRole)
{
    require_login();
    $user = current_user();

    if (($user['role'] ?? '') !== $requiredRole) {
        flash('You do not have permission to open that page.', 'danger');
        $destination = ($user['role'] ?? '') === 'staff' ? 'residents.php' : 'user_dashboard.php';
        header('Location: ' . $destination);
        exit;
    }
}

function role_home($user = null)
{
    $user = $user ?? current_user();
    return (($user['role'] ?? '') === 'staff') ? 'residents.php' : 'user_dashboard.php';
}

function nav()
{
    $user = current_user();
    ?>
    <header>
        <div class="wrap nav">
            <a class="brand" href="index.php">
                <b>B</b>
                <span>BRIS<small>Barangay Resident Information System</small></span>
            </a>
            <nav>
                <a href="index.php">Home</a>
                <?php if (is_logged_in() && ($user['role'] ?? '') === 'staff'): ?>
                    <a href="residents.php">Residents</a>
                    <a href="resident_form.php">Registration</a>
                    <a href="households.php">Households</a>
                    <a href="reports.php">Reports</a>
                    <a href="officials.php">Officials</a>
                    <a href="accounts.php">Accounts</a>
                    <span class="nav-user">Staff: <?= e($user['username'] ?? '') ?></span>
                    <form class="nav-logout" action="logout.php" method="post">
                        <input type="hidden" name="csrf" value="<?= e(token()) ?>">
                        <button type="submit" class="btn primary">Log out</button>
                    </form>
                <?php elseif (is_logged_in()): ?>
                    <a href="user_dashboard.php">My Account</a>
                    <span class="nav-user">User: <?= e($user['username'] ?? '') ?></span>
                    <form class="nav-logout" action="logout.php" method="post">
                        <input type="hidden" name="csrf" value="<?= e(token()) ?>">
                        <button type="submit" class="btn primary">Log out</button>
                    </form>
                <?php else: ?>
                    <a href="login.php">Log in</a>
                    <a href="register.php">Create account</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>
    <?php
}

function start_page($title)
{
    ?>
    <!doctype html>
    <html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title><?= e($title) ?> | BRIS</title>
        <link rel="stylesheet" href="assets/style.css">
    </head>
    <body>
    <?php
    nav();
}

function end_page()
{
    ?>
    <footer><div class="wrap">BRIS · Student project demonstration</div></footer>
    </body>
    </html>
    <?php
}
