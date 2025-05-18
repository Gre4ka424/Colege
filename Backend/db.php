<?php
// Использование переменных окружения для Railway с PostgreSQL
$host = getenv('PGHOST') ?: 'localhost';
$port = getenv('PGPORT') ?: '5432';
$dbname = getenv('PGDATABASE') ?: 'railway';
$username = getenv('PGUSER') ?: 'postgres';
$password = getenv('PGPASSWORD') ?: '';

try {
    // Используем PDO для PostgreSQL
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname;user=$username;password=$password";
    $conn = new PDO($dsn);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>
