<?php
// scripts/prueba_rss.php

require_once __DIR__ . '/../Core/Database.php';
require_once __DIR__ . '/../Fuentes/ContratacionEstado.php';

echo "📡 PRUEBA RSS - CONTRATACIÓN ESTADO\n";
echo "====================================\n\n";

$pdo = Database::getInstance();

// Usar usuario y búsqueda existentes
$busquedaId = 1; // La que creamos antes

echo "🔍 Usando búsqueda ID: $busquedaId\n";

// Ejecutar búsqueda RSS
$fuente = new ContratacionEstado();
$resultados = $fuente->ejecutar($busquedaId);

echo "\n📊 RESULTADOS ENCONTRADOS: $resultados\n";

// Mostrar últimos resultados
$stmt = $pdo->prepare(
    "SELECT * FROM resultados WHERE busqueda_id = ? ORDER BY fecha_deteccion DESC LIMIT 5"
);
$stmt->execute([$busquedaId]);
$resultados = $stmt->fetchAll();

if (count($resultados) > 0) {
    echo "\n📋 ÚLTIMOS RESULTADOS:\n";
    foreach ($resultados as $r) {
        echo "\n• " . mb_substr($r['titulo'], 0, 80) . "...\n";
        echo "  Organismo: {$r['organismo']}\n";
        echo "  URL: {$r['url_detalle']}\n";
    }
} else {
    echo "\n📭 No hay resultados todavía\n";
}