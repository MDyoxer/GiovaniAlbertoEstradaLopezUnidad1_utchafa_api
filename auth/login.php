<?php
header("Content-Type: application/json");
require_once '../conn/db_conn.php'; 
require_once __DIR__ . '/../cors-headers.php';
$database = new Database();
$pdo = $database->getConnection(); 

$data = json_decode(file_get_contents('php://input'), true);

$email = $data['email'] ?? '';
$contra = $data['contra'] ?? '';

if (empty($email) || empty($contra)) {
    http_response_code(400);
    echo json_encode(['error' => 'Email y contraseña son requeridos']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

  if ($user && $contra === $user['contra']) {
        echo json_encode([
            'success' => true,
            'user' => [
                'id' => $user['id'],
                'email' => $user['email'],
                'nombre' => $user['nombre']
            ]
        ]);
    } else {
        http_response_code(401);
        echo json_encode(['error' => 'Credenciales inválidas']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error en la base de datos: ' . $e->getMessage()]);
}
?>