<?php

// Database settings for XAMPP.
$host = 'localhost';
$dbName = 'bris_db';
$username = 'root';
$password = '';

try {
    $pdo = new PDO(
        "mysql:host={$host};dbname={$dbName};charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $exception) {
    exit(
        'Database connection failed. Start MySQL in XAMPP and import ' .
        'database/bris.sql using phpMyAdmin.'
    );
}
