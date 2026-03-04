<?php
// public/busquedas.php
// Panel de gestión de búsquedas y palabras clave

require_once __DIR__ . '/../Core/Database.php';
require_once __DIR__ . '/../Core/Auth.php';
require_once __DIR__ . '/../Core/AuthMiddleware.php';

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

AuthMiddleware::protegerPagina();

// Verificar autenticación (opcional por ahora)
$auth = new Auth();
$usuario = $auth->user();
$usuarioId = $usuario ? $usuario['id'] : 1; // Por defecto usuario 1

$pdo = Database::getInstance();

// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Crear nueva búsqueda
    if (isset($_POST['crear_busqueda'])) {
        $nombre = $_POST['nombre'] ?? 'Nueva búsqueda';
        $palabras = $_POST['palabras_clave'] ?? '';

        $stmt = $pdo->prepare("INSERT INTO busquedas (usuario_id, nombre, palabras_clave) VALUES (?, ?, ?)");
        $stmt->execute([$usuarioId, $nombre, $palabras]);
        $nuevoId = $pdo->lastInsertId();
        $mensaje = "✅ Búsqueda creada (ID: $nuevoId)";
    }

    // Actualizar búsqueda
    if (isset($_POST['actualizar_busqueda'])) {
        $id = $_POST['busqueda_id'];
        $nombre = $_POST['nombre'];
        $palabras = $_POST['palabras_clave'];
        $activo = isset($_POST['activo']) ? 1 : 0;

        $stmt = $pdo->prepare("UPDATE busquedas SET nombre = ?, palabras_clave = ?, activo = ? WHERE id = ? AND usuario_id = ?");
        $stmt->execute([$nombre, $palabras, $activo, $id, $usuarioId]);
        $mensaje = "✅ Búsqueda actualizada";
    }

    // Eliminar búsqueda
    if (isset($_POST['eliminar_busqueda'])) {
        $id = $_POST['busqueda_id'];
        $stmt = $pdo->prepare("DELETE FROM busquedas WHERE id = ? AND usuario_id = ?");
        $stmt->execute([$id, $usuarioId]);
        $mensaje = "✅ Búsqueda eliminada";
    }

    // Añadir palabra a búsqueda
    if (isset($_POST['anadir_palabra'])) {
        $id = $_POST['busqueda_id'];
        $nuevaPalabra = trim($_POST['nueva_palabra']);

        if ($nuevaPalabra) {
            $stmt = $pdo->prepare("SELECT palabras_clave FROM busquedas WHERE id = ? AND usuario_id = ?");
            $stmt->execute([$id, $usuarioId]);
            $busqueda = $stmt->fetch();

            if ($busqueda) {
                $palabras = array_map('trim', explode(',', $busqueda['palabras_clave']));
                if (!in_array($nuevaPalabra, $palabras)) {
                    $palabras[] = $nuevaPalabra;
                    $nuevasPalabras = implode(', ', $palabras);

                    $upd = $pdo->prepare("UPDATE busquedas SET palabras_clave = ? WHERE id = ?");
                    $upd->execute([$nuevasPalabras, $id]);
                    $mensaje = "✅ Palabra añadida: $nuevaPalabra";
                } else {
                    $mensaje = "⚠️ La palabra ya existe";
                }
            }
        }
    }

    // Quitar palabra de búsqueda
    if (isset($_POST['quitar_palabra'])) {
        $id = $_POST['busqueda_id'];
        $palabraQuitar = $_POST['palabra'];

        $stmt = $pdo->prepare("SELECT palabras_clave FROM busquedas WHERE id = ? AND usuario_id = ?");
        $stmt->execute([$id, $usuarioId]);
        $busqueda = $stmt->fetch();

        if ($busqueda) {
            $palabras = array_map('trim', explode(',', $busqueda['palabras_clave']));
            $palabras = array_filter($palabras, function ($p) use ($palabraQuitar) {
                return $p !== $palabraQuitar;
            });
            $nuevasPalabras = implode(', ', $palabras);

            $upd = $pdo->prepare("UPDATE busquedas SET palabras_clave = ? WHERE id = ?");
            $upd->execute([$nuevasPalabras, $id]);
            $mensaje = "✅ Palabra quitada: $palabraQuitar";
        }
    }
}

// Obtener todas las búsquedas del usuario
$stmt = $pdo->prepare("SELECT * FROM busquedas WHERE usuario_id = ? ORDER BY activo DESC, id DESC");
$stmt->execute([$usuarioId]);
$busquedas = $stmt->fetchAll();

