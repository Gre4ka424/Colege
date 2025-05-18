<?php
// Инициализация базы данных PostgreSQL

// Подключение к базе данных
$host = getenv('PGHOST') ?: 'localhost';
$port = getenv('PGPORT') ?: '5432';
$dbname = getenv('PGDATABASE') ?: 'railway';
$username = getenv('PGUSER') ?: 'postgres';
$password = getenv('PGPASSWORD') ?: '';

try {
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname;user=$username;password=$password";
    $conn = new PDO($dsn);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Успешное подключение к PostgreSQL\n";
    
    // Читаем SQL-файл
    $sql = file_get_contents(__DIR__ . '/setup.sql');
    
    // Выполняем SQL-команды
    $conn->exec($sql);
    echo "База данных успешно инициализирована\n";
    
} catch (PDOException $e) {
    die("Ошибка подключения к базе данных: " . $e->getMessage());
}
?>