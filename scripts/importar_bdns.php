<?php
// scripts/importar_bdns.php
// Importa el JSON de BDNS a la base de datos
// VERSIÓN CORREGIDA: URLs con código BDNS + actualización de existentes

require_once __DIR__ . '/../Core/Database.php';

echo "📦 IMPORTANDO BDNS A LA BASE DE DATOS\n";
echo "=====================================\n\n";

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
if (!is_dir($tempPath)) {
    mkdir($tempPath, 0777, true);
}

$archivos = scandir($tempPath);
$jsonFiles = array_filter($archivos, function ($f) {
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
$actualizados = 0;

foreach ($datos as $item) {
    // Guardar el código BDNS (número de convocatoria)
    $codigo_bdns = $item['numeroConvocatoria'] ?? null;

    // Construir URL con el código BDNS (el formato que funciona)
    if ($codigo_bdns) {
        $url = "https://www.pap.hacienda.gob.es/bdnstrans/GE/es/convocatorias/{$codigo_bdns}";
    } else {
        // Fallback al ID interno si no hay código
        $url = "https://www.infosubvenciones.es/bdnstrans/GE/es/convocatorias/{$item['id']}";
    }

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

    // Para cada búsqueda activa, verificar si coincide
    foreach ($busquedas as $busqueda) {
        $palabrasBusqueda = array_map('trim', explode(',', $busqueda['palabras_clave']));
        $coincidencias = [];

        foreach ($palabrasBusqueda as $palabra) {
            if (stripos($texto, $palabra) !== false) {
                $coincidencias[] = $palabra;
            }
        }

        if (!empty($coincidencias)) {
            // Verificar si ya existe para esta búsqueda (por URL antigua o nueva)
            $stmt = $pdo->prepare("
                SELECT id, url_detalle 
                FROM resultados 
                WHERE (url_detalle = ? OR url_detalle = ?) AND busqueda_id = ?
            ");

            // Buscar tanto por la URL nueva como por la antigua (con ID interno)
            $url_antigua = "https://www.infosubvenciones.es/bdnstrans/GE/es/convocatorias/{$item['id']}";
            $stmt->execute([$url, $url_antigua, $busqueda['id']]);
            $existente = $stmt->fetch();

            if ($existente) {
                // Ya existe: actualizar la URL si es diferente
                if ($existente['url_detalle'] != $url) {
                    $upd = $pdo->prepare("UPDATE resultados SET url_detalle = ?, codigo_bdns = ? WHERE id = ?");
                    $upd->execute([$url, $codigo_bdns, $existente['id']]);
                    $actualizados++;
                    echo "🔄 Actualizado [{$codigo_bdns}]: URL corregida\n";
                } else {
                    $duplicados++;
                }
            } else {
                // Insertar nuevo resultado
                $stmt = $pdo->prepare("
                    INSERT INTO resultados (
                        busqueda_id, 
                        fuente_id, 
                        titulo, 
                        descripcion_corta, 
                        organismo,
                        fecha_publicacion, 
                        url_detalle, 
                        codigo_bdns,
                        palabras_coincidentes, 
                        relevancia
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");

                $result = $stmt->execute([
                    $busqueda['id'],
                    $fuenteId,
                    $item['descripcion'] ?? 'Sin título',
                    $item['descripcion'] ?? '',
                    $organismo,
                    $item['fechaRecepcion'] ?? null,
                    $url,
                    $codigo_bdns,
                    json_encode(array_unique($coincidencias), JSON_UNESCAPED_UNICODE),
                    count(array_unique($coincidencias))
                ]);

                if ($result) {
                    $importados++;
                    echo "✅ Nuevo [{$codigo_bdns}]: " . mb_substr($item['descripcion'], 0, 60) . "...\n";
                }
            }

            // Salir del bucle de búsquedas si ya encontramos coincidencia
            break;
        }
    }
}

echo "\n📊 RESUMEN:\n";
echo "   Total registros en JSON: " . count($datos) . "\n";
echo "   Importados (nuevos): $importados\n";
echo "   Actualizados (URL corregida): $actualizados\n";
echo "   Duplicados (ya correctos): $duplicados\n";
echo "   Ignorados (sin coincidencia): " . (count($datos) - $importados - $actualizados - $duplicados) . "\n";

// 6. Mostrar algunos ejemplos de los resultados guardados
echo "\n" . str_repeat("=", 60) . "\n";
echo "📋 EJEMPLOS DE RESULTADOS GUARDADOS\n";
echo str_repeat("=", 60) . "\n";

$stmt = $pdo->prepare("
    SELECT r.*, b.nombre as busqueda_nombre 
    FROM resultados r
    JOIN busquedas b ON r.busqueda_id = b.id
    WHERE r.fuente_id = ?
    ORDER BY r.fecha_deteccion DESC
    LIMIT 5
");
$stmt->execute([$fuenteId]);
$ejemplos = $stmt->fetchAll();

if (count($ejemplos) > 0) {
    foreach ($ejemplos as $e) {
        echo "\n• " . mb_substr($e['titulo'], 0, 80) . "...\n";
        echo "  📌 BDNS: {$e['codigo_bdns']}\n";
        echo "  🏛️  Organismo: " . mb_substr($e['organismo'], 0, 50) . "...\n";
        echo "  🔗 URL: {$e['url_detalle']}\n";
        echo "  🎯 Búsqueda: {$e['busqueda_nombre']}\n";
    }
} else {
    echo "\n📭 No hay resultados de BDNS en la base de datos.\n";
}

echo "\n✅ Proceso completado\n";
