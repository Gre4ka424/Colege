<?php
// Secret key for JWT signing
$jwt_secret = 'prs_secret_key_2024';

// Function to create JWT token
function createJWT($user_id) {
    global $jwt_secret;
    global $conn;
    
    // Get user role from database
    $role_id = 3; // Default regular user role
    
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
    
    // Set token expiration time (24 hours)
    $issued_at = time();
    $expiration = $issued_at + (60 * 60 * 24); // 24 hours
    
    // Create JWT payload
    $payload = array(
        'user_id' => $user_id,
        'role_id' => (int)$role_id, // Ensure role_id is a number
        'iat' => $issued_at,
        'exp' => $expiration
    );
    
    // Encode header
    $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
    $header = rtrim(strtr(base64_encode($header), '+/', '-_'), '=');
    
    // Encode payload
    $payload = json_encode($payload);
    $payload = rtrim(strtr(base64_encode($payload), '+/', '-_'), '=');
    
    // Create signature
    $signature = hash_hmac('sha256', "$header.$payload", $jwt_secret, true);
    $signature = rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');
    
    // Assemble JWT
    return "$header.$payload.$signature";
}

// Function to validate JWT token
function validateJWT($jwt) {
    global $jwt_secret;
    
    // Remove 'Bearer ' from the beginning of the token if present
    if (strpos($jwt, 'Bearer ') === 0) {
        $jwt = substr($jwt, 7);
    }
    
    // Split JWT into parts
    $parts = explode('.', $jwt);
    if (count($parts) !== 3) {
        return false;
    }
    
    list($header, $payload, $signature) = $parts;
    
    // Verify signature
    $valid_signature = hash_hmac('sha256', "$header.$payload", $jwt_secret, true);
    $valid_signature = rtrim(strtr(base64_encode($valid_signature), '+/', '-_'), '=');
    
    if ($signature !== $valid_signature) {
        return false;
    }
    
    // Decode payload
    $payload = json_decode(base64_decode(strtr($payload, '-_', '+/')), true);
    
    // Check expiration
    if (isset($payload['exp']) && $payload['exp'] < time()) {
        return false;
    }
    
    return $payload;
}
?>