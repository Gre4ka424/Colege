<?php
// Подключаемся к базе данных
require_once 'db.php';

// Функция для проверки существования таблицы
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

// Проверяем, существуют ли уже таблицы
if (!tableExists('faculties', $conn)) {
    // Таблицы не существуют, загружаем и выполняем SQL-скрипт
    $sql = file_get_contents(__DIR__ . '/setup.sql');
    
    try {
        $conn->exec($sql);
        echo "База данных успешно инициализирована.\n";
    } catch (PDOException $e) {
        die("Ошибка при инициализации базы данных: " . $e->getMessage());
    }
} else {
    echo "База данных уже существует.\n";
}
?> 