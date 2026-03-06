<?php
// public/estadisticas_estados.php
// Devuelve JSON con los contadores por estado

require_once __DIR__ . '/../Core/Database.php';
require_once __DIR__ . '/../Core/Auth.php';
require_once __DIR__ . '/../Core/AuthMiddleware.php';

session_start();
AuthMiddleware::protegerPagina();

$pdo = Database::getInstance();

$contadores = [
    'pendientes' => $pdo->query("SELECT COUNT(*) FROM resultados WHERE estado_usuario = 0")->fetchColumn(),
    'interesantes' => $pdo->query("SELECT COUNT(*) FROM resultados WHERE estado_usuario = 1")->fetchColumn(),
    'descartados' => $pdo->query("SELECT COUNT(*) FROM resultados WHERE estado_usuario = 2")->fetchColumn()
];

header('Content-Type: application/json');
echo json_encode($contadores);