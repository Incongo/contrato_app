<?php
// public/cambiar_estado.php
require_once __DIR__ . '/../Core/Database.php';
require_once __DIR__ . '/../Core/Auth.php';
require_once __DIR__ . '/../Core/AuthMiddleware.php';

session_start();
AuthMiddleware::protegerPagina();

$auth = new Auth();
$pdo = Database::getInstance();
$usuarioId = $_SESSION['user_id'];

// Log para depuración
error_log("=== INICIO cambiar_estado.php ===");
error_log("Usuario ID: " . $usuarioId);
error_log("POST: " . print_r($_POST, true));

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $resultadoId = $_POST['id'] ?? 0;
    $nuevoEstado = (int)($_POST['estado'] ?? 0);

    error_log("Resultado ID: $resultadoId, Nuevo estado: $nuevoEstado");

    if (!in_array($nuevoEstado, [0, 1, 2])) {
        error_log("Estado no válido");
        http_response_code(400);
        echo json_encode(['error' => 'Estado no válido']);
        exit;
    }

    try {
        // PRIMERO: Verificar que el resultado existe
        $check = $pdo->prepare("SELECT id, estado_usuario FROM resultados WHERE id = ?");
        $check->execute([$resultadoId]);
        $resultado = $check->fetch();

        error_log("Resultado antes: " . print_r($resultado, true));

        if (!$resultado) {
            error_log("Resultado no encontrado");
            http_response_code(404);
            echo json_encode(['error' => 'Resultado no encontrado']);
            exit;
        }

        // SEGUNDO: Actualizar (sin JOIN con busquedas por ahora)
        $stmt = $pdo->prepare("UPDATE resultados SET estado_usuario = ? WHERE id = ?");
        $result = $stmt->execute([$nuevoEstado, $resultadoId]);

        error_log("Update ejecutado: " . ($result ? 'OK' : 'FALLO'));

        if ($result) {
            // Verificar que realmente cambió
            $check2 = $pdo->prepare("SELECT estado_usuario FROM resultados WHERE id = ?");
            $check2->execute([$resultadoId]);
            $nuevoValor = $check2->fetchColumn();

            error_log("Estado después: " . $nuevoValor);

            echo json_encode(['success' => true, 'estado' => $nuevoValor]);
        } else {
            error_log("Error en update");
            http_response_code(500);
            echo json_encode(['error' => 'Error al actualizar']);
        }
    } catch (PDOException $e) {
        error_log("EXCEPCIÓN: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Error de base de datos']);
    }
    exit;
}
