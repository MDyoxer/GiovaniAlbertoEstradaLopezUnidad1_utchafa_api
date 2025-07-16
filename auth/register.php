<?php 

require_once __DIR__ . '/../cors-headers.php';
require_once __DIR__ . '/../include/api-auth.php';
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");
header("X-XSS-Protection: 1; mode=block");
header("Content-Security-Policy: default-src 'self'");
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    if (!isset($input['nombre'], $input['email'], $input['contra'], $input['contra2'])) {
        header('HTTP/1.1 400 SOLICITUD INVALIDA');
        echo json_encode(['message' => 'Datos incompletos']);
        exit;
    }

    // Validación básica de email
    if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
        header('HTTP/1.1 400 EMAIL INVALIDO');
        echo json_encode(['message' => 'El formato del email es incorrecto']);
        exit;
    }

    Users::crear_usuario(
        htmlspecialchars($input['nombre']),
        $input['email'],
        $input['contra'],
        $input['contra2']
    );
} else {
    header('HTTP/1.1 405 METODO NO PERMITIDO');
    echo json_encode(['message' => 'METODO NO PERMITIDO']);
}
?>