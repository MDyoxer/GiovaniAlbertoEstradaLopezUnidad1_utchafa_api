<?php
header("Content-Type: application/json");
require_once '../conn/db_conn.php';
require_once __DIR__ . '/../cors-headers.php';

// Headers de seguridad básicos
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");
header("X-XSS-Protection: 1; mode=block");
header("Content-Security-Policy: default-src 'self'");


$database = new Database();
$pdo = $database->getConnection();

$data = json_decode(file_get_contents('php://input'), true);




// Validar JSON
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Datos inválidos']);
    exit;
}

$email = filter_var($data['email'] ?? '', FILTER_SANITIZE_EMAIL);
$contra = $data['contra'] ?? '';

// Validaciones
if (empty($email)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Email es requerido']);
    exit;
}

if (empty($contra)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Contraseña es requerida']);
    exit;
}

try {
    // Verificar bloqueo
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        if ($user['bloqueado_hasta'] && strtotime($user['bloqueado_hasta']) > time()) {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'error' => 'Cuenta bloqueada temporalmente',
                'bloqueado_hasta' => $user['bloqueado_hasta']
            ]);
            exit;
        }

if (password_verify($contra, $user['contra'])) {
        
            $stmt = $pdo->prepare("UPDATE usuarios SET intentos_fallidos = 0 WHERE email = ?");
            $stmt->execute([$email]);
            
            // Respuesta exitosa
            echo json_encode([
                'success' => true,
                'user' => [
                    'id' => $user['id'],
                    'email' => $user['email'],
                    'nombre' => $user['nombre']
                 ]      
                   ]);

        } else {
            // Incrementar intentos fallidos
            $newAttempts = $user['intentos_fallidos'] + 1;
            $stmt = $pdo->prepare("UPDATE usuarios SET intentos_fallidos = ? WHERE email = ?");
            $stmt->execute([$newAttempts, $email]);
            
            // Bloquear después de 5 intentos
         // En la parte donde bloqueas por intentos fallidos:
if ($newAttempts >= 3) {
    $blockTime = date('Y-m-d H:i:s', strtotime('+30 minutes'));
    $stmt = $pdo->prepare("UPDATE usuarios SET bloqueado_hasta = ? WHERE email = ?");
    $stmt->execute([$blockTime, $email]);
    
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'error' => 'Cuenta bloqueada',
        'message' => 'Demasiados intentos fallidos. Intenta nuevamente después de ' . $blockTime,
        'bloqueado_hasta' => $blockTime
    ]);
    exit;
}   else {
                http_response_code(401);
                echo json_encode([
                    'success' => false,
                    'error' => 'Credenciales incorrectas',
                    'intentos_restantes' => 3 - $newAttempts
                ]);
            }
        }
    } else {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Credenciales incorrectas']);
    }
} catch (PDOException $e) {
    error_log('Database error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error en el servidor']);
}
?>