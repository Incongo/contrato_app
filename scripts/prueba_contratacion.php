<?php
// scripts/prueba_contratacion.php

require_once __DIR__ . '/../app/Core/Database.php';
require_once __DIR__ . '/../app/Core/Auth.php';
require_once __DIR__ . '/../app/Fuentes/ContratacionEstado.php';

echo "🚀 INICIANDO PRUEBA DE CONTRATACIÓN\n";
echo "==================================\n\n";

// 1. Crear usuario de prueba si no existe
$auth = new Auth();
$usuarioId = null;

// Intentar login con usuario de prueba
$login = $auth->login('test@contratos.com', 'password123');
if ($login['success']) {
    $usuarioId = $login['user']['id'];
    echo "✅ Usuario existente: {$login['user']['nombre']}\n";
} else {
    // Registrar nuevo usuario
    $registro = $auth->register('Usuario Prueba', 'test@contratos.com', 'password123');
    if ($registro['success']) {
        $usuarioId = $registro['id'];
        echo "✅ Usuario creado: ID $usuarioId\n";
    } else {
        die("❌ Error creando usuario: " . $registro['error']);
    }
}

// 2. Crear búsqueda de prueba
$pdo = Database::getInstance();

// Palabras clave de ejemplo (las que tú uses)
$palabrasClave = 'divulgación, astronomía, cambio climático, vídeo, streaming, documental, grabación, cobertura';

// Verificar si ya existe una búsqueda para este usuario
$stmt = $pdo->prepare("SELECT id FROM busquedas WHERE usuario_id = ? AND nombre = 'Prueba divulgación científica'");
$stmt->execute([$usuarioId]);
$busquedaExistente = $stmt->fetch();

if ($busquedaExistente) {
    $busquedaId = $busquedaExistente['id'];
    echo "✅ Búsqueda existente: ID $busquedaId\n";
} else {
    $stmt = $pdo->prepare(
        "INSERT INTO busquedas (usuario_id, nombre, palabras_clave) VALUES (?, ?, ?)"
    );
    $stmt->execute([$usuarioId, 'Prueba divulgación científica', $palabrasClave]);
    $busquedaId = $pdo->lastInsertId();
    echo "✅ Búsqueda creada: ID $busquedaId\n";
}

echo "   📌 Palabras clave: $palabrasClave\n\n";

// 3. Ejecutar la fuente
echo "📡 Ejecutando ContratacionEstado...\n";
$fuente = new ContratacionEstado();
$resultado = $fuente->ejecutar($busquedaId);

echo "\n📊 RESULTADO:\n";
print_r($resultado);

echo "\n✅ Prueba completada\n";
