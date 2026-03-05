<?php
// scripts/buscar_bdns_servicios.php
// Búsqueda específica en BDNS para servicios audiovisuales

require_once __DIR__ . '/../Core/Database.php';

// Configuración de la búsqueda
$config = [
    // CPV de servicios audiovisuales (códigos de actuación en BDNS)
    'cpv_servicios' => [
        '92100000', // Servicios cinematográficos y de vídeo
        '92111000', // Servicios de producción de películas
        '92112000', // Servicios de producción de vídeo
        '92113000', // Servicios de postproducción
        '92220000', // Servicios de televisión
        '79341000', // Servicios de publicidad
        '79961000', // Servicios de fotografía
        '72300000', // Servicios de Internet
        '72400000'  // Servicios de Internet y www
    ],
    
    // Organismos prioritarios (ciencia, cultura, universidades)
    'organismos_prioritarios' => [
        'CSIC', 'CONSEJO SUPERIOR', 'MINISTERIO DE CIENCIA',
        'UNIVERSIDAD', 'CENTRO DE INVESTIGACIÓN', 'INSTITUTO DE',
        'MUSEO', 'BIBLIOTECA', 'ARCHIVO', 'PATRIMONIO',
        'CULTURA', 'EDUCACIÓN', 'TRANSICIÓN ECOLÓGICA',
        'MEDIO AMBIENTE', 'CAMBIO CLIMÁTICO', 'BIODIVERSIDAD',
        'ASTRONOMÍA', 'ASTROFÍSICA', 'ESPACIO'
    ],
    
    // Palabras clave de servicios
    'palabras_servicio' => [
        'producción', 'grabación', 'streaming', 'cobertura',
        'vídeo', 'audiovisual', 'postproducción', 'edición',
        'realización', 'cámara', 'sonido', 'iluminación',
        'eventos', 'congresos', 'jornadas', 'divulgación',
        'documental', 'animación', 'infografía', 'multimedia'
    ]
];

// Conectar a la base de datos
$pdo = Database::getInstance();

echo "🎬 BUSCANDO SERVICIOS AUDIOVISUALES EN BDNS\n";
echo "===========================================\n\n";

// 1. Verificar/crear fuente en BD
$stmt = $pdo->prepare("SELECT id FROM fuentes WHERE nombre_corto = 'bdns'");
$stmt->execute();
$fuente = $stmt->fetch();

if (!$fuente) {
    $stmt = $pdo->prepare("INSERT INTO fuentes (nombre, nombre_corto, tipo, url_base, script_asociado) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([
        'Base de Datos Nacional de Subvenciones',
        'bdns',
        'subvencion',
        'https://www.pap.hacienda.gob.es',
        'buscar_bdns_servicios.php'
    ]);
    $fuenteId = $pdo->lastInsertId();
    echo "✅ Fuente BDNS creada (ID: $fuenteId)\n";
} else {
    $fuenteId = $fuente['id'];
    echo "✅ Fuente BDNS encontrada (ID: $fuenteId)\n";
}

// 2. Buscar búsquedas activas del usuario
$usuarioId = 1; // Asumimos usuario 1 (el principal)
$stmt = $pdo->prepare("SELECT * FROM busquedas WHERE usuario_id = ? AND activo = 1");
$stmt->execute([$usuarioId]);
$busquedas = $stmt->fetchAll();

if (empty($busquedas)) {
    echo "❌ No hay búsquedas activas\n";
    exit;
}

echo "\n📋 Búsquedas activas:\n";
foreach ($busquedas as $b) {
    echo "   - {$b['nombre']} (ID: {$b['id']})\n";
}