// Obtener palabras clave predefinidas (opcional)
$palabrasSugeridas = [
    'ciencia' => ['divulgación científica', 'investigación', 'astronomía', 'cambio climático', 'medio ambiente', 'biodiversidad', 'tecnología'],
    'audiovisual' => ['producción audiovisual', 'vídeo divulgativo', 'streaming', 'documental', 'cobertura de eventos', 'grabación', 'contenidos multimedia']
];
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🎬 Gestionar Búsquedas</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            background: #f3f4f6;
            color: #1f2937;
            line-height: 1.5;
        }

        .navbar {
            background: white;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            padding: 1rem 2rem;
            margin-bottom: 2rem;
        }

        .navbar h1 {
            font-size: 1.5rem;
            color: #4f46e5;
        }

        .navbar .nav-links {
            display: flex;
            gap: 1rem;
            margin-top: 0.5rem;
        }

        .navbar .nav-links a {
            color: #6b7280;
            text-decoration: none;
        }

        .navbar .nav-links a:hover {
            color: #4f46e5;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
        }

        .mensaje {
            background: #d1fae5;
            border-left: 4px solid #10b981;
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 2rem;
        }

        .grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
        }

        .card {
            background: white;
            border-radius: 0.75rem;
            padding: 1.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }

        .card h2 {
            font-size: 1.25rem;
            margin-bottom: 1.5rem;
            color: #1f2937;
        }

        .card h3 {
            font-size: 1rem;
            margin-bottom: 1rem;
            color: #4b5563;
        }

        .busqueda-item {
            border: 1px solid #e5e7eb;
            border-radius: 0.5rem;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            background: #f9fafb;
        }

        .busqueda-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .busqueda-nombre {
            font-weight: 600;
            font-size: 1.1rem;
        }

        .busqueda-id {
            color: #6b7280;
            font-size: 0.85rem;
        }

        .badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 1rem;
            font-size: 0.75rem;
            font-weight: 600;
            background: #e5e7eb;
            color: #374151;
        }

        .badge.activo {
            background: #d1fae5;
            color: #065f46;
        }

        .badge.inactivo {
            background: #fee2e2;
            color: #b91c1c;
        }

        .palabras-list {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin: 1rem 0;
        }

        .palabra-item {
            background: #e0e7ff;
            color: #3730a3;
            padding: 0.25rem 0.75rem;
            border-radius: 1rem;
            font-size: 0.85rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .palabra-item button {
            background: none;
            border: none;
            color: #6b7280;
            cursor: pointer;
            font-size: 1rem;
            padding: 0 0.25rem;
        }

        .palabra-item button:hover {
            color: #b91c1c;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-group label {
            display: block;
            font-size: 0.9rem;
            color: #4b5563;
            margin-bottom: 0.25rem;
        }

        .form-group input[type="text"],
        .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #e5e7eb;
            border-radius: 0.5rem;
            font-family: inherit;
            font-size: 0.9rem;
        }

        .form-group textarea {
            min-height: 100px;
            font-family: monospace;
        }

        .form-group.checkbox {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .form-group.checkbox label {
            margin-bottom: 0;
        }

        .btn-group {
            display: flex;
            gap: 0.5rem;
            margin-top: 1rem;
            flex-wrap: wrap;
        }

        .btn {
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            font-size: 0.9rem;
            border: none;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }

        .btn-primary {
            background: #4f46e5;
            color: white;
        }

        .btn-primary:hover {
            background: #4338ca;
        }

        .btn-secondary {
            background: #6b7280;
            color: white;
        }

        .btn-secondary:hover {
            background: #4b5563;
        }

        .btn-danger {
            background: #ef4444;
            color: white;
        }

        .btn-danger:hover {
            background: #dc2626;
        }

        .btn-small {
            padding: 0.25rem 0.75rem;
            font-size: 0.8rem;
        }

        .sugerencias {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin: 1rem 0;
        }

        .sugerencia-item {
            background: #f3f4f6;
            padding: 0.25rem 0.75rem;
            border-radius: 1rem;
            font-size: 0.85rem;
            cursor: pointer;
            border: 1px solid #e5e7eb;
        }

        .sugerencia-item:hover {
            background: #e5e7eb;
        }

        .sugerencia-item.ciencia {
            border-left: 3px solid #3b82f6;
        }

        .sugerencia-item.audiovisual {
            border-left: 3px solid #ec4899;
        }
    </style>
</head>

<body>
    <nav class="navbar">
        <div class="container">
            <h1>🎬 Gestionar Búsquedas y Palabras Clave</h1>
            <div class="nav-links">
                <a href="index.php">← Volver a resultados</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <?php if (isset($mensaje)): ?>
            <div class="mensaje"><?= $mensaje ?></div>
        <?php endif; ?>

        <div class="grid">
            <!-- Columna izquierda: Crear nueva búsqueda + sugerencias -->
            <div>
                <div class="card">
                    <h2>➕ Crear nueva búsqueda</h2>
                    <form method="POST">
                        <div class="form-group">
                            <label>Nombre de la búsqueda</label>
                            <input type="text" name="nombre" placeholder="Ej: Divulgación científica 2026" required>
                        </div>
                        <div class="form-group">
                            <label>Palabras clave (separadas por comas)</label>
                            <textarea name="palabras_clave" placeholder="producción audiovisual, vídeo divulgativo, streaming..."></textarea>
                        </div>
                        <button type="submit" name="crear_busqueda" class="btn btn-primary">Crear búsqueda</button>
                    </form>
                </div>

                <div class="card">
                    <h2>💡 Palabras clave sugeridas</h2>

                    <h3>🔬 Ciencia</h3>
                    <div class="sugerencias">
                        <?php foreach ($palabrasSugeridas['ciencia'] as $palabra): ?>
                            <span class="sugerencia-item ciencia" onclick="anadirSugerencia('<?= $palabra ?>')"><?= $palabra ?></span>
                        <?php endforeach; ?>
                    </div>

                    <h3>🎥 Audiovisual</h3>
                    <div class="sugerencias">
                        <?php foreach ($palabrasSugeridas['audiovisual'] as $palabra): ?>
                            <span class="sugerencia-item audiovisual" onclick="anadirSugerencia('<?= $palabra ?>')"><?= $palabra ?></span>
                        <?php endforeach; ?>
                    </div>

                    <p style="font-size: 0.85rem; color: #6b7280; margin-top: 1rem;">
                        Haz clic en una palabra para copiarla. Luego pégala en el campo de texto.
                    </p>
                </div>
            </div>

            <!-- Columna derecha: Búsquedas existentes -->
            <div>
                <div class="card">
                    <h2>📋 Mis búsquedas</h2>

                    <?php if (empty($busquedas)): ?>
                        <p style="color: #6b7280;">No hay búsquedas creadas. Crea una nueva.</p>
                    <?php endif; ?>

                    <?php foreach ($busquedas as $b): ?>
                        <div class="busqueda-item">
                            <div class="busqueda-header">
                                <div>
                                    <span class="busqueda-nombre"><?= htmlspecialchars($b['nombre']) ?></span>
                                    <span class="busqueda-id">(ID: <?= $b['id'] ?>)</span>
                                </div>
                                <span class="badge <?= $b['activo'] ? 'activo' : 'inactivo' ?>">
                                    <?= $b['activo'] ? 'Activa' : 'Inactiva' ?>
                                </span>
                            </div>

                            <!-- Palabras clave actuales -->
                            <?php
                            $palabras = array_map('trim', explode(',', $b['palabras_clave']));
                            ?>
                            <div class="palabras-list">
                                <?php foreach ($palabras as $p): ?>
                                    <?php if ($p): ?>
                                        <span class="palabra-item">
                                            <?= htmlspecialchars($p) ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="busqueda_id" value="<?= $b['id'] ?>">
                                                <input type="hidden" name="palabra" value="<?= htmlspecialchars($p) ?>">
                                                <button type="submit" name="quitar_palabra" title="Quitar palabra">✕</button>
                                            </form>
                                        </span>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>

                            <!-- Formulario para añadir palabra -->
                            <form method="POST" style="margin: 1rem 0;">
                                <input type="hidden" name="busqueda_id" value="<?= $b['id'] ?>">
                                <div style="display: flex; gap: 0.5rem;">
                                    <input type="text" name="nueva_palabra" placeholder="Nueva palabra clave..." style="flex: 1; padding: 0.5rem;">
                                    <button type="submit" name="anadir_palabra" class="btn btn-small btn-primary">Añadir</button>
                                </div>
                            </form>

                            <!-- Formulario de edición completa -->
                            <details>
                                <summary style="cursor: pointer; color: #4f46e5; margin: 1rem 0;">Editar búsqueda completa</summary>
                                <form method="POST" style="margin-top: 1rem;">
                                    <input type="hidden" name="busqueda_id" value="<?= $b['id'] ?>">

                                    <div class="form-group">
                                        <label>Nombre</label>
                                        <input type="text" name="nombre" value="<?= htmlspecialchars($b['nombre']) ?>" required>
                                    </div>

                                    <div class="form-group">
                                        <label>Palabras clave (separadas por comas)</label>
                                        <textarea name="palabras_clave" rows="4"><?= htmlspecialchars($b['palabras_clave']) ?></textarea>
                                    </div>

                                    <div class="form-group checkbox">
                                        <input type="checkbox" name="activo" id="activo_<?= $b['id'] ?>" <?= $b['activo'] ? 'checked' : '' ?>>
                                        <label for="activo_<?= $b['id'] ?>">Búsqueda activa</label>
                                    </div>

                                    <div class="btn-group">
                                        <button type="submit" name="actualizar_busqueda" class="btn btn-small btn-primary">Actualizar</button>
                                        <button type="submit" name="eliminar_busqueda" class="btn btn-small btn-danger" onclick="return confirm('¿Eliminar esta búsqueda?')">Eliminar</button>
                                    </div>
                                </form>
                            </details>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        function anadirSugerencia(palabra) {
            // Buscar el primer textarea visible y añadir la palabra
            const textareas = document.querySelectorAll('textarea[name="palabras_clave"]');
            if (textareas.length > 0) {
                const ta = textareas[0];
                if (ta.value) {
                    ta.value += ', ' + palabra;
                } else {
                    ta.value = palabra;
                }
            } else {
                alert('Haz clic en el campo de texto primero');
            }
        }
    </script>
</body>

</html>