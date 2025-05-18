<?php
// Разрешаем CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Включаем отображение ошибок для отладки
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Обработка OPTIONS запросов (preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header("HTTP/1.1 200 OK");
    exit;
}

require 'db.php';
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$request = [];
if (isset($_SERVER['PATH_INFO'])) {
    $request = explode('/', trim($_SERVER['PATH_INFO'], '/'));
}

// Функция логирования
function logError($message, $data = null) {
    $log = date('Y-m-d H:i:s') . " - " . $message . "\n";
    if ($data) {
        $log .= "Data: " . print_r($data, true) . "\n";
    }
    file_put_contents('error.log', $log, FILE_APPEND);
}

// Подключаем библиотеку JWT
require_once __DIR__ . '/../vendor/autoload.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

// Секретный ключ для подписи JWT токенов 
// В production нужно хранить в переменных окружения
define('JWT_SECRET_KEY', 'your_super_secret_key_for_mediteranian_college_alumni_system');
define('JWT_EXPIRATION', 3600); // Срок действия токена в секундах (1 час)

// Функция для генерации JWT токена
function generateJWT($user_id, $role_id) {
    $issued_at = time();
    $expiration = $issued_at + JWT_EXPIRATION;
    
    $payload = [
        'iat' => $issued_at,         // Время создания токена
        'exp' => $expiration,        // Время истечения токена
        'data' => [
            'user_id' => $user_id,
            'role_id' => $role_id
        ]
    ];
    
    return JWT::encode($payload, JWT_SECRET_KEY, 'HS256');
}

// Функция для проверки JWT токена
function validateJWT() {
    $headers = getallheaders();
    
    if (!isset($headers['Authorization'])) {
        return null;
    }
    
    $authHeader = $headers['Authorization'];
    $token = null;
    
    // Формат: Bearer <token>
    if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
        $token = $matches[1];
    }
    
    if (!$token) {
        return null;
    }
    
    try {
        $decoded = JWT::decode($token, new Key(JWT_SECRET_KEY, 'HS256'));
        return $decoded->data;
    } catch (Exception $e) {
        logError("JWT validation error: " . $e->getMessage());
        return null;
    }
}

