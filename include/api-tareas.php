<?php
require_once __DIR__ . '/../conn/db_conn.php';

class Tarea {
    public static function new_tarea($nombre, $materia, $fecha_limite) {
        $database = new DataBase();
        $pdo = $database->getConnection();

        try {
            // Sanitizar entradas
            $nombre = filter_var($nombre, FILTER_SANITIZE_STRING);
            $materia = filter_var($materia, FILTER_SANITIZE_STRING);
            $fecha_limite = filter_var($fecha_limite, FILTER_SANITIZE_STRING);
            
            // Validar campos obligatorios
            if (empty($nombre) || empty($materia) || empty($fecha_limite)) {
                throw new Exception("Todos los campos son obligatorios");
            }

            $fecha_inicio = date('Y-m-d');

            // Validar formato de fecha
            if (!DateTime::createFromFormat('Y-m-d', $fecha_limite)) {
                throw new Exception("Formato de fecha inválido. Use YYYY-MM-DD");
            }

            if (strtotime($fecha_limite) < strtotime($fecha_inicio)) {
                throw new Exception("La fecha límite no puede ser anterior a la fecha de inicio");
            }

            $stmt = $pdo->prepare('INSERT INTO tareas (nombre, materia, fecha_inicio, fecha_limite) VALUES (:nombre, :materia, :fecha_inicio, :fecha_limite)');
            $stmt->bindParam(':nombre', $nombre, PDO::PARAM_STR);
            $stmt->bindParam(':materia', $materia, PDO::PARAM_STR);
            $stmt->bindParam(':fecha_inicio', $fecha_inicio, PDO::PARAM_STR);
            $stmt->bindParam(':fecha_limite', $fecha_limite, PDO::PARAM_STR);

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

    //mostrar tareas
    public static function all_tareas(){
        $database = new Database();
        $pdo = $database->getConnection();

        try{
            $stmt = $pdo->prepare('SELECT * FROM tareas');
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Sanitizar resultados antes de mostrarlos
            $sanitized_results = array_map(function($item) {
                return [
                    'id' => filter_var($item['id'], FILTER_SANITIZE_NUMBER_INT),
                    'nombre' => filter_var($item['nombre'], FILTER_SANITIZE_STRING),
                    'materia' => filter_var($item['materia'], FILTER_SANITIZE_STRING),
                    'fecha_inicio' => filter_var($item['fecha_inicio'], FILTER_SANITIZE_STRING),
                    'fecha_limite' => filter_var($item['fecha_limite'], FILTER_SANITIZE_STRING)
                ];
            }, $result);
            
            header('Content-Type: application/json');
            header('HTTP/1.1 200 OK');
            echo json_encode($sanitized_results);
        } catch (PDOException $e) {
            header('HTTP/1.1 500 Error interno del servidor');
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    //eliminar tarea
    public static function EliminarTarea($id){
        $database = new Database();
        $pdo = $database->getConnection();

        try{
            // Validar y sanitizar el ID
            $id = filter_var($id, FILTER_VALIDATE_INT);
            if ($id === false || $id <= 0) {
                throw new Exception("ID de tarea inválido");
            }

            $stmt = $pdo->prepare('DELETE FROM tareas WHERE id = :id');
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);

            if ($stmt->execute()) {
                if ($stmt->rowCount() > 0) {
                    header('HTTP/1.1 200 OK');
                    echo json_encode(['message' => 'Tarea eliminada correctamente']);
                } else {
                    header('HTTP/1.1 404 Not Found');
                    echo json_encode(['message' => 'No se encontró la tarea con el ID proporcionado']);
                }
            } else {
                header('HTTP/1.1 400 Bad Request');
                echo json_encode(['message' => 'No se pudo eliminar la tarea']);
            }
        } catch (PDOException $e) {
            header('HTTP/1.1 500 Error interno del servidor');
            echo json_encode(['error' => $e->getMessage()]);
        } catch (Exception $ex) {
            header('HTTP/1.1 400 Bad Request');
            echo json_encode(['error' => $ex->getMessage()]);
        }
    }
}
?>