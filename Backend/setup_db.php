<?php
// Database initialization

// Database connection
$host = getenv('PGHOST');
$port = getenv('PGPORT');
$dbname = getenv('PGDATABASE');
$username = getenv('PGUSER');
$password = getenv('PGPASSWORD');

echo "Connecting to DB: $host:$port/$dbname ($username)\n";

try {
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";
    $conn = new PDO($dsn, $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "DB connection successful\n";
    
    // Read SQL file
    $sql = file_get_contents(__DIR__ . '/setup.sql');
    
    // Execute SQL commands
    $conn->exec($sql);
    
    echo "Tables created successfully\n";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
?> 