// GET /api.php/faculties - получить все факультеты
if ($method === 'GET' && isset($request[0]) && $request[0] === 'faculties') {
    try {
        $stmt = $conn->prepare("SELECT * FROM faculties");
        $stmt->execute();
        $faculties = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Стандартизация имен полей для совместимости
        foreach ($faculties as &$faculty) {
            if (isset($faculty['name']) && !isset($faculty['faculty_name'])) {
                $faculty['faculty_name'] = $faculty['name'];
            } else if (isset($faculty['faculty_name']) && !isset($faculty['name'])) {
                $faculty['name'] = $faculty['faculty_name'];
            }
        }
        
        echo json_encode(['status' => 'success', 'data' => $faculties]);
    } catch (PDOException $e) {
        logError("Error getting faculties: " . $e->getMessage());
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

// GET /api.php/users - получить всех пользователей
if ($method === 'GET' && isset($request[0]) && $request[0] === 'users') {
    try {
        $stmt = $conn->prepare("SELECT * FROM users");
        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['status' => 'success', 'data' => $users]);
    } catch (PDOException $e) {
        logError("Error getting users: " . $e->getMessage());
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

// POST /api.php/users - регистрация нового пользователя
if ($method === 'POST' && isset($request[0]) && $request[0] === 'users') {
    try {
        $input = file_get_contents("php://input");
        $data = json_decode($input, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            logError("JSON decode error: " . json_last_error_msg(), $input);
            echo json_encode(["status" => "error", "message" => "Invalid JSON data"]);
            exit;
        }

        // Проверяем наличие всех необходимых полей
        $required_fields = ['full_name', 'email', 'password', 'student_id'];
        foreach ($required_fields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                logError("Missing required field: " . $field, $data);
                echo json_encode(["status" => "error", "message" => "Missing required field: " . $field]);
                exit;
            }
        }
        
        // Проверка, существует ли уже пользователь с таким email
        $check_stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE email = :email");
        $check_stmt->bindParam(':email', $data['email']);
        $check_stmt->execute();
        $email_count = $check_stmt->fetchColumn();
        
        if ($email_count > 0) {
            echo json_encode(["status" => "error", "message" => "Email already exists"]);
            exit;
        }
        
        // Проверка, существует ли уже пользователь с таким student_id
        $check_stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE student_id = :student_id");
        $check_stmt->bindParam(':student_id', $data['student_id']);
        $check_stmt->execute();
        $student_id_count = $check_stmt->fetchColumn();
        
        if ($student_id_count > 0) {
            echo json_encode(["status" => "error", "message" => "Student ID already exists"]);
            exit;
        }
        
        // Вставка нового пользователя
        $stmt = $conn->prepare("INSERT INTO users (full_name, email, password_hash, phone, student_id, faculty_id, graduation_year, bio, role_id, status) 
                               VALUES (:full_name, :email, :password_hash, :phone, :student_id, :faculty_id, :graduation_year, :bio, :role_id, :status)");
        
        $password_hashed = password_hash($data['password'], PASSWORD_DEFAULT);
        $phone = isset($data['phone']) ? $data['phone'] : null;
        $faculty_id = isset($data['faculty_id']) ? $data['faculty_id'] : null;
        $graduation_year = isset($data['graduation_year']) ? $data['graduation_year'] : null;
        $bio = isset($data['bio']) ? $data['bio'] : null;
        $role_id = isset($data['role_id']) ? $data['role_id'] : 3; // По умолчанию роль 3 (pending)
        $status = isset($data['status']) ? $data['status'] : 'pending';
        
        $stmt->bindParam(':full_name', $data['full_name']);
        $stmt->bindParam(':email', $data['email']);
        $stmt->bindParam(':password_hash', $password_hashed);
        $stmt->bindParam(':phone', $phone);
        $stmt->bindParam(':student_id', $data['student_id']);
        $stmt->bindParam(':faculty_id', $faculty_id);
        $stmt->bindParam(':graduation_year', $graduation_year);
        $stmt->bindParam(':bio', $bio);
        $stmt->bindParam(':role_id', $role_id);
        $stmt->bindParam(':status', $status);
        
        $result = $stmt->execute();
        if ($result) {
            echo json_encode(["status" => "success", "message" => "User registered successfully"]);
        } else {
            logError("Execute error: " . implode(", ", $stmt->errorInfo()));
            echo json_encode(["status" => "error", "message" => "Failed to register user"]);
        }
        
    } catch (PDOException $e) {
        logError("PDOException: " . $e->getMessage());
        echo json_encode(["status" => "error", "message" => "Server error occurred"]);
    }
    exit;
}

// POST /api.php/login - аутентификация пользователя
if ($method === 'POST' && isset($request[0]) && $request[0] === 'login') {
    try {
        $data = json_decode(file_get_contents("php://input"), true);
        
        if (!isset($data['email']) || !isset($data['password'])) {
            echo json_encode(["status" => "error", "message" => "Email and password required"]);
            exit;
        }
        
        // Проверяем учетные данные пользователя
        $stmt = $conn->prepare("
            SELECT u.user_id, u.full_name, u.email, u.password_hash, u.role_id, u.faculty_id, 
                   u.student_id, u.graduation_year, u.phone, u.bio, u.status
            FROM users u 
            WHERE u.email = :email
        ");
        
        $stmt->bindParam(':email', $data['email']);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($data['password'], $user['password_hash'])) {
            // Генерация JWT токена
            $token = generateJWT($user['user_id'], $user['role_id']);
            
            // Получаем полные данные пользователя, но исключаем password_hash
            unset($user['password_hash']);
            
            // Разделяем полное имя на компоненты (если нужно)
            $name_parts = explode(' ', $user['full_name']);
            $first_name = $name_parts[0] ?? '';
            $last_name = $name_parts[1] ?? '';
            $middle_name = isset($name_parts[2]) ? implode(' ', array_slice($name_parts, 2)) : '';
            
            // Формируем объект пользователя для фронтенда
            $user_data = [
                "user_id" => $user['user_id'],
                "first_name" => $first_name,
                "last_name" => $last_name,
                "middle_name" => $middle_name,
                "email" => $user['email'],
                "role_id" => (int)$user['role_id'],
                "faculty_id" => $user['faculty_id'] ? (int)$user['faculty_id'] : null,
                "student_id" => $user['student_id'],
                "graduation_year" => $user['graduation_year'] ? (int)$user['graduation_year'] : null,
                "phone" => $user['phone'],
                "status" => $user['status']
            ];
            
            echo json_encode([
                "status" => "success",
                "message" => "Login successful",
                "token" => $token,
                "user" => $user_data
            ]);
        } else {
            echo json_encode(["status" => "error", "message" => "Invalid credentials"]);
        }
    } catch (PDOException $e) {
        logError("Login error: " . $e->getMessage());
        echo json_encode(["status" => "error", "message" => "Server error occurred"]);
    }
    exit;
}

// GET /api.php/events - получение всех событий
if ($method === 'GET' && isset($request[0]) && $request[0] === 'events') {
    try {
        $stmt = $conn->prepare("SELECT * FROM events ORDER BY event_date DESC");
        $stmt->execute();
        $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['status' => 'success', 'data' => $events]);
    } catch (PDOException $e) {
        logError("Error getting events: " . $e->getMessage());
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

// GET /api.php/achievements - получение всех достижений
if ($method === 'GET' && isset($request[0]) && $request[0] === 'achievements') {
    try {
        $stmt = $conn->prepare("
            SELECT a.*, u.full_name 
            FROM achievements a
            JOIN users u ON a.user_id = u.user_id
            ORDER BY a.achievement_date DESC
        ");
        $stmt->execute();
        $achievements = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['status' => 'success', 'data' => $achievements]);
    } catch (PDOException $e) {
        logError("Error getting achievements: " . $e->getMessage());
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

// GET /api.php/profile - получить профиль текущего пользователя (защищенный эндпоинт)
if ($method === 'GET' && isset($request[0]) && $request[0] === 'profile') {
    $jwt_data = validateJWT();
    
    if (!$jwt_data) {
        echo json_encode([
            "status" => "error", 
            "message" => "Unauthorized access"
        ]);
        exit;
    }
    
    try {
        $stmt = $conn->prepare("
            SELECT u.user_id, u.full_name, u.email, u.student_id, u.faculty_id, 
                   u.graduation_year, u.phone, u.bio, u.status, u.role_id,
                   f.name as faculty_name
            FROM users u
            LEFT JOIN faculties f ON u.faculty_id = f.faculty_id
            WHERE u.user_id = :user_id
        ");
        
        $stmt->bindParam(':user_id', $jwt_data->user_id);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            echo json_encode([
                "status" => "error", 
                "message" => "User not found"
            ]);
            exit;
        }
        
        // Разделяем полное имя на компоненты
        $name_parts = explode(' ', $user['full_name']);
        $first_name = $name_parts[0] ?? '';
        $last_name = $name_parts[1] ?? '';
        $middle_name = isset($name_parts[2]) ? implode(' ', array_slice($name_parts, 2)) : '';
        
        $user_data = [
            "user_id" => $user['user_id'],
            "first_name" => $first_name,
            "last_name" => $last_name,
            "middle_name" => $middle_name,
            "email" => $user['email'],
            "student_id" => $user['student_id'],
            "faculty_id" => $user['faculty_id'] ? (int)$user['faculty_id'] : null,
            "faculty_name" => $user['faculty_name'],
            "graduation_year" => $user['graduation_year'] ? (int)$user['graduation_year'] : null,
            "phone" => $user['phone'],
            "bio" => $user['bio'],
            "status" => $user['status'],
            "role_id" => (int)$user['role_id']
        ];
        
        echo json_encode([
            "status" => "success", 
            "user" => $user_data
        ]);
        
    } catch (PDOException $e) {
        logError("Profile error: " . $e->getMessage());
        echo json_encode([
            "status" => "error", 
            "message" => "Server error occurred"
        ]);
    }
    exit;
}

// GET /api.php/faculties/:id - получить конкретный факультет по ID
if ($method === 'GET' && isset($request[0]) && $request[0] === 'faculties' && isset($request[1]) && is_numeric($request[1])) {
    try {
        $faculty_id = (int)$request[1];
        $stmt = $conn->prepare("SELECT * FROM faculties WHERE faculty_id = :faculty_id");
        $stmt->bindParam(':faculty_id', $faculty_id);
        $stmt->execute();
        $faculty = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($faculty) {
            // Обеспечиваем что имя факультета доступно как в faculty_name, так и в name
            if (isset($faculty['name']) && !isset($faculty['faculty_name'])) {
                $faculty['faculty_name'] = $faculty['name'];
            } else if (isset($faculty['faculty_name']) && !isset($faculty['name'])) {
                $faculty['name'] = $faculty['faculty_name'];
            }
            
            echo json_encode(['status' => 'success', 'data' => $faculty]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Faculty not found']);
        }
    } catch (PDOException $e) {
        logError("Error getting faculty by ID: " . $e->getMessage());
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

// Защищенный эндпоинт: POST /api.php/achievements - добавление нового достижения
if ($method === 'POST' && isset($request[0]) && $request[0] === 'achievements') {
    // Проверка JWT токена
    $jwt_data = validateJWT();
    
    if (!$jwt_data) {
        echo json_encode([
            "status" => "error", 
            "message" => "Unauthorized access"
        ]);
        exit;
    }
    
    try {
        $data = json_decode(file_get_contents("php://input"), true);
        
        // Проверка обязательных полей
        if (!isset($data['title']) || !isset($data['achievement_date']) || !isset($data['description'])) {
            echo json_encode([
                "status" => "error", 
                "message" => "Missing required fields"
            ]);
            exit;
        }
        
        $stmt = $conn->prepare("
            INSERT INTO achievements (user_id, title, achievement_date, description) 
            VALUES (:user_id, :title, :achievement_date, :description)
        ");
        
        $stmt->bindParam(':user_id', $jwt_data->user_id);
        $stmt->bindParam(':title', $data['title']);
        $stmt->bindParam(':achievement_date', $data['achievement_date']);
        $stmt->bindParam(':description', $data['description']);
        
        $result = $stmt->execute();
        
        if ($result) {
            $achievement_id = $conn->lastInsertId();
            echo json_encode([
                "status" => "success", 
                "message" => "Achievement added successfully",
                "achievement_id" => $achievement_id
            ]);
        } else {
            echo json_encode([
                "status" => "error", 
                "message" => "Failed to add achievement"
            ]);
        }
        
    } catch (PDOException $e) {
        logError("Achievement error: " . $e->getMessage());
        echo json_encode([
            "status" => "error", 
            "message" => "Server error occurred"
        ]);
    }
    exit;
}

// POST /api.php/upload_avatar - upload user avatar (protected endpoint)
if ($method === 'POST' && isset($request[0]) && $request[0] === 'upload_avatar') {
    // JWT token validation
    $jwt_data = validateJWT();
    
    if (!$jwt_data) {
        echo json_encode([
            "status" => "error", 
            "message" => "Unauthorized access"
        ]);
        exit;
    }
    
    try {
        // Check if files were uploaded
        if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode([
                "status" => "error", 
                "message" => "No file uploaded or upload error"
            ]);
            exit;
        }
        
        $uploaded_file = $_FILES['avatar'];
        
        // Validate file type
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($uploaded_file['type'], $allowed_types)) {
            echo json_encode([
                "status" => "error", 
                "message" => "Invalid file type. Only JPEG, PNG and GIF are allowed."
            ]);
            exit;
        }
        
        // Check file size (max 2MB)
        $max_size = 2 * 1024 * 1024; // 2MB
        if ($uploaded_file['size'] > $max_size) {
            echo json_encode([
                "status" => "error", 
                "message" => "File too large. Maximum size is 2MB."
            ]);
            exit;
        }
        
        // Create avatars directory if it doesn't exist
        $upload_dir = __DIR__ . '/uploads/avatars';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        // Generate unique filename
        $file_extension = pathinfo($uploaded_file['name'], PATHINFO_EXTENSION);
        $new_filename = $jwt_data->user_id . '_' . time() . '.' . $file_extension;
        $file_path = $upload_dir . '/' . $new_filename;
        
        // Move uploaded file
        if (move_uploaded_file($uploaded_file['tmp_name'], $file_path)) {
            // Get file URL - relative path to access from frontend
            $file_url = 'Backend/uploads/avatars/' . $new_filename;
            
            // Update database record
            $stmt = $conn->prepare("UPDATE users SET avatar_url = :avatar_url WHERE user_id = :user_id");
            $stmt->bindParam(':avatar_url', $file_url);
            $stmt->bindParam(':user_id', $jwt_data->user_id);
            $stmt->execute();
            
            echo json_encode([
                "status" => "success", 
                "message" => "Avatar uploaded successfully",
                "avatar_url" => $file_url
            ]);
        } else {
            echo json_encode([
                "status" => "error", 
                "message" => "Failed to save the uploaded file"
            ]);
        }
        
    } catch (Exception $e) {
        logError("Avatar upload error: " . $e->getMessage());
        echo json_encode([
            "status" => "error", 
            "message" => "Server error occurred during file upload"
        ]);
    }
    exit;
}

// Обработка неизвестных запросов
echo json_encode(["status" => "error", "message" => "Unknown API endpoint"]);
?>