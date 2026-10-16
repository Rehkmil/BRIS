<?php
require_once 'config/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit;
}

verify_token();
$_SESSION = [];
session_regenerate_id(true);
flash('You have been logged out successfully.');
header('Location: login.php');
exit;
