<?php
require_once 'config/functions.php';
require_once 'config/database.php';

// Already signed in? Send the visitor to their own home page.
if (is_logged_in()) {
    header('Location: ' . role_home());
    exit;
}

const REGISTER_SEX_OPTIONS = ['Female', 'Male', 'Prefer not to say'];

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

/** Returns null for an empty string, otherwise the string itself. */
function register_null_if_empty(string $value): ?string
{
    return $value !== '' ? $value : null;
}

/**
 * Checks the submitted form values.
 * Returns the first error message found, or '' when everything is valid.
 */
function register_validate(array $v, string $password, string $confirm): string
{
    $required = ['username', 'email', 'first_name', 'last_name', 'address', 'household_code'];
    foreach ($required as $field) {
        if ($v[$field] === '') {
            return 'Please complete all required fields.';
        }
    }

    if (!preg_match('/^[a-z0-9._-]{3,40}$/', $v['username'])) {
        return 'Username must be 3–40 characters and may use letters, numbers, dots, underscores, or hyphens.';
    }
    if (!filter_var($v['email'], FILTER_VALIDATE_EMAIL) || strlen($v['email']) > 150) {
        return 'Enter a valid email address.';
    }
    if (strlen($password) < 8) {
        return 'Password must contain at least 8 characters.';
    }
    if ($password !== $confirm) {
        return 'The password confirmation does not match.';
    }
    if (!preg_match('/^[A-Za-z0-9_-]{2,30}$/', $v['household_code'])) {
        return 'Household code must be 2–30 characters and may contain letters, numbers, hyphens, or underscores.';
    }
    if (strlen($v['address']) > 255) {
        return 'Resident address must be 255 characters or fewer.';
    }

    return '';
}

/** True when the username or email already belongs to an account. */
function register_is_taken(PDO $pdo, string $username, string $email): bool
{
    $check = $pdo->prepare('SELECT id FROM users WHERE username = ? OR email = ? LIMIT 1');
    $check->execute([$username, $email]);
    return (bool) $check->fetch();
}

/**
 * Existing household codes link the resident to that household.
 * A new code creates a household using the supplied household details.
 * Returns the household id.
 */
function register_household_id(PDO $pdo, array $v): int
{
    $code = strtoupper($v['household_code']);

    $find = $pdo->prepare('SELECT id FROM households WHERE household_code = ? LIMIT 1');
    $find->execute([$code]);
    $household = $find->fetch(PDO::FETCH_ASSOC);

    if ($household) {
        return (int) $household['id'];
    }

    if ($v['household_address'] === '') {
        throw new RuntimeException('For a new household, enter the household address.');
    }

    $headName = $v['head_name'] !== ''
        ? $v['head_name']
        : trim($v['first_name'] . ' ' . $v['middle_name'] . ' ' . $v['last_name']);

    $insert = $pdo->prepare('INSERT INTO households (household_code, address, head_name) VALUES (?, ?, ?)');
    $insert->execute([$code, $v['household_address'], $headName]);

    return (int) $pdo->lastInsertId();
}

/** Validates the optional fields, inserts the resident, and returns the resident id. */
function register_insert_resident(PDO $pdo, array $v, int $householdId): int
{
    $birthDate = register_null_if_empty($v['birth_date']);
    if ($birthDate !== null) {
        $date = DateTime::createFromFormat('Y-m-d', $birthDate);
        if (!$date || $date->format('Y-m-d') !== $birthDate) {
            throw new RuntimeException('Enter a valid birth date.');
        }
    }

    $sex    = in_array($v['sex'], REGISTER_SEX_OPTIONS, true) ? $v['sex'] : null;
    $phone  = register_null_if_empty($v['phone']);
    $middle = register_null_if_empty($v['middle_name']);

    $insert = $pdo->prepare(
        "INSERT INTO residents
            (first_name, middle_name, last_name, birth_date, sex, phone, email, address, household_id, status)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Active')"
    );
    $insert->execute([
        $v['first_name'], $middle, $v['last_name'], $birthDate,
        $sex, $phone, $v['email'], $v['address'], $householdId,
    ]);

    return (int) $pdo->lastInsertId();
}

/** Inserts the login account linked to the resident. */
function register_insert_user(PDO $pdo, array $v, string $password, int $residentId): void
{
    $insert = $pdo->prepare(
        "INSERT INTO users (username, email, password_hash, role, resident_id, is_active)
         VALUES (?, ?, ?, 'user', ?, 1)"
    );
    $insert->execute([
        $v['username'], $v['email'], password_hash($password, PASSWORD_DEFAULT), $residentId,
    ]);
}

// ---------------------------------------------------------------------------
// Form state and submission
// ---------------------------------------------------------------------------
$error = '';

