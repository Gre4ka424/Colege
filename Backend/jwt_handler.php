<?php
// Секретный ключ для подписи JWT
$jwt_secret = 'prs_secret_key_2024';

// Функция для создания JWT токена
function createJWT($user_id) {
    global $jwt_secret;
    global $conn;
    
    // Получаем роль пользователя из базы данных
    $role_id = 3; // По умолчанию роль обычного пользователя
    
    try {
        $stmt = $conn->prepare("SELECT role_id FROM users WHERE user_id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $stmt->bind_result($db_role_id);
            if ($stmt->fetch()) {
                $role_id = $db_role_id;
            }
            $stmt->close();
        }
    } catch (Exception $e) {
        error_log("Error getting role_id: " . $e->getMessage());
    }
    
    // Задаем время истечения токена (24 часа)
    $issued_at = time();
    $expiration = $issued_at + (60 * 60 * 24); // 24 часа
    
    // Создаем полезную нагрузку JWT
    $payload = array(
        'user_id' => $user_id,
        'role_id' => (int)$role_id, // Убедимся, что role_id - это число
        'iat' => $issued_at,
        'exp' => $expiration
    );
    
    // Кодируем заголовок
    $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
    $header = rtrim(strtr(base64_encode($header), '+/', '-_'), '=');
    
    // Кодируем полезную нагрузку
    $payload = json_encode($payload);
    $payload = rtrim(strtr(base64_encode($payload), '+/', '-_'), '=');
    
    // Создаем подпись
    $signature = hash_hmac('sha256', "$header.$payload", $jwt_secret, true);
    $signature = rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');
    
    // Собираем JWT
    return "$header.$payload.$signature";
}

// Функция для проверки JWT токена
function validateJWT($jwt) {
    global $jwt_secret;
    
    // Убираем 'Bearer ' из начала токена, если он есть
    if (strpos($jwt, 'Bearer ') === 0) {
        $jwt = substr($jwt, 7);
    }
    
    // Разбиваем JWT на части
    $parts = explode('.', $jwt);
    if (count($parts) !== 3) {
        return false;
    }
    
    list($header, $payload, $signature) = $parts;
    
    // Проверяем подпись
    $valid_signature = hash_hmac('sha256', "$header.$payload", $jwt_secret, true);
    $valid_signature = rtrim(strtr(base64_encode($valid_signature), '+/', '-_'), '=');
    
    if ($signature !== $valid_signature) {
        return false;
    }
    
    // Декодируем полезную нагрузку
    $payload = json_decode(base64_decode(strtr($payload, '-_', '+/')), true);
    
    // Проверяем срок действия
    if (isset($payload['exp']) && $payload['exp'] < time()) {
        return false;
    }
    
    return $payload;
}
?>