<?php
// public/index.php

// 1. PRIMERO: Includes necesarios
require_once __DIR__ . '/../Core/Database.php';
require_once __DIR__ . '/../Core/Auth.php';
require_once __DIR__ . '/../Core/AuthMiddleware.php';

// 2. SEGUNDO: Iniciar sesión
session_start();

// Mostrar mensajes de sesión
if (isset($_SESSION['mensaje'])) {
    echo "<div class='aviso'>" . $_SESSION['mensaje'] . "</div>";
    unset($_SESSION['mensaje']);
}

// 3. TERCERO: Proteger la página (redirige a login si no hay sesión)
AuthMiddleware::protegerPagina();

// 4. CUARTO: Inicializar clases
$auth = new Auth();
$pdo = Database::getInstance();

// ============================================
// PROCESAR ACCIONES POST (TODOS LOS BOTONES)
// ============================================
// Procesar acciones (DOGE, BOE, BOPB, DIBA, ZARAGOZA, CSIC, BDNS)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['ejecutar_doge'])) {
        echo "<div class='aviso'>⏳ Ejecutando DOGE... (puede tardar unos segundos)</div>";
        ob_flush();
        flush();
        exec('php ' . __DIR__ . '/../scripts/prueba_doge.php 2>&1', $output, $return);
        echo "<div class='aviso'>✅ DOGE ejecutado. " . ($return === 0 ? 'OK' : 'Error') . "</div>";
    }
    if (isset($_POST['ejecutar_boe'])) {
        echo "<div class='aviso'>⏳ Ejecutando BOE...</div>";
        ob_flush();
        flush();
        exec('php ' . __DIR__ . '/../scripts/prueba_boe.php 2>&1', $output, $return);
        echo "<div class='aviso'>✅ BOE ejecutado. " . ($return === 0 ? 'OK' : 'Error') . "</div>";
    }
    if (isset($_POST['ejecutar_bopb'])) {
        echo "<div class='aviso'>⏳ Ejecutando BOPB (Barcelona)...</div>";
        ob_flush();
        flush();
        exec('php ' . __DIR__ . '/../scripts/prueba_bopb.php 2>&1', $output, $return);
        echo "<div class='aviso'>✅ BOPB ejecutado. " . ($return === 0 ? 'OK' : 'Error') . "</div>";
    }
    if (isset($_POST['ejecutar_diba'])) {
        echo "<div class='aviso'>⏳ Ejecutando DIBA (Barcelona API)...</div>";
        ob_flush();
        flush();
        exec('php ' . __DIR__ . '/../scripts/prueba_diba.php 2>&1', $output, $return);
        echo "<div class='aviso'>✅ DIBA ejecutado. " . ($return === 0 ? 'OK' : 'Error') . "</div>";
    }
    if (isset($_POST['ejecutar_zaragoza'])) {
        echo "<div class='aviso'>⏳ Ejecutando Zaragoza...</div>";
        ob_flush();
        flush();
        exec('php ' . __DIR__ . '/../scripts/prueba_zaragoza.php 2>&1', $output, $return);
        echo "<div class='aviso'>✅ Zaragoza ejecutado. " . ($return === 0 ? 'OK' : 'Error') . "</div>";
    }
    if (isset($_POST['ejecutar_csic'])) {
        echo "<div class='aviso'>⏳ Ejecutando CSIC...</div>";
        ob_flush();
        flush();
        exec('php ' . __DIR__ . '/../scripts/prueba_csic.php 2>&1', $output, $return);
        echo "<div class='aviso'>✅ CSIC ejecutado. " . ($return === 0 ? 'OK' : 'Error') . "</div>";
    }
    if (isset($_POST['ejecutar_bdns'])) {
        echo "<div class='aviso'>⏳ Ejecutando BDNS...</div>";
        ob_flush();
        flush();
        // Primero descargar JSON, luego importar
        exec('php ' . __DIR__ . '/../scripts/descargar_bdns_json.php 2>&1', $output1, $return1);
        if ($return1 === 0) {
            exec('php ' . __DIR__ . '/../scripts/importar_bdns.php 2>&1', $output2, $return2);
            echo "<div class='aviso'>✅ BDNS ejecutado (descarga + importación)</div>";
        } else {
            echo "<div class='aviso'>❌ Error en BDNS</div>";
        }
    }
}

