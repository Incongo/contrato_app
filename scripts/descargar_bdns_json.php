<?php
// scripts/descargar_bdns_json.php
// Ejecuta el script de Puppeteer para descargar JSON de BDNS

echo "📥 DESCARGANDO JSON DE BDNS\n";
echo "==========================\n\n";

// Ruta al script de Node.js
$scriptJs = __DIR__ . '/bdns_puppeteer.js';

if (!file_exists($scriptJs)) {
    die("❌ No existe el script: $scriptJs\n");
}

echo "📡 Ejecutando Puppeteer...\n";
$comando = "node \"$scriptJs\" 2>&1";
exec($comando, $output, $return);

// Mostrar la salida del script
echo implode("\n", $output) . "\n";

if ($return === 0) {
    echo "\n✅ Script ejecutado correctamente\n";
    
    // Buscar archivos JSON descargados en storage/temp
    $tempPath = __DIR__ . '/../storage/temp';
    if (!is_dir($tempPath)) {
        mkdir($tempPath, 0777, true);
    }
    
    $archivos = scandir($tempPath);
    $jsonFiles = array_filter($archivos, function($f) {
        return strpos($f, '.json') !== false;
    });
    
    if (!empty($jsonFiles)) {
        // Mostrar el más reciente
        $archivosConFecha = [];
        foreach ($jsonFiles as $file) {
            $archivosConFecha[$file] = filemtime($tempPath . '/' . $file);
        }
        arsort($archivosConFecha);
        $ultimo = key($archivosConFecha);
        $ruta = $tempPath . '/' . $ultimo;
        $tamaño = filesize($ruta);
        
        echo "\n📦 Último archivo descargado:\n";
        echo "   Nombre: $ultimo\n";
        echo "   Tamaño: " . round($tamaño / 1024 / 1024, 2) . " MB\n";
        
        // Mostrar primeras líneas
        $contenido = file_get_contents($ruta);
        $primeros = substr($contenido, 0, 500);
        echo "\n📋 Primeros 500 caracteres:\n$primeros\n";
    } else {
        echo "\n⚠️ No se encontraron archivos JSON en storage/temp\n";
    }
} else {
    echo "\n❌ Error al ejecutar Puppeteer (código $return)\n";
}

echo "\n✅ Proceso completado\n";