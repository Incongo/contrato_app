<?php
// scripts/importar_bdns.php
// Importa el JSON de BDNS a la base de datos

require_once __DIR__ . '/../Core/Database.php';

echo "📦 IMPORTANDO BDNS A MONGODB\n";
echo "============================\n\n";

$pdo = Database::getInstance();

// 1. Obtener el ID de la fuente BDNS
$stmt = $pdo->prepare("SELECT id FROM fuentes WHERE nombre_corto = 'bdns'");
$stmt->execute();
$fuente = $stmt->fetch();

if (!$fuente) {
    die("❌ Fuente BDNS no encontrada. Ejecuta primero el script de prueba.\n");
}
$fuenteId = $fuente['id'];

// 2. Buscar el archivo JSON más reciente en storage/temp
$tempPath = __DIR__ . '/../storage/temp';
$archivos = scandir($tempPath);
$jsonFiles = array_filter($archivos, function($f) {
    return pathinfo($f, PATHINFO_EXTENSION) === 'json' && strpos($f, 'listado') !== false;
});

if (empty($jsonFiles)) {
    die("❌ No hay archivos JSON en storage/temp\n");
}

// Ordenar por fecha de modificación (más reciente primero)
$archivosConFecha = [];
foreach ($jsonFiles as $file) {
    $archivosConFecha[$file] = filemtime($tempPath . '/' . $file);
}
arsort($archivosConFecha);
$archivo = key($archivosConFecha);
$ruta = $tempPath . '/' . $archivo;

echo "📄 Procesando: $archivo\n";

// 3. Leer el JSON
$contenido = file_get_contents($ruta);
$datos = json_decode($contenido, true);

if (!is_array($datos)) {
    die("❌ El archivo no contiene un JSON válido\n");
}

echo "📊 Registros encontrados: " . count($datos) . "\n";

// 4. Obtener las búsquedas activas del usuario
$usuarioId = 1;
$stmt = $pdo->prepare("SELECT * FROM busquedas WHERE usuario_id = ? AND activo = 1");
$stmt->execute([$usuarioId]);
$busquedas = $stmt->fetchAll();

if (empty($busquedas)) {
    echo "⚠️ No hay búsquedas activas. Se importarán sin asociar a búsquedas.\n";
}

// 5. Procesar cada registro
$importados = 0;
$duplicados = 0;

foreach ($datos as $item) {
    // Construir URL del detalle
    $url = "https://www.infosubvenciones.es/bdnstrans/GE/es/convocatorias/{$item['id']}";
    
    // Construir organismo completo
    $organismo = trim(implode(' - ', array_filter([
        $item['nivel1'] ?? '',
        $item['nivel2'] ?? '',
        $item['nivel3'] ?? ''
    ])));
    
    if (empty($organismo)) {
        $organismo = 'No especificado';
    }
    
    // Extraer palabras clave del título
    $texto = $item['descripcion'] ?? '';
    $palabrasEncontradas = [];
    
    // Para cada búsqueda activa, verificar si coincide
    foreach ($busquedas as $busqueda) {
        $palabrasBusqueda = array_map('trim', explode(',', $busqueda['palabras_clave']));
        
        foreach ($palabrasBusqueda as $palabra) {
            if (stripos($texto, $palabra) !== false) {
                $palabrasEncontradas[] = $palabra;
            }
        }
        
        if (!empty($palabrasEncontradas)) {
            // Verificar si ya existe para esta búsqueda
            $stmt = $pdo->prepare("SELECT id FROM resultados WHERE url_detalle = ? AND busqueda_id = ?");
            $stmt->execute([$url, $busqueda['id']]);
            
            if (!$stmt->fetch()) {
                // Insertar nuevo resultado
                $stmt = $pdo->prepare("
                    INSERT INTO resultados (
                        busqueda_id, fuente_id, titulo, descripcion_corta, organismo,
                        fecha_publicacion, url_detalle, palabras_coincidentes, relevancia
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                
                $stmt->execute([
                    $busqueda['id'],
                    $fuenteId,
                    $item['descripcion'] ?? 'Sin título',
                    $item['descripcion'] ?? '',
                    $organismo,
                    $item['fechaRecepcion'] ?? null,
                    $url,
                    json_encode(array_unique($palabrasEncontradas), JSON_UNESCAPED_UNICODE),
                    count(array_unique($palabrasEncontradas))
                ]);
                
                $importados++;
                echo "✅ Nuevo: {$item['descripcion']}\n";
            } else {
                $duplicados++;
            }
            
            // Salir del bucle de búsquedas si ya encontramos coincidencia
            break;
        }
    }
}

echo "\n📊 RESUMEN:\n";
echo "   Total registros en JSON: " . count($datos) . "\n";
echo "   Importados: $importados\n";
echo "   Duplicados: $duplicados\n";
echo "   Ignorados: " . (count($datos) - $importados - $duplicados) . "\n";

echo "\n✅ Proceso completado\n";