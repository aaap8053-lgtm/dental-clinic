<?php
$host = 'localhost';
$db   = 'dental_clinic_db';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    // تۆمارکردنی هەڵەکە لە سێرڤەردا لەبری نیشاندانی بۆ بەکارهێنەر بۆ پاراستنی ئاسایش
    error_log("Database Connection Error: " . $e->getMessage());
    die("کێشەیەک لە پەیوەندیکردن بە بنکەی داتایان دروست بوو. تکایە دواتر هەوڵبدەرەوە.");
}