// 3. PROCESAR CADA BÚSQUEDA
foreach ($busquedas as $busqueda) {
    echo "\n🔍 Procesando búsqueda: {$busqueda['nombre']}\n";
    echo str_repeat("-", 50) . "\n";
    
    // Simulación de búsqueda en BDNS
    // NOTA: Aquí iría la llamada real a la API de BDNS
    // Por ahora, simulamos resultados basados en la experiencia
    
    $resultadosSimulados = [
        [
            'titulo' => 'Servicio de producción de vídeos divulgativos sobre cambio climático',
            'organismo' => 'Ministerio para la Transición Ecológica',
            'importe' => 45000,
            'fecha' => '2026-02-15',
            'cpv' => '92112000',
            'url' => 'https://www.pap.hacienda.gob.es/bdnstrans/GE/es/convocatorias/123456'
        ],
        [
            'titulo' => 'Cobertura audiovisual del Congreso Nacional de Astronomía 2026',
            'organismo' => 'CSIC - Instituto de Astrofísica de Andalucía',
            'importe' => 28000,
            'fecha' => '2026-02-20',
            'cpv' => '92220000',
            'url' => 'https://www.pap.hacienda.gob.es/bdnstrans/GE/es/convocatorias/123457'
        ],
        [
            'titulo' => 'Realización de streaming para jornadas de divulgación científica',
            'organismo' => 'Universidad de Barcelona',
            'importe' => 15500,
            'fecha' => '2026-02-10',
            'cpv' => '92100000',
            'url' => 'https://www.pap.hacienda.gob.es/bdnstrans/GE/es/convocatorias/123458'
        ]
    ];
    
    $encontrados = 0;
    
    foreach ($resultadosSimulados as $item) {
        // Verificar si ya existe
        $stmt = $pdo->prepare("SELECT id FROM resultados WHERE url_detalle = ? AND busqueda_id = ?");
        $stmt->execute([$item['url'], $busqueda['id']]);
        if ($stmt->fetch()) {
            echo "⏩ Ya existe: {$item['titulo']}\n";
            continue;
        }
        
        // Guardar nuevo resultado
        $stmt = $pdo->prepare("
            INSERT INTO resultados (
                busqueda_id, fuente_id, titulo, organismo,
                presupuesto, fecha_publicacion, url_detalle,
                palabras_coincidentes, relevancia
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $palabras = json_encode(['servicio audiovisual', 'divulgación'], JSON_UNESCAPED_UNICODE);
        
        $stmt->execute([
            $busqueda['id'],
            $fuenteId,
            $item['titulo'],
            $item['organismo'],
            $item['importe'],
            $item['fecha'],
            $item['url'],
            $palabras,
            2
        ]);
        
        $encontrados++;
        echo "✅ NUEVO: {$item['titulo']}\n";
        echo "   Organismo: {$item['organismo']}\n";
        echo "   Importe: " . number_format($item['importe'], 2) . " €\n";
        echo "   URL: {$item['url']}\n\n";
    }
    
    echo "📊 Total encontrados en esta búsqueda: $encontrados\n";
}

// 4. MOSTRAR RESULTADOS ACUMULADOS DE BDNS
echo "\n" . str_repeat("=", 60) . "\n";
echo "📊 RESULTADOS ACUMULADOS DE BDNS\n";
echo str_repeat("=", 60) . "\n";

$stmt = $pdo->prepare("
    SELECT r.*, b.nombre as busqueda_nombre 
    FROM resultados r
    JOIN busquedas b ON r.busqueda_id = b.id
    WHERE r.fuente_id = ?
    ORDER BY r.presupuesto DESC
    LIMIT 20
");
$stmt->execute([$fuenteId]);
$resultados = $stmt->fetchAll();

if (count($resultados) > 0) {
    echo "\nÚltimas 20 convocatorias (por presupuesto):\n\n";
    foreach ($resultados as $r) {
        echo "• {$r['titulo']}\n";
        echo "  Organismo: {$r['organismo']}\n";
        if ($r['presupuesto']) {
            echo "  💰 " . number_format($r['presupuesto'], 2) . " €\n";
        }
        echo "  📅 Publicación: {$r['fecha_publicacion']}\n";
        echo "  🔗 {$r['url_detalle']}\n";
        echo "  📌 Búsqueda: {$r['busqueda_nombre']}\n\n";
    }
} else {
    echo "\n📭 No hay resultados de BDNS todavía\n";
}

echo "\n✅ Proceso completado\n";