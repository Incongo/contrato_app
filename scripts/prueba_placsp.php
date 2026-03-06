<?php
// scripts/prueba_placsp.php

require_once __DIR__ . '/../Core/Database.php';
require_once __DIR__ . '/../Fuentes/PLACSP.php';

echo "📡 PRUEBA PLACSP (Feed ATOM Corregido)\n";
echo "======================================\n\n";

$pdo = Database::getInstance();
$busquedaId = 1; // Ajusta según tu búsqueda

$fuente = new PLACSP();
$fuente->probar();

echo "\n🔍 Ejecutando búsqueda (últimos 30 días)...\n";
$resultados = $fuente->ejecutar($busquedaId, 30);

echo "\n📊 TOTAL RESULTADOS: $resultados\n";

// Mostrar resultados guardados
$stmt = $pdo->prepare("
    SELECT * FROM resultados 
    WHERE fuente_id = (SELECT id FROM fuentes WHERE nombre_corto = 'placsp') 
    AND busqueda_id = ? 
    ORDER BY fecha_deteccion DESC
    LIMIT 10
");
$stmt->execute([$busquedaId]);
$resultadosBD = $stmt->fetchAll();

if (count($resultadosBD) > 0) {
    echo "\n📋 ÚLTIMAS LICITACIONES:\n";
    echo "========================\n";
    
    foreach ($resultadosBD as $r) {
        echo "\n• " . $r['titulo'] . "\n";
        echo "  🔗 URL: {$r['url_detalle']}\n";
        echo "  📅 Publicación: {$r['fecha_publicacion']}\n";
    }
} else {
    echo "\n📭 No hay resultados todavía\n";
}