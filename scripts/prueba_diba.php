<?php
// scripts/prueba_diba.php
// Script de prueba para la API de la Diputació de Barcelona

require_once __DIR__ . '/../Core/Database.php';
require_once __DIR__ . '/../Fuentes/DIBAAPI.php';

echo "📡 PRUEBA DIBA API - Diputació de Barcelona\n";
echo "===========================================\n\n";

$pdo = Database::getInstance();
$busquedaId = 1;

$fuente = new DIBAAPI();
$fuente->probar();

// EJECUTAMOS LA BÚSQUEDA (sin filtro de fecha por ahora)
echo "\n🔍 Ejecutando búsqueda en la API...\n";
$resultadosGuardados = $fuente->ejecutar($busquedaId, null); // Pasamos null para no filtrar por fecha

echo "\n📊 RESULTADOS GUARDADOS COMO NUEVOS: $resultadosGuardados\n";

// --- AQUÍ VA EL CÓDIGO QUE ME PASaste PARA VER LOS PRIMEROS REGISTROS ---
// Para poder ejecutarlo, necesitamos acceder a los datos que devolvió la API dentro del método ejecutar.
// Esto es más complejo. De momento, te propongo una alternativa más sencilla:

echo "\n--- Para ver los primeros registros sin guardar, necesitamos modificar DIBAAPI.php ---\n";
echo "Por favor, dime si quieres que modifique DIBAAPI.php para que muestre esta información.\n";

// Mostrar últimos resultados guardados (si los hubo)
$stmt = $pdo->prepare("
    SELECT * FROM resultados 
    WHERE fuente_id = (SELECT id FROM fuentes WHERE nombre_corto = 'diba') 
    AND busqueda_id = ? 
    ORDER BY fecha_deteccion DESC 
    LIMIT 5
");
$stmt->execute([$busquedaId]);
$resultadosBD = $stmt->fetchAll();

if (count($resultadosBD) > 0) {
    echo "\n📋 ÚLTIMOS RESULTADOS DE DIBA EN LA BD:\n";
    foreach ($resultadosBD as $r) {
        echo "\n• " . mb_substr($r['titulo'], 0, 80) . "...\n";
        echo "  Organismo: {$r['organismo']}\n";
        echo "  Presupuesto: " . ($r['presupuesto'] ? number_format($r['presupuesto'], 2) . '€' : 'No especificado') . "\n";
        echo "  URL: {$r['url_detalle']}\n";
    }
} else {
    echo "\n📭 No hay resultados de DIBA en la base de datos todavía.\n";
}
