<?php
// Using environment variables for Railway with PostgreSQL
$host = getenv('PGHOST') ?: 'localhost';
$port = getenv('PGPORT') ?: '5432';
$dbname = getenv('PGDATABASE') ?: 'railway';
$username = getenv('PGUSER') ?: 'postgres';
$password = getenv('PGPASSWORD') ?: '';

try {
    // Using PDO for PostgreSQL
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname;user=$username;password=$password";
    $conn = new PDO($dsn);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>
