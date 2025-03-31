<?php
function getDatabaseConnection()
{
    $host = "localhost";
    $username = "DevConnect";
    $password = "";
    $database = "DevConnect";
    
    try {
        $db = new PDO("mysql:host=$host;dbname=$database", $username, $password);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $db;
    } catch (PDOException $e) {
        die("Connection failed: " . $e->getMessage());
    }
}

$db = getDatabaseConnection();
