<?php
try {
    $dotenv = parse_ini_file(__DIR__.'/../.env');
    $db = new PDO("mysql:host={$dotenv['DB_HOST']};dbname={$dotenv['DB_NAME']}", $dotenv['DB_USERNAME'], $dotenv['DB_PASSWORD'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
