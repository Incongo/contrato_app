<?php
// public/index.php - VERSIÓN MODERNA CON TAILWIND

require_once __DIR__ . '/../Core/Database.php';
require_once __DIR__ . '/../Core/Auth.php';
require_once __DIR__ . '/../Core/AuthMiddleware.php';

session_start();

// Mostrar mensajes de sesión
if (isset($_SESSION['mensaje'])) {
    $mensaje = $_SESSION['mensaje'];
    unset($_SESSION['mensaje']);
}

AuthMiddleware::protegerPagina();

$auth = new Auth();
$pdo = Database::getInstance();

// ============================================
// PROCESAR ACCIONES POST
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acciones = [
        'doge',
        'boe',
        'bopb',
        'diba',
        'zaragoza',
        'csic',
        'bdns',
        'placsp'
    ];

    foreach ($acciones as $accion) {
        if (isset($_POST["ejecutar_$accion"])) {
            $mensaje = "<div class='mb-4 p-4 bg-blue-50 border-l-4 border-blue-500 text-blue-700 animate-fadeIn'>
                ⏳ Ejecutando " . strtoupper($accion) . "...</div>";
            ob_flush();
            flush();
            exec("php " . __DIR__ . "/../scripts/prueba_$accion.php 2>&1", $output, $return);
            $mensaje .= "<div class='mb-4 p-4 " . ($return === 0 ? 'bg-green-50 border-l-4 border-green-500 text-green-700' : 'bg-red-50 border-l-4 border-red-500 text-red-700') . " animate-fadeIn'>
                " . ($return === 0 ? '✅' : '❌') . " " . strtoupper($accion) . " ejecutado.</div>";
        }
    }
}

// ============================================
// ESTADÍSTICAS
// ============================================
$fuentes = ['doge', 'boe', 'bopb', 'diba', 'zaragoza', 'csic', 'bdns', 'placsp'];
$stats = ['total' => $pdo->query("SELECT COUNT(*) FROM resultados")->fetchColumn()];

foreach ($fuentes as $f) {
    $stats[$f] = $pdo->query("SELECT COUNT(*) FROM resultados WHERE fuente_id = (SELECT id FROM fuentes WHERE nombre_corto = '$f')")->fetchColumn();
}
$stats['ultima'] = $pdo->query("SELECT MAX(fecha_deteccion) FROM resultados")->fetchColumn();

// Contadores por estado
$contadores = [
    'pendientes' => $pdo->query("SELECT COUNT(*) FROM resultados WHERE estado_usuario = 0")->fetchColumn(),
    'interesantes' => $pdo->query("SELECT COUNT(*) FROM resultados WHERE estado_usuario = 1")->fetchColumn(),
    'descartados' => $pdo->query("SELECT COUNT(*) FROM resultados WHERE estado_usuario = 2")->fetchColumn()
];

// Parámetros de filtro
$fuente = $_GET['fuente'] ?? '';
$busqueda = $_GET['busqueda'] ?? '1';
$dias = (int)($_GET['dias'] ?? 30);
$estadoFiltro = isset($_GET['estado']) && $_GET['estado'] !== '' ? (int)$_GET['estado'] : null;
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

$totalStmt = $pdo->prepare("SELECT COUNT(*) FROM resultados $whereClause");
$totalStmt->execute($params);
$total = $totalStmt->fetchColumn();

