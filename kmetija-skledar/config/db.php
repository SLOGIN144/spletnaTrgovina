<?php
// Povezava s podatkovno bazo (XAMPP privzeto: uporabnik root, brez gesla)
$host = 'localhost';
$db   = 'kmetija_skledar';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
} catch (PDOException $e) {
    die('Napaka pri povezavi z bazo: ' . $e->getMessage());
}
