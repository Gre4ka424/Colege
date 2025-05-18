<?php
require_once 'db.php';
require_once 'jwt_handler.php';

// Данные для создания администратора
$admin_data = [
    'full_name' => 'System Administrator',
    'email' => 'admin@prs.com',
    'password' => 'Admin123!',
    'phone' => '+1234567890',
    'national_id' => 'ADMIN001',
    'role_id' => 1
];

try {
    // Проверяем, существует ли уже пользователь с таким email
    $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
    $stmt->bind_param("s", $admin_data['email']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Если администратор уже существует, обновляем его роль
        $row = $result->fetch_assoc();
        $update_stmt = $conn->prepare("UPDATE users SET role_id = 1 WHERE user_id = ?");
        $update_stmt->bind_param("i", $row['user_id']);
        $update_stmt->execute();
        echo "Существующий пользователь обновлен до роли администратора.\n";
    } else {
        // Создаем нового администратора
        $prs_id = 'PRS' . rand(1000, 9999);
        $password_hash = password_hash($admin_data['password'], PASSWORD_DEFAULT);
        
        $stmt = $conn->prepare("INSERT INTO users (full_name, email, password_hash, phone, national_id, prs_id, role_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssi", 
            $admin_data['full_name'],
            $admin_data['email'],
            $password_hash,
            $admin_data['phone'],
            $admin_data['national_id'],
            $prs_id,
            $admin_data['role_id']
        );
        
        if ($stmt->execute()) {
            echo "Администратор успешно создан.\n";
            echo "Email: " . $admin_data['email'] . "\n";
            echo "Пароль: " . $admin_data['password'] . "\n";
        } else {
            echo "Ошибка при создании администратора: " . $stmt->error . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "Произошла ошибка: " . $e->getMessage() . "\n";
}
?> 