<?php
require_once 'config/functions.php';
require_once 'config/database.php';

if (is_logged_in()) {
    header('Location: ' . role_home());
    exit;
}

$error = '';
$values = [
    'username' => '', 'email' => '', 'first_name' => '', 'middle_name' => '',
    'last_name' => '', 'birth_date' => '', 'sex' => '', 'phone' => '',
    'address' => '', 'household_code' => '', 'household_address' => '', 'head_name' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_token();
    foreach ($values as $key => $unused) {
        $values[$key] = trim((string) ($_POST[$key] ?? ''));
    }
    $values['username'] = strtolower($values['username']);
    $values['email'] = strtolower($values['email']);
    $password = (string) ($_POST['password'] ?? '');
    $confirm = (string) ($_POST['confirm_password'] ?? '');

    $required = ['username', 'email', 'first_name', 'last_name', 'address', 'household_code'];
    foreach ($required as $field) {
        if ($values[$field] === '') {
            $error = 'Please complete all required fields.';
            break;
        }
    }

    if ($error === '' && !preg_match('/^[a-z0-9._-]{3,40}$/', $values['username'])) {
        $error = 'Username must be 3–40 characters and may use letters, numbers, dots, underscores, or hyphens.';
    } elseif ($error === '' && (!filter_var($values['email'], FILTER_VALIDATE_EMAIL) || strlen($values['email']) > 150)) {
        $error = 'Enter a valid email address.';
    } elseif ($error === '' && (strlen($password) < 8 || $password !== $confirm)) {
        $error = strlen($password) < 8 ? 'Password must contain at least 8 characters.' : 'The password confirmation does not match.';
    } elseif ($error === '' && (!preg_match('/^[A-Za-z0-9_-]{2,30}$/', $values['household_code']))) {
        $error = 'Household code must be 2–30 characters and may contain letters, numbers, hyphens, or underscores.';
    } elseif ($error === '' && strlen($values['address']) > 255) {
        $error = 'Resident address must be 255 characters or fewer.';
    }

    if ($error === '') {
        $check = $pdo->prepare('SELECT id FROM users WHERE username = ? OR email = ? LIMIT 1');
        $check->execute([$values['username'], $values['email']]);
        if ($check->fetch()) {
            $error = 'That username or email is already registered.';
        }
    }

    if ($error === '') {
        try {
            $pdo->beginTransaction();

            // Existing household codes link the resident to that household.
            // A new code creates a household using the supplied household details.
            $householdCode = strtoupper($values['household_code']);
            $findHousehold = $pdo->prepare('SELECT id, address FROM households WHERE household_code = ? LIMIT 1');
            $findHousehold->execute([$householdCode]);
            $household = $findHousehold->fetch(PDO::FETCH_ASSOC);

            if ($household) {
                $householdId = (int) $household['id'];
            } else {
                if ($values['household_address'] === '') {
                    throw new RuntimeException('For a new household, enter the household address.');
                }
                $headName = $values['head_name'] !== '' ? $values['head_name'] : trim($values['first_name'] . ' ' . $values['middle_name'] . ' ' . $values['last_name']);
                $insertHousehold = $pdo->prepare('INSERT INTO households (household_code, address, head_name) VALUES (?, ?, ?)');
                $insertHousehold->execute([$householdCode, $values['household_address'], $headName]);
                $householdId = (int) $pdo->lastInsertId();
            }

            $birthDate = $values['birth_date'] !== '' ? $values['birth_date'] : null;
            if ($birthDate !== null) {
                $date = DateTime::createFromFormat('Y-m-d', $birthDate);
                if (!$date || $date->format('Y-m-d') !== $birthDate) {
                    throw new RuntimeException('Enter a valid birth date.');
                }
            }
            $sex = in_array($values['sex'], ['Female', 'Male', 'Prefer not to say'], true) ? $values['sex'] : null;
            $phone = $values['phone'] !== '' ? $values['phone'] : null;
            $middle = $values['middle_name'] !== '' ? $values['middle_name'] : null;

            $insertResident = $pdo->prepare("INSERT INTO residents (first_name, middle_name, last_name, birth_date, sex, phone, email, address, household_id, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Active')");
            $insertResident->execute([
                $values['first_name'], $middle, $values['last_name'], $birthDate,
                $sex, $phone, $values['email'], $values['address'], $householdId
            ]);
            $residentId = (int) $pdo->lastInsertId();

            $insertUser = $pdo->prepare("INSERT INTO users (username, email, password_hash, role, resident_id, is_active) VALUES (?, ?, ?, 'user', ?, 1)");
            $insertUser->execute([
                $values['username'], $values['email'], password_hash($password, PASSWORD_DEFAULT), $residentId
            ]);

            $pdo->commit();
            flash('Your account and resident profile have been created. You can now log in.');
            header('Location: login.php');
            exit;
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error = $exception instanceof RuntimeException
                ? $exception->getMessage()
                : 'The account could not be created. Check the household code, username, and email, then try again.';
        }
    }
}

