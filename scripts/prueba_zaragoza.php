<?php
// scripts/prueba_zaragoza.php

require_once __DIR__ . '/../Core/Database.php';
require_once __DIR__ . '/../Fuentes/ZaragozaAPI.php';

echo "📡 PRUEBA ZARAGOZA API\n";
echo "=====================\n\n";

$pdo = Database::getInstance();
$busquedaId = 1;

$fuente = new ZaragozaAPI();
$fuente->probar();

// Ejecutar búsqueda general
echo "\n🔍 Búsqueda general (últimos 90 días)...\n";
$resultados = $fuente->ejecutar($busquedaId, 90);

echo "\n📊 TOTAL RESULTADOS: $resultados\n";

// Mostrar los resultados guardados
$stmt = $pdo->prepare("
    SELECT * FROM resultados 
    WHERE fuente_id = (SELECT id FROM fuentes WHERE nombre_corto = 'zaragoza') 
    AND busqueda_id = ? 
    ORDER BY presupuesto DESC
");
$stmt->execute([$busquedaId]);
$resultadosBD = $stmt->fetchAll();

if (count($resultadosBD) > 0) {
    echo "\n📋 LICITACIONES DE ZARAGOZA (por presupuesto):\n";
    echo "================================================\n";

    foreach ($resultadosBD as $r) {
        echo "\n• " . $r['titulo'] . "\n";
        echo "  Organismo: {$r['organismo']}\n";
        if ($r['presupuesto']) {
            echo "  💰 " . number_format($r['presupuesto'], 2) . " €\n";
        }
        if ($r['fecha_limite']) {
            echo "  ⏳ Límite: {$r['fecha_limite']}\n";
        }
        echo "  🔗 URL: {$r['url_detalle']}\n";

        // Destacar Distrito 7
        if (stripos($r['titulo'], 'DISTRITO 7') !== false) {
            echo "  ⭐⭐⭐ LICITACIÓN ESTRATÉGICA ⭐⭐⭐\n";
        }
    }
} else {
    echo "\n📭 No hay resultados de Zaragoza todavía\n";
}
