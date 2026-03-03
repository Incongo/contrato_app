<?php
// scripts/prueba_doge.php
require_once __DIR__ . '/../Core/Database.php';
require_once __DIR__ . '/../Fuentes/DOGE.php';

echo "📡 PRUEBA DOGE - CANALES RSS OFICIALES\n";
echo "======================================\n\n";

$pdo = Database::getInstance();
$busquedaId = 1; // Usamos la búsqueda que ya tenemos

$fuente = new DOGE();
$fuente->probar(); // Verificar configuración
$resultados = $fuente->ejecutar($busquedaId);

echo "\n📊 TOTAL RESULTADOS: $resultados\n";

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
}