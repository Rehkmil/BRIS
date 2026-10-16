# BRIS Project Structure and Code Guide

This is a beginner-friendly map of the Barangay Resident Information System (BRIS).
The organization is intentionally conservative: PHP page URLs and SQL filenames stay in place so existing links continue to work.

## Main folders

- `config/` — shared PHP setup and configuration.
  - `database.php` creates the PDO connection (`$pdo`) to the `bris_db` MySQL database.
  - `functions.php` contains shared functions used by many pages: escaping output (`e()`), CSRF tokens, flash messages, current-user/login checks, role checks, navigation, and the common page opening/closing layout.
  - `.htaccess` helps prevent direct web access to configuration files on Apache.
- `database/` — SQL scripts to create the database or apply the older login-system migration.
- `assets/` — CSS used by the current PHP website (`assets/style.css`).
- `docs/` — project notes and this guide.
- Root `.php` files — the pages and actions users navigate to.

## What each PHP page does

- `index.php` — homepage; uses `start_page()` and `end_page()` from `config/functions.php`.
- `residents.php` — searches and lists resident records.
- `resident_form.php` — displays the add/edit resident form.
- `resident_save.php` — validates the submitted form and inserts or updates a resident in MySQL.
- `households.php` — household list and member summaries, with an Add Household button.
- `household_form.php` — displays the form for a new household.
- `household_save.php` — validates household details, checks for a duplicate code, and inserts the new household into MySQL.
- `reports.php` — summary reports and printing.
- `officials.php` — officials directory.
- `login.php`, `logout.php`, `register.php`, `setup_staff.php` — authentication and account setup.
- `accounts.php` — staff account management.
- `user_dashboard.php` — the standard user's account page.

## How the household creation flow works

1. Staff opens `households.php` and clicks **Add Household**.
2. `household_form.php` displays fields for a unique household code, address, and optional head of household.
3. The form submits to `household_save.php`, which checks the staff role, verifies the CSRF token, validates required fields, and checks for an existing household code.
4. A prepared SQL statement inserts the household into the `households` table.
5. The page redirects to `households.php`. The new household will also appear automatically in the Household dropdown on `resident_form.php`.

## How the resident registration flow works

1. Staff opens `resident_form.php` and fills out the form.
2. The browser sends the form to `resident_save.php` using POST.
3. `resident_save.php` checks the staff role, verifies the CSRF token, validates the fields, and uses `$pdo` to run a prepared SQL statement.
4. MySQL stores the resident in the `residents` table.
5. The save page redirects back to `residents.php`, which reads records from MySQL and displays them.

The `.sql` file is a database setup script, not the live database itself. The live records are stored by MySQL in `bris_db`.

## Why shared code is kept in `config/functions.php`

The navigation and common HTML page wrapper are defined once rather than repeated on every page. The helper functions for escaping output, checking the login/role, showing one-time messages, and validating CSRF tokens are also reused. If you change the shared navigation or footer, change it in this file instead of copying changes into every page.

`config/database.php` is kept separate on purpose. Database connection configuration and UI/security helper functions have different responsibilities; combining them would make the project harder to understand and maintain.

## Files removed from the cleaned copy

- `.git/` — version-control history; not needed for the website to run and can make a ZIP much larger.
- `Index.html` and `styles.css` — an older static homepage and its stylesheet. The active homepage is `index.php`, and the shared page wrapper loads `assets/style.css`. The old HTML linked to `styles.css` but was not part of the PHP navigation flow.

The original ZIP is not changed; these removals apply only to this cleaned copy.

## Safe next steps for further refactoring

1. Test all pages in XAMPP before making more structural changes.
2. If the project grows, move repeated form-validation/database operations into a dedicated `includes/` or `services/` file, but only after checking all page dependencies.
3. Keep role checks on the server; hiding a link in navigation is not a substitute for authorization.
4. Back up the database before testing changes to SQL scripts. Use fictional resident records during development.

This is an educational project structure guide, not a production security audit.
