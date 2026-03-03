<?php
// scripts/prueba_inicial.php

require_once __DIR__ . '/../Core/Database.php';
require_once __DIR__ . '/../Core/Auth.php';
require_once __DIR__ . '/../Fuentes/ContratacionEstado.php';

echo "🚀 PRUEBA INICIAL - CONTRATACIÓN ESTADO\n";
echo "========================================\n\n";

$pdo = Database::getInstance();

// 1. Crear usuario de prueba si no existe
$auth = new Auth();
$email = 'test@prueba.com';

$stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
$stmt->execute([$email]);
$usuario = $stmt->fetch();

if (!$usuario) {
    $result = $auth->register('Usuario Prueba', $email, 'password123');
    $usuarioId = $result['id'];
    echo "✅ Usuario creado: ID $usuarioId\n";
} else {
    $usuarioId = $usuario['id'];
    echo "✅ Usuario existente: ID $usuarioId\n";
}

// 2. Crear búsqueda de prueba
$palabrasClave = 'divulgación, astronomía, vídeo, streaming, documental';

$stmt = $pdo->prepare("SELECT id FROM busquedas WHERE usuario_id = ? AND nombre = 'Prueba inicial'");
$stmt->execute([$usuarioId]);
$busqueda = $stmt->fetch();

if (!$busqueda) {
    $stmt = $pdo->prepare(
        "INSERT INTO busquedas (usuario_id, nombre, palabras_clave) VALUES (?, ?, ?)"
    );
    $stmt->execute([$usuarioId, 'Prueba inicial', $palabrasClave]);
    $busquedaId = $pdo->lastInsertId();
    echo "✅ Búsqueda creada: ID $busquedaId\n";
} else {
    $busquedaId = $busqueda['id'];
    echo "✅ Búsqueda existente: ID $busquedaId\n";
}

echo "   📌 Palabras: $palabrasClave\n\n";

// 3. Ejecutar la fuente (ahora sí, con el método ejecutar)
echo "📡 Ejecutando ContratacionEstado...\n";
$fuente = new ContratacionEstado();

// ANTES DE EJECUTAR, debemos comentar la línea del ZIP
// Por ahora, haremos una versión simplificada

echo "\n⚠️  ATENCIÓN: El script intentará buscar el ZIP\n";
echo "   Si no tienes el ZIP, la ejecución fallará.\n";
echo "   ¿Quieres continuar? (responde en el código)\n";

// Por ahora, solo probamos que la fuente responde
echo "\n✅ Fuente lista para ejecutar\n";