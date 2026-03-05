<?php
// scripts/prueba_csic.php

require_once __DIR__ . '/../Core/Database.php';
require_once __DIR__ . '/../Fuentes/CSIC.php';

echo "📡 PRUEBA CSIC\n";
echo "=============\n\n";

$pdo = Database::getInstance();
$busquedaId = 2; // Usa la búsqueda de divulgación científica o servicios

$fuente = new CSIC();
$fuente->probar();

echo "\n🔍 Ejecutando búsqueda...\n";
$resultados = $fuente->ejecutar($busquedaId, 365); // Último año

echo "\n📊 TOTAL RESULTADOS: $resultados\n";

// Mostrar resultados guardados
$stmt = $pdo->prepare("
    SELECT * FROM resultados 
    WHERE fuente_id = (SELECT id FROM fuentes WHERE nombre_corto = 'csic') 
    AND busqueda_id = ? 
    ORDER BY fecha_deteccion DESC
");
$stmt->execute([$busquedaId]);
$resultadosBD = $stmt->fetchAll();

if (count($resultadosBD) > 0) {
    echo "\n📋 LICITACIONES DEL CSIC:\n";
    echo "=========================\n";
    
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
    }
} else {
    echo "\n📭 No hay resultados del CSIC todavía\n";
}