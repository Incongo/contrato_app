<?php
// scripts/prueba_bopb.php
// Prueba del RSS del Butlletí Oficial de Barcelona

require_once __DIR__ . '/../Core/Database.php';
require_once __DIR__ . '/../Fuentes/BOPB_RSS.php';

echo "📡 PRUEBA BOPB RSS - Butlletí Oficial de Barcelona\n";
echo "=================================================\n\n";

$pdo = Database::getInstance();
$busquedaId = 1;

$fuente = new BOPB_RSS();
$fuente->probar();

// Ejecutar búsqueda (últimos 7 días)
echo "\n🔍 Ejecutando búsqueda...\n";
$resultados = $fuente->ejecutar($busquedaId, 30); // Últimos 30 días

echo "\n📊 RESULTADOS ENCONTRADOS: $resultados\n";

// Mostrar últimos resultados
$stmt = $pdo->prepare("
    SELECT * FROM resultados 
    WHERE fuente_id = (SELECT id FROM fuentes WHERE nombre_corto = 'bopb') 
    AND busqueda_id = ? 
    ORDER BY fecha_deteccion DESC 
    LIMIT 5
");
$stmt->execute([$busquedaId]);
$resultadosBD = $stmt->fetchAll();

if (count($resultadosBD) > 0) {
    echo "\n📋 ÚLTIMOS RESULTADOS BOPB:\n";
    foreach ($resultadosBD as $r) {
        echo "\n• " . mb_substr($r['titulo'], 0, 100) . "...\n";
        echo "  Organismo: {$r['organismo']}\n";
        echo "  Fecha publicación: {$r['fecha_publicacion']}\n";
        echo "  URL: {$r['url_detalle']}\n";
    }
} else {
    echo "\n📭 No hay resultados de BOPB en la base de datos todavía.\n";
}