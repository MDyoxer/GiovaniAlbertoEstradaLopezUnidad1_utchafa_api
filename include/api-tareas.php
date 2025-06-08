<?php
require_once __DIR__ . '/../conn/db_conn.php';

class Tarea {
    public static function new_tarea($nombre, $materia, $fecha_limite) {
        $database = new DataBase();
        $pdo = $database->getConnection();

        try {
            $fecha_inicio = date('Y-m-d');

            if (strtotime($fecha_limite) < strtotime($fecha_inicio)) {
                throw new Exception("La fecha límite no puede ser anterior a la fecha de inicio");
            }

            $stmt = $pdo->prepare('INSERT INTO tareas (nombre, materia, fecha_inicio, fecha_limite) VALUES (:nombre, :materia, :fecha_inicio, :fecha_limite)');
            $stmt->bindParam(':nombre', $nombre);
            $stmt->bindParam(':materia', $materia);
            $stmt->bindParam(':fecha_inicio', $fecha_inicio);
            $stmt->bindParam(':fecha_limite', $fecha_limite);

            if ($stmt->execute()) {
                header('HTTP/1.1 201 Created');
                echo json_encode(['message' => 'Tarea registrada correctamente']);
            } else {
                header('HTTP/1.1 400 Bad Request');
                echo json_encode(['message' => 'No se pudo registrar la tarea']);
            }

        } catch (PDOException $e) {
            header('HTTP/1.1 500 Internal Server Error');
            echo json_encode(['error' => $e->getMessage()]);
        } catch (Exception $ex) {
            header('HTTP/1.1 400 Bad Request');
            echo json_encode(['error' => $ex->getMessage()]);
        }
    }
}
?>
