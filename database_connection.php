<?php

// database_connection.php
// Central PDO connection used by the legacy LMS codebase.

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$host = "localhost";
$username = "root";
$password = "gmthrsd37";
$database = "library";

try {
    $connect = new PDO("mysql:host={$host};dbname={$database};charset=utf8", $username, $password);
    $connect->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

?>