<?php 
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");
header("X-XSS-Protection: 1; mode=block");
header("Content-Security-Policy: default-src 'self'");
//CREAR LOS USUARIOS
require_once __DIR__ . '/../conn/db_conn.php';

Class Users {
    public static function crear_usuario($nombre, $email, $contra, $contra2) {
        $database = new Database();
        $pdo = $database->getConnection();

        // Validar que las contraseñas coincidan
        if($contra !== $contra2) {
            header('HTTP/1.1 400 CONTRASEÑAS NO COINCIDEN');
            echo json_encode(['message' => 'LAS CONTRASEÑAS NO COINCIDEN']);
            exit;
        }

        // Validar longitud de contraseña

          if(strlen($nombre) > 30) {
            header('HTTP/1.1 400 NOMBRE INVALIDO');
            echo json_encode(['message' => 'Nombre muy largo']);
            exit;
        }


        if(strlen($contra) < 8) {
            header('HTTP/1.1 400 CONTRASEÑA INVALIDA');
            echo json_encode(['message' => 'La contraseña debe tener al menos 8 caracteres']);
            exit;
        }

        try {
            // Verificar si el correo ya existe
            $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = :email");
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            
            if ($stmt->fetch()) {
                header('HTTP/1.1 409 CORREO YA REGISTRADO');
                echo json_encode(['message' => 'EL CORREO ELECTRÓNICO YA ESTÁ REGISTRADO']);
                exit;
            }

            // Hashear la contraseña
            $hashedPassword = password_hash($contra, PASSWORD_DEFAULT);

            $stmt = $pdo->prepare('INSERT INTO usuarios (nombre, email, contra) VALUES (:nombre, :email, :contra)');
            $stmt->bindParam(':nombre', $nombre);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':contra', $hashedPassword);

            if($stmt->execute()) {
                header('HTTP/1.1 201 USUARIO CREADO CORRECTAMENTE');
                echo json_encode(['message' => 'USUARIO CREADO CORRECTAMENTE']);
            } else {
                header('HTTP/1.1 400 FALLO AL CREAR EL USUARIO');
                echo json_encode(['message' => 'FALLO AL CREAR EL USUARIO']);
            }
        } catch(PDOException $e) {
            header('HTTP/1.1 500 FALLO EN EL SERVIDOR');
            echo json_encode(['message' => 'FALLO EN EL SERVIDOR']);
            error_log('Error en crear_usuario: ' . $e->getMessage()); // Log del error
        }
    }
}
?>