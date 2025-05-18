<?php
// Инициализация базы данных

// Подключение к базе данных
$host = getenv('PGHOST');
$port = getenv('PGPORT');
$dbname = getenv('PGDATABASE');
$username = getenv('PGUSER');
$password = getenv('PGPASSWORD');

echo "Подключение к БД: $host:$port/$dbname ($username)\n";

try {
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";
    $conn = new PDO($dsn, $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Подключение к БД успешно\n";
    
    // Читаем SQL-файл
    $sql = file_get_contents(__DIR__ . '/setup.sql');
    
    // Выполняем SQL-команды
    $conn->exec($sql);
    
    echo "Таблицы созданы успешно\n";
    
} catch (PDOException $e) {
    echo "Ошибка: " . $e->getMessage() . "\n";
    exit(1);
}
?> 