start_page('Create User Account');
?>
<main class="auth-wrap" style="width:min(760px,92%)">
    <section class="auth-card">
        <div class="auth-mark">B</div>
        <p class="kicker center">Resident access</p>
        <h1 class="center">Create an account</h1>
        <p class="muted center">Enter your account and resident information once. Your resident profile will be linked automatically when you sign up.</p>
        <?php if ($error !== ''): ?><div class="alert danger" role="alert"><?= e($error) ?></div><?php endif; ?>
        <form method="post" action="register.php" class="auth-form">
            <input type="hidden" name="csrf" value="<?= e(token()) ?>">
            <h2>Account Information</h2>
            <div class="formgrid">
                <div class="field"><label for="username">Username *</label><input id="username" name="username" autocomplete="username" minlength="3" maxlength="40" required value="<?= e($values['username']) ?>"></div>
                <div class="field"><label for="email">Email Address *</label><input id="email" name="email" type="email" autocomplete="email" maxlength="150" required value="<?= e($values['email']) ?>"></div>
                <div class="field"><label for="password">Password *</label><input id="password" name="password" type="password" autocomplete="new-password" minlength="8" required><small>Use at least 8 characters.</small></div>
                <div class="field"><label for="confirm_password">Confirm Password *</label><input id="confirm_password" name="confirm_password" type="password" autocomplete="new-password" minlength="8" required></div>
            </div>

            <h2>Basic / Resident Information</h2>
            <div class="formgrid">
                <div class="field"><label for="first_name">First Name *</label><input id="first_name" name="first_name" maxlength="80" required value="<?= e($values['first_name']) ?>"></div>
                <div class="field"><label for="middle_name">Middle Name</label><input id="middle_name" name="middle_name" maxlength="80" value="<?= e($values['middle_name']) ?>"></div>
                <div class="field"><label for="last_name">Last Name *</label><input id="last_name" name="last_name" maxlength="80" required value="<?= e($values['last_name']) ?>"></div>
                <div class="field"><label for="birth_date">Birth Date</label><input id="birth_date" name="birth_date" type="date" value="<?= e($values['birth_date']) ?>"></div>
                <div class="field"><label for="sex">Sex</label><select id="sex" name="sex"><option value="">Select</option><?php foreach (['Female', 'Male', 'Prefer not to say'] as $option): ?><option value="<?= e($option) ?>" <?= $values['sex'] === $option ? 'selected' : '' ?>><?= e($option) ?></option><?php endforeach; ?></select></div>
                <div class="field"><label for="phone">Phone Number</label><input id="phone" name="phone" type="tel" maxlength="30" value="<?= e($values['phone']) ?>"></div>
                <div class="field full"><label for="address">Complete Residential Address *</label><textarea id="address" name="address" maxlength="255" required><?= e($values['address']) ?></textarea></div>
            </div>

            <h2>Household Information</h2>
            <p class="muted">If your household already exists, enter its household code. If it is new, enter a new unique code and complete the address below. For an existing code, the saved household address and head will be used.</p>
            <div class="formgrid">
                <div class="field"><label for="household_code">Household Code *</label><input id="household_code" name="household_code" maxlength="30" required placeholder="e.g. HH-0003" value="<?= e($values['household_code']) ?>"></div>
                <div class="field"><label for="head_name">Head of Household</label><input id="head_name" name="head_name" maxlength="160" placeholder="Full name (for a new household)" value="<?= e($values['head_name']) ?>"></div>
                <div class="field full"><label for="household_address">Household Address (required for a new household)</label><textarea id="household_address" name="household_address" maxlength="255"><?= e($values['household_address']) ?></textarea></div>
            </div>
            <button class="btn primary auth-submit" type="submit">Create Account and Resident Profile</button>
        </form>
        <p class="auth-foot">Already registered? <a href="login.php">Log in</a></p>
    </section>
</main>
<?php end_page(); ?>
