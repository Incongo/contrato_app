<?php
// scripts/prueba_placsp.php
// Versión con búsqueda específica para Luzlux

require_once __DIR__ . '/../Core/Database.php';
require_once __DIR__ . '/../Fuentes/PLACSP.php';

echo "📡 PRUEBA PLACSP - Búsqueda Luzlux\n";
echo "==================================\n\n";

$pdo = Database::getInstance();

// Obtener el ID de la nueva búsqueda (cámbialo si es necesario)
$stmt = $pdo->prepare("SELECT id FROM busquedas WHERE nombre LIKE '%Luzlux%'");
$stmt->execute();
$busqueda = $stmt->fetch();

if (!$busqueda) {
    die("❌ No se encontró la búsqueda Luzlux. Ejecuta primero el SQL.\n");
}

$busquedaId = $busqueda['id'];
echo "🔍 Usando búsqueda ID: $busquedaId\n";

$fuente = new PLACSP();
$fuente->probar();

echo "\n🔍 Ejecutando búsqueda (últimos 90 días)...\n";
$resultados = $fuente->ejecutar($busquedaId, 90);

echo "\n📊 TOTAL RESULTADOS: $resultados\n";

// Mostrar resultados guardados
$stmt = $pdo->prepare("
    SELECT r.*, b.nombre as busqueda_nombre 
    FROM resultados r
    JOIN busquedas b ON r.busqueda_id = b.id
    WHERE r.fuente_id = (SELECT id FROM fuentes WHERE nombre_corto = 'placsp') 
    AND r.busqueda_id = ? 
    ORDER BY r.fecha_deteccion DESC
    LIMIT 20
");
$stmt->execute([$busquedaId]);
$resultadosBD = $stmt->fetchAll();

if (count($resultadosBD) > 0) {
    echo "\n📋 LICITACIONES ENCONTRADAS:\n";
    echo "============================\n";

    foreach ($resultadosBD as $r) {
        echo "\n• " . $r['titulo'] . "\n";
        echo "  🏛️ Organismo: {$r['organismo']}\n";
        if ($r['presupuesto']) {
            echo "  💰 " . number_format($r['presupuesto'], 2) . " €\n";
        }
        echo "  🔗 URL: {$r['url_detalle']}\n";
        echo "  🔑 Keywords: " . implode(', ', json_decode($r['palabras_coincidentes'] ?? '[]')) . "\n";
    }
} else {
    echo "\n📭 No hay resultados todavía\n";
}