// ============================================
// ESTADÍSTICAS COMPLETAS (6 TARJETAS)
// ============================================
$stats = [
    'total' => $pdo->query("SELECT COUNT(*) FROM resultados")->fetchColumn(),
    'doge' => $pdo->query("SELECT COUNT(*) FROM resultados WHERE fuente_id = (SELECT id FROM fuentes WHERE nombre_corto = 'doge')")->fetchColumn(),
    'boe' => $pdo->query("SELECT COUNT(*) FROM resultados WHERE fuente_id = (SELECT id FROM fuentes WHERE nombre_corto = 'boe')")->fetchColumn(),
    'bopb' => $pdo->query("SELECT COUNT(*) FROM resultados WHERE fuente_id = (SELECT id FROM fuentes WHERE nombre_corto = 'bopb')")->fetchColumn(),
    'diba' => $pdo->query("SELECT COUNT(*) FROM resultados WHERE fuente_id = (SELECT id FROM fuentes WHERE nombre_corto = 'diba')")->fetchColumn(),
    'zaragoza' => $pdo->query("SELECT COUNT(*) FROM resultados WHERE fuente_id = (SELECT id FROM fuentes WHERE nombre_corto = 'zaragoza')")->fetchColumn(),
    'csic' => $pdo->query("SELECT COUNT(*) FROM resultados WHERE fuente_id = (SELECT id FROM fuentes WHERE nombre_corto = 'csic')")->fetchColumn(),
    'bdns' => $pdo->query("SELECT COUNT(*) FROM resultados WHERE fuente_id = (SELECT id FROM fuentes WHERE nombre_corto = 'bdns')")->fetchColumn(),
    'ultima' => $pdo->query("SELECT MAX(fecha_deteccion) FROM resultados")->fetchColumn()
];

// Palabras clave de ciencia y audiovisual (para clasificar)
$keywordsCiencia = ['divulgación científica', 'investigación', 'ciencia', 'astronomía', 'cambio climático', 'medio ambiente', 'biodiversidad', 'tecnología'];
$keywordsAV = ['producción audiovisual', 'vídeo', 'streaming', 'documental', 'grabación', 'cobertura', 'contenido multimedia', 'postproducción'];

// Parámetros de filtro
$fuente = $_GET['fuente'] ?? '';
$busqueda = $_GET['busqueda'] ?? '1';
$dias = (int)($_GET['dias'] ?? 30);
$estadoFiltro = isset($_GET['estado']) && $_GET['estado'] !== '' ? (int)$_GET['estado'] : null;
$soloRelevantes = $_GET['relevantes'] ?? '';
$page = (int)($_GET['page'] ?? 1);
$limit = 12;
$offset = ($page - 1) * $limit;

// Construir query
$where = [];
$params = [];

if ($fuente) {
    $where[] = "fuente_id = (SELECT id FROM fuentes WHERE nombre_corto = ?)";
    $params[] = $fuente;
}

if ($busqueda) {
    $where[] = "busqueda_id = ?";
    $params[] = $busqueda;
}

if ($dias > 0) {
    $where[] = "fecha_publicacion >= DATE_SUB(CURDATE(), INTERVAL $dias DAY)";
}

if ($estadoFiltro !== null) {
    $where[] = "estado_usuario = ?";
    $params[] = $estadoFiltro;
}

$whereClause = $where ? "WHERE " . implode(" AND ", $where) : "";

// Obtener total para paginación
$totalStmt = $pdo->prepare("SELECT COUNT(*) FROM resultados $whereClause");
$totalStmt->execute($params);
$total = $totalStmt->fetchColumn();

