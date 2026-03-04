<?php
// public/perfil.php
require_once __DIR__ . '/../Core/Database.php';
require_once __DIR__ . '/../Core/Auth.php';
require_once __DIR__ . '/../Core/AuthMiddleware.php';

session_start();
AuthMiddleware::protegerPagina();

$auth = new Auth();
$pdo = Database::getInstance();
$usuario = $auth->user();

$mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Aquí procesaremos cambios de perfil (nombre, email, contraseña)
    $mensaje = "Funcionalidad en desarrollo";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>✏️ Mi Perfil</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <a href="index.php">← Volver</a>
        </div>
    </nav>
    
    <div class="container" style="max-width: 600px;">
        <h1>✏️ Mi Perfil</h1>
        
        <?php if ($mensaje): ?>
            <div class="aviso"><?= $mensaje ?></div>
        <?php endif; ?>
        
        <div class="stat-card">
            <p><strong>Nombre:</strong> <?= htmlspecialchars($usuario['nombre']) ?></p>
            <p><strong>Email:</strong> <?= htmlspecialchars($usuario['email']) ?></p>
            <p><strong>Registrado:</strong> <?= date('d/m/Y', strtotime($usuario['created_at'])) ?></p>
            <p><strong>Último acceso:</strong> <?= $usuario['last_login'] ? date('d/m/Y H:i', strtotime($usuario['last_login'])) : 'Nunca' ?></p>
        </div>
    </div>
</body>
</html>