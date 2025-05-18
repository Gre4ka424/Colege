<?php
// Connect to the database
require_once 'db.php';

// Function to check if a table exists
function tableExists($tableName, $conn) {
    $query = $conn->prepare(
        "SELECT EXISTS (
            SELECT FROM information_schema.tables 
            WHERE table_schema = 'public' 
            AND table_name = :table
        )"
    );
    $query->bindParam(':table', $tableName);
    $query->execute();
    return $query->fetchColumn();
}

// Check if tables already exist
if (!tableExists('faculties', $conn)) {
    // Tables don't exist, load and execute SQL script
    $sql = file_get_contents(__DIR__ . '/setup.sql');
    
    try {
        $conn->exec($sql);
        echo "Database successfully initialized.\n";
    } catch (PDOException $e) {
        die("Error initializing database: " . $e->getMessage());
    }
} else {
    echo "Database already exists.\n";
}
?> 