BRIS - BARANGAY RESIDENT INFORMATION SYSTEM
==========================================

ABOUT
-----
This is a simplified PHP + MySQL project for XAMPP. It is organized as a fresh,
student-friendly project and uses the supplied iNetTutor barangay system ZIP as a
feature reference (resident records, households, officials/documents concepts,
and reports). It does not bundle or directly reuse the legacy source code.

FEATURES INCLUDED
-----------------
- Responsive BRIS landing page
- Resident registration and editing
- Resident search and status filter
- Household assignment and household summary
- Printable resident report
- PDO prepared statements for database queries
- Session-based login/logout with staff and user roles
- Password hashing, CSRF checks, and protected staff pages
- Staff account management and optional resident profile linking
- CSRF token check on resident save form
- Sample records for demonstration

PROJECT STRUCTURE
-----------------
BRIS/
  index.php                 Landing page
  residents.php             Resident list, search, and status filter
  resident_form.php         Add/edit resident form
  resident_save.php         Validate and save resident information
  login.php                 Login for staff and users
  logout.php                Secure sign-out endpoint (POST)
  register.php              Public standard-user registration
  setup_staff.php           One-time initial staff setup
  user_dashboard.php        User account and linked profile view
  accounts.php              Staff-only account creation and listing
  households.php            Household list and member counts
  reports.php               Printable resident report
  config/
    database.php            PDO database connection
    functions.php           Shared helpers, navigation, flash messages, CSRF
  assets/
    style.css               Shared responsive styles
  database/
    bris.sql                Database tables and sample data
    add_login_system.sql    Migration for an existing database
  README.txt                This file
  REFERENCE_NOTES.txt       Notes about the supplied reference archive

INSTALLATION ON XAMPP
---------------------
1. Extract the ZIP file.
2. Copy the BRIS folder to C:\xampp\htdocs\
   Final path should be C:\xampp\htdocs\BRIS\index.php
3. Open the XAMPP Control Panel.
4. Start Apache and MySQL.
5. Open http://localhost/phpmyadmin/
6. Select Import, choose BRIS/database/bris.sql, then click Import/Go. The SQL creates bris_db, the tables, and sample records.
7. If you already imported an older BRIS database, import BRIS/database/add_login_system.sql into that database to add the users table. This migration does not delete resident data.
8. Open http://localhost/BRIS/setup_staff.php and create the first staff account using your own credentials.
9. Open http://localhost/BRIS/login.php to log in. Standard users can create an account at http://localhost/BRIS/register.php. Staff can create more user/staff accounts in Accounts.

DATABASE SETTINGS
-----------------
Default settings are in config/database.php:
Host: localhost
Database: bris_db
Username: root
Password: blank

If your MySQL username or password is different, edit config/database.php.

TROUBLESHOOTING
---------------
- If the page cannot connect to MySQL, confirm MySQL is running and the database
  import succeeded.
- If CSS is missing, make sure the folder name is BRIS and open the site using
  http://localhost/BRIS/ rather than opening the PHP file directly.
- If a column/table error appears, import the included database/bris.sql into
  the same MySQL server used by config/database.php.

IMPORTANT LIMITATIONS
---------------------
- This is a local/student demonstration project, not production-ready software.
- Authentication and basic role permissions are included, but the project still lacks a full audit log and production-grade privacy/security review. Do not put real residents' personal data into a public deployment.
- The sample residents and households are fictional.
- Make a database backup before testing destructive changes.