// Blank defaults. These keys are also the form fields we accept from $_POST.
$values = [
    'username'          => '',
    'email'             => '',
    'first_name'        => '',
    'middle_name'       => '',
    'last_name'         => '',
    'birth_date'        => '',
    'sex'               => '',
    'phone'             => '',
    'address'           => '',
    'household_code'    => '',
    'household_address' => '',
    'head_name'         => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_token();

    // 1. Read and clean the submitted values.
    foreach ($values as $key => $unused) {
        $values[$key] = trim((string) ($_POST[$key] ?? ''));
    }
    $values['username'] = strtolower($values['username']);
    $values['email']    = strtolower($values['email']);
    $password           = (string) ($_POST['password'] ?? '');
    $confirm            = (string) ($_POST['confirm_password'] ?? '');

    // 2. Validate.
    $error = register_validate($values, $password, $confirm);

    if ($error === '' && register_is_taken($pdo, $values['username'], $values['email'])) {
        $error = 'That username or email is already registered.';
    }

    // 3. Save household, resident, and user together (all or nothing).
    if ($error === '') {
        try {
            $pdo->beginTransaction();

            $householdId = register_household_id($pdo, $values);
            $residentId  = register_insert_resident($pdo, $values, $householdId);
            register_insert_user($pdo, $values, $password, $residentId);

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
        <p class="muted center">
            Enter your account and resident information once.
            Your resident profile will be linked automatically when you sign up.
        </p>

        <?php if ($error !== ''): ?>
            <div class="alert danger" role="alert"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="post" action="register.php" class="auth-form">
            <input type="hidden" name="csrf" value="<?= e(token()) ?>">

            <!-- ===== Account information ===== -->
            <h2>Account Information</h2>
            <div class="formgrid">
                <div class="field">
                    <label for="username">Username *</label>
                    <input id="username" name="username" autocomplete="username"
                           minlength="3" maxlength="40" required
                           value="<?= e($values['username']) ?>">
                </div>
                <div class="field">
                    <label for="email">Email Address *</label>
                    <input id="email" name="email" type="email" autocomplete="email"
                           maxlength="150" required
                           value="<?= e($values['email']) ?>">
                </div>
                <div class="field">
                    <label for="password">Password *</label>
                    <input id="password" name="password" type="password"
                           autocomplete="new-password" minlength="8" required>
                    <small>Use at least 8 characters.</small>
                </div>
                <div class="field">
                    <label for="confirm_password">Confirm Password *</label>
                    <input id="confirm_password" name="confirm_password" type="password"
                           autocomplete="new-password" minlength="8" required>
                </div>
            </div>

            <!-- ===== Resident information ===== -->
            <h2>Basic / Resident Information</h2>
            <div class="formgrid">
                <div class="field">
                    <label for="first_name">First Name *</label>
                    <input id="first_name" name="first_name" maxlength="80" required
                           value="<?= e($values['first_name']) ?>">
                </div>
                <div class="field">
                    <label for="middle_name">Middle Name</label>
                    <input id="middle_name" name="middle_name" maxlength="80"
                           value="<?= e($values['middle_name']) ?>">
                </div>
                <div class="field">
                    <label for="last_name">Last Name *</label>
                    <input id="last_name" name="last_name" maxlength="80" required
                           value="<?= e($values['last_name']) ?>">
                </div>
                <div class="field">
                    <label for="birth_date">Birth Date</label>
                    <input id="birth_date" name="birth_date" type="date"
                           value="<?= e($values['birth_date']) ?>">
                </div>
                <div class="field">
                    <label for="sex">Sex</label>
                    <select id="sex" name="sex">
                        <option value="">Select</option>
                        <?php foreach (REGISTER_SEX_OPTIONS as $option): ?>
                            <option value="<?= e($option) ?>" <?= $values['sex'] === $option ? 'selected' : '' ?>>
                                <?= e($option) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field">
                    <label for="phone">Phone Number</label>
                    <input id="phone" name="phone" type="tel" maxlength="30"
                           value="<?= e($values['phone']) ?>">
                </div>
                <div class="field full">
                    <label for="address">Complete Residential Address *</label>
                    <textarea id="address" name="address" maxlength="255" required><?= e($values['address']) ?></textarea>
                </div>
            </div>

            <!-- ===== Household information ===== -->
            <h2>Household Information</h2>
            <p class="muted">
                If your household already exists, enter its household code.
                If it is new, enter a new unique code and complete the address below.
                For an existing code, the saved household address and head will be used.
            </p>
            <div class="formgrid">
                <div class="field">
                    <label for="household_code">Household Code *</label>
                    <input id="household_code" name="household_code" maxlength="30" required
                           placeholder="e.g. HH-0003"
                           value="<?= e($values['household_code']) ?>">
                </div>
                <div class="field">
                    <label for="head_name">Head of Household</label>
                    <input id="head_name" name="head_name" maxlength="160"
                           placeholder="Full name (for a new household)"
                           value="<?= e($values['head_name']) ?>">
                </div>
                <div class="field full">
                    <label for="household_address">Household Address (required for a new household)</label>
                    <textarea id="household_address" name="household_address" maxlength="255"><?= e($values['household_address']) ?></textarea>
                </div>
            </div>

            <button class="btn primary auth-submit" type="submit">Create Account and Resident Profile</button>
        </form>

        <p class="auth-foot">Already registered? <a href="login.php">Log in</a></p>
    </section>
</main>
<?php end_page(); ?>