$stmt = $pdo->prepare("
    SELECT r.*, f.nombre_corto as fuente_nombre, f.nombre as fuente_nombre_completo
    FROM resultados r
    JOIN fuentes f ON r.fuente_id = f.id
    $whereClause
    ORDER BY r.fecha_publicacion DESC, r.relevancia DESC
    LIMIT $limit OFFSET $offset
");
$stmt->execute($params);
$resultados = $stmt->fetchAll();

$busquedas = $pdo->query("SELECT id, nombre FROM busquedas ORDER BY nombre")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🎬 Lumi·Nova · Buscador de oportunidades</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Fuente Inter (opcional) -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Estilos personalizados -->
    <link rel="stylesheet" href="css/modern.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>

<body class="bg-gray-50 text-gray-800 antialiased">

    <!-- Navbar minimalista -->
    <nav class="bg-white border-b border-gray-200 sticky top-0 z-50 backdrop-blur-sm bg-white/80">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center space-x-2">
                    <span class="text-2xl">🎬</span>
                    <span class="font-semibold text-lg text-gray-800">Lumi·Nova</span>
                    <span class="text-xs text-gray-400 ml-2">oportunidades</span>
                </div>
                <div class="flex items-center space-x-4">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <span class="text-sm text-gray-600">👤 <?= htmlspecialchars($_SESSION['user_nombre'] ?? 'Usuario') ?></span>
                        <a href="busquedas.php" class="text-sm text-gray-600 hover:text-gray-900 transition">⚙️ Mis búsquedas</a>
                        <a href="perfil.php" class="text-sm text-gray-600 hover:text-gray-900 transition">✏️ Perfil</a>
                        <a href="logout.php" class="text-sm text-gray-600 hover:text-gray-900 transition">🚪 Salir</a>
                    <?php else: ?>
                        <a href="login.php" class="text-sm text-gray-600 hover:text-gray-900 transition">🔐 Iniciar sesión</a>
                        <a href="register.php" class="text-sm text-gray-600 hover:text-gray-900 transition">📝 Registrarse</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        <!-- Mensajes -->
        <?php if (isset($mensaje)): ?>
            <div class="mb-6"><?= $mensaje ?></div>
        <?php endif; ?>

        <!-- Cabecera con estadísticas rápidas -->
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-4 mb-8">
            <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 animate-fadeIn">
                <div class="text-2xl font-bold text-gray-800"><?= $stats['total'] ?></div>
                <div class="text-xs text-gray-400 uppercase tracking-wider">Total</div>
            </div>
            <?php foreach (['doge', 'boe', 'bopb', 'diba', 'zaragoza', 'csic', 'bdns', 'placsp'] as $f): ?>
                <?php if ($stats[$f] > 0): ?>
                    <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 animate-fadeIn">
                        <div class="text-lg font-semibold text-gray-800"><?= $stats[$f] ?></div>
                        <div class="text-xs text-gray-400 uppercase tracking-wider"><?= strtoupper($f) ?></div>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
            <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 animate-fadeIn">
                <div class="text-sm text-gray-600"><?= $stats['ultima'] ? date('d/m/Y', strtotime($stats['ultima'])) : '-' ?></div>
                <div class="text-xs text-gray-400 uppercase tracking-wider">última</div>
            </div>
        </div>

        <!-- Contadores de estado (3 tarjetas horizontales) -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
            <div class="bg-white rounded-xl p-5 shadow-sm border-l-4 border-gray-400 flex justify-between items-center animate-fadeIn">
                <div>
                    <div class="text-3xl font-bold text-gray-700"><?= $contadores['pendientes'] ?></div>
                    <div class="text-sm text-gray-500">⏳ Pendientes</div>
                </div>
                <div class="text-4xl opacity-20">⏳</div>
            </div>
            <div class="bg-white rounded-xl p-5 shadow-sm border-l-4 border-green-400 flex justify-between items-center animate-fadeIn">
                <div>
                    <div class="text-3xl font-bold text-green-600"><?= $contadores['interesantes'] ?></div>
                    <div class="text-sm text-gray-500">⭐ Interesantes</div>
                </div>
                <div class="text-4xl opacity-20">⭐</div>
            </div>
            <div class="bg-white rounded-xl p-5 shadow-sm border-l-4 border-red-400 flex justify-between items-center animate-fadeIn">
                <div>
                    <div class="text-3xl font-bold text-red-500"><?= $contadores['descartados'] ?></div>
                    <div class="text-sm text-gray-500">❌ Descartados</div>
                </div>
                <div class="text-4xl opacity-20">❌</div>
            </div>
        </div>

        <!-- Panel de acciones rápidas (botones de ejecución) -->
        <div class="bg-white rounded-xl p-6 shadow-sm mb-8 border border-gray-100">
            <h2 class="text-sm font-medium text-gray-400 uppercase tracking-wider mb-4">⚡ Acciones rápidas</h2>
            <div class="flex flex-wrap gap-2">
                <form method="POST" class="flex flex-wrap gap-2">
                    <?php foreach (['doge', 'boe', 'bopb', 'diba', 'zaragoza', 'csic', 'bdns', 'placsp'] as $f): ?>
                        <button type="submit" name="ejecutar_<?= $f ?>" class="px-4 py-2 bg-gray-50 hover:bg-gray-100 text-gray-700 text-sm rounded-lg border border-gray-200 transition shadow-sm">
                            🔄 <?= strtoupper($f) ?>
                        </button>
                    <?php endforeach; ?>
                </form>
            </div>
        </div>

        <!-- Filtros -->
        <div class="bg-white rounded-xl p-6 shadow-sm mb-8 border border-gray-100">
            <form method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                <select name="busqueda" class="rounded-lg border-gray-200 bg-gray-50 text-sm">
                    <option value="">Todas las búsquedas</option>
                    <?php foreach ($busquedas as $b): ?>
                        <option value="<?= $b['id'] ?>" <?= $busqueda == $b['id'] ? 'selected' : '' ?>><?= htmlspecialchars($b['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="fuente" class="rounded-lg border-gray-200 bg-gray-50 text-sm">
                    <option value="">Todas las fuentes</option>
                    <?php foreach (['doge', 'boe', 'bopb', 'diba', 'zaragoza', 'csic', 'bdns', 'placsp'] as $f): ?>
                        <option value="<?= $f ?>" <?= $fuente == $f ? 'selected' : '' ?>><?= strtoupper($f) ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="dias" class="rounded-lg border-gray-200 bg-gray-50 text-sm">
                    <option value="7" <?= $dias == 7 ? 'selected' : '' ?>>7 días</option>
                    <option value="15" <?= $dias == 15 ? 'selected' : '' ?>>15 días</option>
                    <option value="30" <?= $dias == 30 ? 'selected' : '' ?>>30 días</option>
                    <option value="90" <?= $dias == 90 ? 'selected' : '' ?>>90 días</option>
                    <option value="0" <?= $dias == 0 ? 'selected' : '' ?>>Todos</option>
                </select>
                <select name="estado" class="rounded-lg border-gray-200 bg-gray-50 text-sm">
                    <option value="">Todos los estados</option>
                    <option value="0" <?= $estadoFiltro === 0 ? 'selected' : '' ?>>⏳ Pendiente</option>
                    <option value="1" <?= $estadoFiltro === 1 ? 'selected' : '' ?>>⭐ Interesante</option>
                    <option value="2" <?= $estadoFiltro === 2 ? 'selected' : '' ?>>❌ Descartado</option>
                </select>
                <div class="flex gap-2">
                    <button type="submit" class="flex-1 bg-blue-500 hover:bg-blue-600 text-white rounded-lg px-4 py-2 text-sm transition shadow">Filtrar</button>
                    <a href="?" class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg px-4 py-2 text-sm text-center transition shadow">Limpiar</a>
                </div>
            </form>
        </div>

        <!-- Resultados -->
        <div class="mb-4 flex justify-between items-center">
            <h2 class="text-lg font-medium text-gray-700">📋 Resultados <span class="text-gray-400 text-sm ml-2"><?= $total ?> encontrados</span></h2>
            <?php if ($total > $limit): ?>
                <div class="flex gap-2 text-sm">
                    <?php for ($i = 1; $i <= ceil($total / $limit); $i++): ?>
                        <a href="?page=<?= $i ?>&fuente=<?= urlencode($fuente) ?>&busqueda=<?= urlencode($busqueda) ?>&dias=<?= $dias ?><?= $estadoFiltro !== null ? '&estado=' . $estadoFiltro : '' ?>" class="px-3 py-1 rounded border <?= $i == $page ? 'bg-blue-500 text-white border-blue-500' : 'bg-white text-gray-700 border-gray-200 hover:bg-gray-50' ?> transition"><?= $i ?></a>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Grid de tarjetas -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($resultados as $r):
                $keywords = json_decode($r['palabras_coincidentes'], true) ?? [];
                $estadoActual = (int)($r['estado_usuario'] ?? 0);
                $estadoColor = ['#6b7280', '#10b981', '#ef4444'][$estadoActual];
                $estadoTexto = ['pendiente', 'interesante', 'descartado'][$estadoActual];
                $organismo = htmlspecialchars($r['organismo'] ?: 'No especificado');
            ?>
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden card-hover animate-fadeIn relative group">
                    <!-- Estado visual en esquina superior derecha -->
                    <div class="absolute top-3 right-3 z-10">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium" style="background: <?= $estadoColor ?>15; color: <?= $estadoColor ?>;">
                            <?= $estadoTexto ?>
                        </span>
                    </div>
                    <div class="p-5">
                        <!-- Badges de fuente y tipo -->
                        <div class="flex flex-wrap gap-2 mb-3">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                <?= strtoupper($r['fuente_nombre']) ?>
                            </span>
                            <?php if (!empty($r['codigo_bdns'])): ?>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-50 text-amber-800">
                                    BDNS: <?= $r['codigo_bdns'] ?>
                                </span>
                            <?php endif; ?>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                🔑 <?= count($keywords) ?>
                            </span>
                        </div>
                        <!-- Título enlace a detalle -->
                        <h3 class="text-base font-medium text-gray-800 mb-2 line-clamp-2">
                            <a href="detalle.php?id=<?= $r['id'] ?>" class="hover:text-blue-600 transition"><?= htmlspecialchars($r['titulo']) ?></a>
                        </h3>
                        <!-- Organismo y presupuesto -->
                        <div class="text-sm text-gray-500 mb-2 flex items-center gap-2">
                            <span>🏛️ <?= mb_substr($organismo, 0, 40) ?><?= strlen($organismo) > 40 ? '…' : '' ?></span>
                            <?php if ($r['presupuesto']): ?>
                                <span>💰 <?= number_format($r['presupuesto'], 0) ?> €</span>
                            <?php endif; ?>
                        </div>
                        <!-- Fechas -->
                        <div class="text-xs text-gray-400 flex gap-3 mb-3">
                            <span>📅 <?= $r['fecha_publicacion'] ? date('d/m/Y', strtotime($r['fecha_publicacion'])) : 'N/D' ?></span>
                            <span>🔍 <?= isset($r['ultima_deteccion']) ? date('d/m/Y', strtotime($r['ultima_deteccion'])) : 'N/D' ?></span>
                        </div>
                        <!-- Keywords extra (si hay) -->
                        <?php if (!empty($keywords)): ?>
                            <div class="flex flex-wrap gap-1 mb-4">
                                <?php foreach (array_slice($keywords, 0, 3) as $kw): ?>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs bg-gray-100 text-gray-600"><?= htmlspecialchars($kw) ?></span>
                                <?php endforeach; ?>
                                <?php if (count($keywords) > 3): ?>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs bg-gray-100 text-gray-400">+<?= count($keywords) - 3 ?></span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <!-- Botones de estado -->

                        <div class="flex gap-1 pt-3 border-t border-gray-100">
                            <button onclick="cambiarEstado(<?= $r['id'] ?>, 0)" class="flex-1 text-xs px-2 py-1.5 rounded transition" style="background: <?= $estadoActual == 0 ? '#4f46e5' : '#f3f4f6' ?>; color: <?= $estadoActual == 0 ? 'white' : '#374151' ?>;">⏳ Pendiente</button>
                            <button onclick="cambiarEstado(<?= $r['id'] ?>, 1)" class="flex-1 text-xs px-2 py-1.5 rounded transition" style="background: <?= $estadoActual == 1 ? '#10b981' : '#f3f4f6' ?>; color: <?= $estadoActual == 1 ? 'white' : '#374151' ?>;">⭐ Interesante</button>
                            <button onclick="cambiarEstado(<?= $r['id'] ?>, 2)" class="flex-1 text-xs px-2 py-1.5 rounded transition" style="background: <?= $estadoActual == 2 ? '#ef4444' : '#f3f4f6' ?>; color: <?= $estadoActual == 2 ? 'white' : '#374151' ?>;">❌ Descartado</button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            <?php if (empty($resultados)): ?>
                <div class="col-span-full text-center py-12 bg-white rounded-xl border border-gray-100">
                    <div class="text-5xl mb-3">📭</div>
                    <p class="text-gray-500">No hay resultados que mostrar</p>
                    <p class="text-sm text-gray-400 mt-1">Ejecuta alguna fuente desde acciones rápidas</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Paginación inferior-->

        <?php if ($total > $limit): ?>
            <?php $totalPages = ceil($total / $limit); ?>
            <div class="mt-8 flex justify-center">
                <div class="flex gap-2">
                    <!-- Botón anterior -->
                    <?php if ($page > 1): ?>
                        <a href="?page=<?= $page - 1 ?>&fuente=<?= urlencode($fuente) ?>&busqueda=<?= urlencode($busqueda) ?>&dias=<?= $dias ?><?= $estadoFiltro !== null ? '&estado=' . $estadoFiltro : '' ?>" class="px-4 py-2 rounded border bg-white text-gray-700 border-gray-200 hover:bg-gray-50 transition">←</a>
                    <?php endif; ?>

                    <!-- Números de página -->
                    <?php for ($i = 1; $i <= $totalPages; $i++):
                        // Mostrar solo páginas cercanas (para no saturar)
                        if ($i == 1 || $i == $totalPages || ($i >= $page - 2 && $i <= $page + 2)): ?>
                            <a href="?page=<?= $i ?>&fuente=<?= urlencode($fuente) ?>&busqueda=<?= urlencode($busqueda) ?>&dias=<?= $dias ?><?= $estadoFiltro !== null ? '&estado=' . $estadoFiltro : '' ?>" class="px-4 py-2 rounded border <?= $i == $page ? 'bg-blue-500 text-white border-blue-500' : 'bg-white text-gray-700 border-gray-200 hover:bg-gray-50' ?> transition"><?= $i ?></a>
                        <?php elseif ($i == $page - 3 || $i == $page + 3): ?>
                            <span class="px-4 py-2 text-gray-400">...</span>
                        <?php endif; ?>
                    <?php endfor; ?>

                    <!-- Botón siguiente -->
                    <?php if ($page < $totalPages): ?>
                        <a href="?page=<?= $page + 1 ?>&fuente=<?= urlencode($fuente) ?>&busqueda=<?= urlencode($busqueda) ?>&dias=<?= $dias ?><?= $estadoFiltro !== null ? '&estado=' . $estadoFiltro : '' ?>" class="px-4 py-2 rounded border bg-white text-gray-700 border-gray-200 hover:bg-gray-50 transition">→</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

    </main>

    <script src="js/app.js"></script>
</body>

</html>