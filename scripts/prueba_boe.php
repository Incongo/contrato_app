<?php
// scripts/prueba_boe.php

require_once __DIR__ . '/../Core/Database.php';
require_once __DIR__ . '/../Fuentes/BOE.php';

echo "📡 PRUEBA BOE\n";
echo "============\n\n";

$pdo = Database::getInstance();

// Usar la misma búsqueda (ID 1)
$busquedaId = 1;

echo "🔍 Usando búsqueda ID: $busquedaId\n";

// Ejecutar BOE
$fuente = new BOE();
$resultados = $fuente->ejecutar($busquedaId);

echo "\n📊 RESULTADOS ENCONTRADOS: $resultados\n";

// Mostrar últimos resultados
$stmt = $pdo->prepare(
    "SELECT * FROM resultados WHERE fuente_id = (SELECT id FROM fuentes WHERE nombre_corto = 'boe') AND busqueda_id = ? ORDER BY fecha_deteccion DESC LIMIT 5"
);
$stmt->execute([$busquedaId]);
$resultados = $stmt->fetchAll();

if (count($resultados) > 0) {
    echo "\n📋 ÚLTIMOS RESULTADOS BOE:\n";
    foreach ($resultados as $r) {
        echo "\n• " . mb_substr($r['titulo'], 0, 80) . "...\n";
        echo "  Organismo: {$r['organismo']}\n";
        echo "  URL: {$r['url_detalle']}\n";
    }
} else {
    echo "\n📭 No hay resultados de BOE todavía\n";
}
