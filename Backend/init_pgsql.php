<?php
// PostgreSQL database initialization

// Database connection
$host = getenv('PGHOST') ?: 'localhost';
$port = getenv('PGPORT') ?: '5432';
$dbname = getenv('PGDATABASE') ?: 'railway';
$username = getenv('PGUSER') ?: 'postgres';
$password = getenv('PGPASSWORD') ?: '';

try {
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname;user=$username;password=$password";
    $conn = new PDO($dsn);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Successfully connected to PostgreSQL\n";
    
    // Read SQL file
    $sql = file_get_contents(__DIR__ . '/setup.sql');
    
    // Execute SQL commands
    $conn->exec($sql);
    echo "Database successfully initialized\n";
    
} catch (PDOException $e) {
    die("Database connection error: " . $e->getMessage());
}
?>