// Obtener resultados
$stmt = $pdo->prepare("
    SELECT r.*, f.nombre_corto as fuente_nombre 
    FROM resultados r
    JOIN fuentes f ON r.fuente_id = f.id
    $whereClause
    ORDER BY r.fecha_publicacion DESC, r.relevancia DESC
    LIMIT $limit OFFSET $offset
");
$stmt->execute($params);
$resultados = $stmt->fetchAll();

// Obtener búsquedas para el filtro
$busquedas = $pdo->query("SELECT id, nombre, palabras_clave FROM busquedas ORDER BY nombre")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🎬 Buscador de Licitaciones Audiovisuales + Ciencia</title>
    <link rel="stylesheet" href="css/style.css">
</head>

<body>
    <nav class="navbar">
        <div class="container">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h1>🎬 Buscador de Licitaciones</h1>
                    <div class="subtitle">Contratos públicos para divulgación científica, producción audiovisual, streaming y más</div>
                </div>
                <div style="display: flex; gap: 1rem; align-items: center;">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <span style="color: #4b5563; font-weight: 500;">
                            👤 <?= htmlspecialchars($_SESSION['user_nombre'] ?? 'Usuario') ?>
                        </span>
                        <a href="busquedas.php" class="btn btn-secondary">⚙️ Mis búsquedas</a>
                        <a href="perfil.php" class="btn btn-secondary">✏️ Perfil</a>
                        <a href="logout.php" class="btn btn-secondary">🚪 Cerrar sesión</a>
                    <?php else: ?>
                        <a href="login.php" class="btn btn-primary">🔐 Iniciar sesión</a>
                        <a href="register.php" class="btn btn-secondary">📝 Registrarse</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <div class="container">
        <!-- ============================================
             ESTADÍSTICAS COMPLETAS 
             ============================================ -->

        <div class="stats-grid">
            <div class="stat-card">
                <h3><?= $stats['total'] ?></h3>
                <p>Total resultados</p>
            </div>
            <div class="stat-card">
                <h3><?= $stats['doge'] ?></h3>
                <p>DOGE (Galicia)</p>
            </div>
            <div class="stat-card">
                <h3><?= $stats['boe'] ?></h3>
                <p>BOE</p>
            </div>
            <div class="stat-card">
                <h3><?= $stats['bopb'] ?></h3>
                <p>BOPB (Barcelona)</p>
            </div>
            <div class="stat-card">
                <h3><?= $stats['diba'] ?></h3>
                <p>DIBA (Barcelona API)</p>
            </div>
            <div class="stat-card">
                <h3><?= $stats['zaragoza'] ?></h3>
                <p>Zaragoza</p>
            </div>
            <div class="stat-card">
                <h3><?= $stats['csic'] ?></h3>
                <p>CSIC</p>
            </div>
            <div class="stat-card">
                <h3><?= $stats['bdns'] ?></h3>
                <p>BDNS</p>
            </div>
            <div class="stat-card">
                <h3><?= $stats['ultima'] ? date('d/m/Y', strtotime($stats['ultima'])) : '-' ?></h3>
                <p>Última detección</p>
            </div>
        </div>

        <!-- ============================================
             ACCIONES RÁPIDAS CON TODOS LOS BOTONES
             ============================================ -->

        <div class="filters">
            <form method="POST" class="filter-group" style="justify-content: space-between;">
                <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                    <button type="submit" name="ejecutar_doge" class="btn btn-secondary">🔄 Ejecutar DOGE</button>
                    <button type="submit" name="ejecutar_boe" class="btn btn-secondary">🔄 Ejecutar BOE</button>
                    <button type="submit" name="ejecutar_bopb" class="btn btn-secondary">🔄 Ejecutar BOPB</button>
                    <button type="submit" name="ejecutar_diba" class="btn btn-secondary">🔄 Ejecutar DIBA</button>
                    <button type="submit" name="ejecutar_zaragoza" class="btn btn-secondary">🔄 Ejecutar Zaragoza</button>
                    <button type="submit" name="ejecutar_csic" class="btn btn-secondary">🔄 Ejecutar CSIC</button>
                    <button type="submit" name="ejecutar_bdns" class="btn btn-secondary">🔄 Ejecutar BDNS</button>
                </div>
            </form>
        </div>
        <div>
            <a href="?relevantes=1" class="btn btn-primary">🎯 Solo relevantes</a>
            <a href="?" class="btn btn-secondary" style="margin-left: 0.5rem;">⟲ Quitar filtros</a>
        </div>
        </form>
    </div>

    <!-- ============================================
             FILTROS (SELECTOR COMPLETO)
             ============================================ -->
    <div class="filters">
        <form method="GET" class="filter-group">
            <select name="busqueda">
                <option value="">Todas las búsquedas</option>
                <?php foreach ($busquedas as $b): ?>
                    <option value="<?= $b['id'] ?>" <?= $busqueda == $b['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($b['nombre']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <select name="fuente">
                <option value="">Todas las fuentes</option>
                <option value="doge" <?= $fuente == 'doge' ? 'selected' : '' ?>>DOGE (Galicia)</option>
                <option value="boe" <?= $fuente == 'boe' ? 'selected' : '' ?>>BOE</option>
                <option value="bopb" <?= $fuente == 'bopb' ? 'selected' : '' ?>>BOPB (Barcelona)</option>
                <option value="diba" <?= $fuente == 'diba' ? 'selected' : '' ?>>DIBA (Barcelona API)</option>
                <option value="zaragoza" <?= $fuente == 'zaragoza' ? 'selected' : '' ?>>Zaragoza</option>
                <option value="csic" <?= $fuente == 'csic' ? 'selected' : '' ?>>CSIC</option>
                <option value="bdns" <?= $fuente == 'bdns' ? 'selected' : '' ?>>BDNS</option>
            </select>

            <select name="dias">
                <option value="7" <?= $dias == 7 ? 'selected' : '' ?>>Últimos 7 días</option>
                <option value="15" <?= $dias == 15 ? 'selected' : '' ?>>Últimos 15 días</option>
                <option value="30" <?= $dias == 30 ? 'selected' : '' ?>>Últimos 30 días</option>
                <option value="90" <?= $dias == 90 ? 'selected' : '' ?>>Últimos 90 días</option>
                <option value="0" <?= $dias == 0 ? 'selected' : '' ?>>Todos</option>
            </select>

            <select name="estado">
                <option value="">Todos los estados</option>
                <option value="0" <?= ($estadoFiltro === 0) ? 'selected' : '' ?>>⏳ Pendiente</option>
                <option value="1" <?= ($estadoFiltro === 1) ? 'selected' : '' ?>>⭐ Interesante</option>
                <option value="2" <?= ($estadoFiltro === 2) ? 'selected' : '' ?>>❌ Descartado</option>
            </select>

            <button type="submit">Filtrar</button>
            <a href="?" class="btn btn-secondary">Limpiar</a>
        </form>
    </div>

    <!-- Resultados -->
    <div class="results-header">
        <h2>📋 Resultados (<?= $total ?> encontrados)</h2>
    </div>

    <div class="results-grid">
        <?php foreach ($resultados as $r):
            // Analizar keywords
            $keywords = json_decode($r['palabras_coincidentes'], true) ?? [];

            // Clasificar por tipo de oportunidad
            $tipoOportunidad = 'Otros';
            $palabrasServicio = ['contrato', 'servicios', 'asistencia técnica', 'producción', 'grabación', 'vídeo', 'streaming', 'cobertura', 'eventos', 'congresos', 'jornadas', 'material audiovisual', 'campaña', 'publicidad'];
            $palabrasPersonal = ['oposiciones', 'concurso de méritos', 'plazas', 'personal laboral', 'funcionario', 'empleo público', 'bolsa de trabajo', 'contratación laboral', 'puesto de trabajo', 'nómina', 'tribunal calificador', 'proceso selectivo', 'baremación'];

            foreach ($keywords as $kw) {
                $kwLower = strtolower($kw);
                foreach ($palabrasServicio as $ps) {
                    if (strpos($kwLower, strtolower($ps)) !== false) {
                        $tipoOportunidad = 'SERVICIOS';
                        break 2;
                    }
                }
                foreach ($palabrasPersonal as $pp) {
                    if (strpos($kwLower, strtolower($pp)) !== false) {
                        $tipoOportunidad = 'PERSONAL';
                        break 2;
                    }
                }
            }

            // Clasificar por área científica
            $areaCiencia = [];
            $mapaCiencia = [
                'astronomía' => ['astronomía', 'astrofísica', 'espacio', 'universo', 'telescopio'],
                'clima' => ['cambio climático', 'clima', 'calentamiento global', 'meteorología'],
                'medio ambiente' => ['medio ambiente', 'biodiversidad', 'naturaleza', 'ecosistema', 'conservación'],
                'tecnología' => ['tecnología', 'innovación', 'inteligencia artificial', 'robótica'],
                'ciencia general' => ['divulgación científica', 'investigación', 'ciencia', 'científico', 'I+D']
            ];

            foreach ($keywords as $kw) {
                $kwLower = strtolower($kw);
                foreach ($mapaCiencia as $area => $terminos) {
                    foreach ($terminos as $termino) {
                        if (strpos($kwLower, $termino) !== false) {
                            $areaCiencia[] = $area;
                            break 2;
                        }
                    }
                }
            }
            $areaCiencia = array_unique($areaCiencia);

            // Clasificar por tipo de servicio audiovisual
            $tipoAV = [];
            $mapaAV = [
                'producción' => ['producción audiovisual', 'producción de vídeo', 'realización'],
                'streaming' => ['streaming', 'retransmisión', 'directo', 'cobertura en directo'],
                'documental' => ['documental', 'reportaje', 'pieza divulgativa'],
                'grabación' => ['grabación', 'cobertura', 'rodaje'],
                'postproducción' => ['postproducción', 'edición', 'montaje', 'vfx'],
                'contenido educativo' => ['contenido educativo', 'material didáctico', 'formación']
            ];

            foreach ($keywords as $kw) {
                $kwLower = strtolower($kw);
                foreach ($mapaAV as $tipo => $terminos) {
                    foreach ($terminos as $termino) {
                        if (strpos($kwLower, $termino) !== false) {
                            $tipoAV[] = $tipo;
                            break 2;
                        }
                    }
                }
            }
            $tipoAV = array_unique($tipoAV);

            $tieneCiencia = !empty($areaCiencia);
            $tieneAV = !empty($tipoAV);
            $esRelevante = $tieneCiencia && $tieneAV;

            // Estado actual
            $estadoActual = (int)($r['estado_usuario'] ?? 0);
            $estadoTexto = ['pendiente', 'interesante', 'descartado'][$estadoActual];
            $estadoColor = ['#6b7280', '#10b981', '#ef4444'][$estadoActual];

            // Título y organismo
            $organismo = htmlspecialchars($r['organismo'] ?: 'No especificado');
            $presupuestoTexto = $r['presupuesto'] ? '💰 ' . number_format($r['presupuesto'], 0) . ' €' : '';

            // Icono y color según tipo
            $iconoTipo = [
                'SERVICIOS' => '🎯',
                'PERSONAL' => '👤',
                'Otros' => '📄'
            ];

            $colorTipo = [
                'SERVICIOS' => '#10b981',
                'PERSONAL' => '#6b7280',
                'Otros' => '#9ca3af'
            ];
        ?>
            <div class="result-card" style="border-left-color: <?= $colorTipo[$tipoOportunidad] ?>; position: relative;">
                <!-- Estado visual en la esquina -->
                <div style="position: absolute; top: 10px; right: 10px; z-index: 5;">
                    <span style="background: <?= $estadoColor ?>20; color: <?= $estadoColor ?>; padding: 0.25rem 0.75rem; border-radius: 1rem; font-size: 0.7rem; font-weight: 600;">
                        <?= $estadoTexto ?>
                    </span>
                </div>

                <div class="card-header" style="padding-right: 80px;">
                    <div class="badge-group">
                        <span class="badge <?= $r['fuente_nombre'] ?>">
                            <?= strtoupper($r['fuente_nombre']) ?>
                        </span>
                        <span class="badge" style="background: <?= $colorTipo[$tipoOportunidad] ?>20; color: <?= $colorTipo[$tipoOportunidad] ?>; font-weight: bold;">
                            <?= $iconoTipo[$tipoOportunidad] ?> <?= $tipoOportunidad ?>
                        </span>
                        <!-- NUEVO: Código BDNS (solo para fuente BDNS) -->
                        <?php if ($r['fuente_nombre'] == 'bdns' && !empty($r['codigo_bdns'])): ?>
                            <span class="badge" style="background: #ffedd5; color: #9a3412; font-weight: bold;">
                                📋 BDNS: <?= $r['codigo_bdns'] ?>
                            </span>
                        <?php endif; ?>

                        <!-- NUEVO: Oportunidad relevante (si tiene keywords de ciencia y AV) -->
                        <?php if ($esRelevante): ?>
                            <span class="badge" style="background: #d1fae5; color: #065f46; font-weight: bold;">
                                🎯 OPORTUNIDAD
                            </span>
                        <?php endif; ?>
                    </div>
                    <span class="badge keyword">🔑 <?= count($keywords) ?></span>
                </div>

                <!-- Título (enlace a detalle.php) -->
                <h3 class="card-title" style="font-size: 1rem; margin-bottom: 0.5rem; margin-top: 1.5rem;">
                    <a href="detalle.php?id=<?= $r['id'] ?>">
                        <?= htmlspecialchars(mb_substr($r['titulo'], 0, 150)) ?><?= strlen($r['titulo']) > 150 ? '...' : '' ?>
                    </a>
                </h3>
                <!-- Botón de enlace original (para depuración) -->
                <div style="margin-top: 0.5rem;">
                    <a href="<?= htmlspecialchars($r['url_detalle']) ?>" target="_blank" class="btn btn-small btn-outline">
                        🔗 Ver original
                    </a>
                </div>

                <div style="display: flex; flex-wrap: wrap; gap: 1rem; margin: 0.5rem 0; font-size: 0.9rem; color: #4b5563;">
                    <span>🏛️ <?= htmlspecialchars(mb_substr($organismo, 0, 50)) ?><?= strlen($organismo) > 50 ? '...' : '' ?></span>
                    <?php if ($presupuestoTexto): ?>
                        <span><?= $presupuestoTexto ?></span>
                    <?php endif; ?>
                </div>

                <div style="display: flex; gap: 1.5rem; margin: 0.5rem 0; font-size: 0.85rem; color: #6b7280;">
                    <span>📅 Publicación: <?= $r['fecha_publicacion'] ? date('d/m/Y', strtotime($r['fecha_publicacion'])) : 'N/D' ?></span>
                    <span>🔍 Detección: <?= isset($r['ultima_deteccion']) ? date('d/m/Y', strtotime($r['ultima_deteccion'])) : 'N/D' ?></span>
                </div>

                <?php if (!empty($areaCiencia) || !empty($tipoAV)): ?>
                    <div style="display: flex; flex-wrap: wrap; gap: 0.5rem; margin: 0.75rem 0;">
                        <?php foreach ($areaCiencia as $area): ?>
                            <span style="background: #dbeafe; color: #1e40af; padding: 0.2rem 0.75rem; border-radius: 1rem; font-size: 0.75rem; font-weight: 500;">
                                🔬 <?= $area ?>
                            </span>
                        <?php endforeach; ?>
                        <?php foreach ($tipoAV as $tipo): ?>
                            <span style="background: #fce7f3; color: #9d174d; padding: 0.2rem 0.75rem; border-radius: 1rem; font-size: 0.75rem; font-weight: 500;">
                                🎥 <?= $tipo ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <!-- BOTONES DE ESTADO Y ELIMINAR -->
                <div style="display: flex; gap: 0.5rem; margin-top: 1rem; border-top: 1px solid #e5e7eb; padding-top: 1rem;">
                    <button onclick="cambiarEstado(<?= $r['id'] ?>, 0)" class="btn btn-secondary btn-small" style="background: <?= $estadoActual == 0 ? '#4f46e5' : '#6b7280' ?>; color: white; flex: 1; padding: 0.4rem;">
                        ⏳ Pendiente
                    </button>
                    <button onclick="cambiarEstado(<?= $r['id'] ?>, 1)" class="btn btn-secondary btn-small" style="background: <?= $estadoActual == 1 ? '#10b981' : '#6b7280' ?>; color: white; flex: 1; padding: 0.4rem;">
                        ⭐ Interesante
                    </button>
                    <button onclick="cambiarEstado(<?= $r['id'] ?>, 2)" class="btn btn-secondary btn-small" style="background: <?= $estadoActual == 2 ? '#ef4444' : '#6b7280' ?>; color: white; flex: 1; padding: 0.4rem;">
                        ❌ Descartado
                    </button>

                    <!-- NUEVO BOTÓN ELIMINAR -->
                    <form method="POST" action="eliminar_resultado.php" style="display: inline;" onsubmit="return confirm('¿Eliminar permanentemente este resultado?')">
                        <input type="hidden" name="id" value="<?= $r['id'] ?>">
                        <button type="submit" class="btn btn-secondary btn-small" style="background: #6b7280; color: white; padding: 0.4rem 0.8rem;" title="Eliminar">
                            🗑️
                        </button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>

        <?php if (empty($resultados)): ?>
            <div class="empty-state">
                <p>📭 No hay resultados que mostrar</p>
                <p style="font-size: 0.9rem; margin-top: 0.5rem;">Ejecuta desde los botones superiores</p>
            </div>
        <?php endif; ?>
    </div>

    <?php if ($total > $limit): ?>
        <div class="pagination">
            <?php for ($i = 1; $i <= ceil($total / $limit); $i++): ?>
                <a href="?page=<?= $i ?>&fuente=<?= urlencode($fuente) ?>&busqueda=<?= urlencode($busqueda) ?>&dias=<?= $dias ?><?= $estadoFiltro !== null ? '&estado=' . $estadoFiltro : '' ?>">
                    <button class="<?= $i == $page ? 'active' : '' ?>"><?= $i ?></button>
                </a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
    </div>

    <script src="js/app.js"></script>
</body>

</html>