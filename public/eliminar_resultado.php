<?php
// public/eliminar_resultado.php
// Versión de depuración

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../Core/Database.php';
require_once __DIR__ . '/../Core/Auth.php';
require_once __DIR__ . '/../Core/AuthMiddleware.php';

session_start();
AuthMiddleware::protegerPagina();

$auth = new Auth();
$pdo = Database::getInstance();
$usuarioId = $_SESSION['user_id'];

// Mostrar información de depuración (solo visible si miras el código fuente)
echo "<!-- DEBUG: usuarioId = $usuarioId -->\n";
echo "<!-- DEBUG: POST = " . print_r($_POST, true) . " -->\n";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo "<!-- DEBUG: No es POST -->\n";
    header('Location: index.php');
    exit;
}

$id = $_POST['id'] ?? 0;
echo "<!-- DEBUG: id = $id -->\n";

if (!$id) {
    echo "<!-- DEBUG: ID no válido -->\n";
    $_SESSION['mensaje'] = "❌ ID no válido";
    header('Location: index.php');
    exit;
}

try {
    // Verificar que el resultado pertenece a una búsqueda del usuario
    $check = $pdo->prepare("
        SELECT r.id, r.titulo, b.nombre as busqueda
        FROM resultados r
        JOIN busquedas b ON r.busqueda_id = b.id
        WHERE r.id = ? AND b.usuario_id = ?
    ");
    $check->execute([$id, $usuarioId]);
    $resultado = $check->fetch();

    echo "<!-- DEBUG: resultado encontrado = " . ($resultado ? 'SI' : 'NO') . " -->\n";
    if ($resultado) {
        echo "<!-- DEBUG: título = {$resultado['titulo']} -->\n";
        echo "<!-- DEBUG: búsqueda = {$resultado['busqueda']} -->\n";
    }

    if (!$resultado) {
        echo "<!-- DEBUG: No se encontró el resultado o no pertenece al usuario -->\n";
        $_SESSION['mensaje'] = "❌ No tienes permiso para eliminar este resultado (ID: $id, usuario: $usuarioId)";
        header('Location: index.php');
        exit;
    }

    // Eliminar el resultado
    $stmt = $pdo->prepare("DELETE FROM resultados WHERE id = ?");
    $result = $stmt->execute([$id]);

    echo "<!-- DEBUG: delete result = " . ($result ? 'true' : 'false') . " -->\n";
    echo "<!-- DEBUG: rows affected = " . $stmt->rowCount() . " -->\n";

    if ($result && $stmt->rowCount() > 0) {
        $_SESSION['mensaje'] = "✅ Resultado eliminado correctamente";
    } else {
        $_SESSION['mensaje'] = "❌ No se pudo eliminar el resultado";
    }
} catch (PDOException $e) {
    echo "<!-- DEBUG: excepción = " . $e->getMessage() . " -->\n";
    $_SESSION['mensaje'] = "❌ Error: " . $e->getMessage();
}

header('Location: index.php');
exit;
