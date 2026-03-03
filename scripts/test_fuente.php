<?php
// scripts/test_fuente.php
require_once __DIR__ . '/../Fuentes/ContratacionEstado.php';

echo "🧪 Probando fuente...\n";
$fuente = new ContratacionEstado();
$fuente->probar();
echo "✅ Prueba completada\n";
