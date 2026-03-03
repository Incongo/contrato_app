<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🎬 Buscador de Licitaciones Audiovisuales + Ciencia</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            background: #f3f4f6;
            color: #1f2937;
            line-height: 1.5;
        }

        .navbar {
            background: white;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            padding: 1rem 2rem;
            margin-bottom: 2rem;
        }

        .navbar h1 {
            font-size: 1.5rem;
            color: #4f46e5;
        }

        .navbar .subtitle {
            color: #6b7280;
            font-size: 0.9rem;
            margin-top: 0.25rem;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 2rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            border-radius: 0.5rem;
            padding: 1.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .stat-card h3 {
            font-size: 2rem;
            color: #4f46e5;
            margin-bottom: 0.5rem;
        }

        .stat-card p {
            color: #6b7280;
            font-size: 0.9rem;
        }

        .filters {
            background: white;
            border-radius: 0.5rem;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .filter-group {
            display: flex;
            gap: 1rem;
            align-items: center;
            flex-wrap: wrap;
        }

        .filter-group select,
        .filter-group button {
            padding: 0.5rem 1rem;
            border: 1px solid #e5e7eb;
            border-radius: 0.5rem;
            font-size: 0.9rem;
        }

        .filter-group button {
            background: #4f46e5;
            color: white;
            border: none;
            cursor: pointer;
        }

        .filter-group button:hover {
            background: #4338ca;
        }

        .filter-group button.reset {
            background: #6b7280;
        }

        .filter-group button.reset:hover {
            background: #4b5563;
        }

        .results-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .results-header h2 {
            font-size: 1.25rem;
        }

        .results-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
            gap: 1.5rem;
        }

        .result-card {
            background: white;
            border-radius: 0.75rem;
            padding: 1.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            transition: all 0.2s;
            border-left: 4px solid transparent;
        }

        .result-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .result-card.relevante {
            border-left-color: #10b981;
            /* Verde si tiene keywords de ciencia+av */
        }

        .result-card.poco-relevante {
            border-left-color: #6b7280;
            /* Gris si son de personal */
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }

        .badge-group {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 1rem;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .badge.boe {
            background: #dbeafe;
            color: #1e40af;
        }

        .badge.doge {
            background: #dcfce7;
            color: #166534;
        }

        .badge.relevante {
            background: #d1fae5;
            color: #065f46;
        }

        .badge.no-relevante {
            background: #f3f4f6;
            color: #4b5563;
        }

        .badge.keyword {
            background: #e0e7ff;
            color: #3730a3;
            font-size: 0.7rem;
        }

        .relevancia-tag {
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            padding: 0.25rem 0.75rem;
            border-radius: 1rem;
        }

        .relevancia-alta {
            background: #fee2e2;
            color: #b91c1c;
        }

        .relevancia-media {
            background: #fef3c7;
            color: #92400e;
        }

        .relevancia-baja {
            background: #e0e7ff;
            color: #3730a3;
        }

        .card-title {
            font-size: 1rem;
            margin-bottom: 0.75rem;
            line-height: 1.5;
        }

        .card-title a {
            color: #1f2937;
            text-decoration: none;
        }

        .card-title a:hover {
            color: #4f46e5;
            text-decoration: underline;
        }

        .card-meta {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.5rem;
            margin-bottom: 1rem;
            font-size: 0.85rem;
            color: #6b7280;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        .keywords-section {
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #e5e7eb;
        }

        .keywords-section h4 {
            font-size: 0.8rem;
            color: #6b7280;
            margin-bottom: 0.5rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .keywords-list {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .keyword-item {
            background: #f3f4f6;
            padding: 0.25rem 0.75rem;
            border-radius: 1rem;
            font-size: 0.75rem;
            color: #374151;
        }

        .keyword-item.ciencia {
            background: #dbeafe;
            color: #1e3a8a;
        }

        .keyword-item.audiovisual {
            background: #fce7f3;
            color: #9d174d;
        }

        .resumen {
            background: #f9fafb;
            border-radius: 0.5rem;
            padding: 1rem;
            margin-top: 1rem;
            font-size: 0.9rem;
        }

        .resumen p {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #4b5563;
        }

        .resumen .valor {
            font-weight: 600;
            color: #1f2937;
        }

        .pagination {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            margin-top: 2rem;
        }

        .pagination button {
            padding: 0.5rem 1rem;
            border: 1px solid #e5e7eb;
            background: white;
            border-radius: 0.5rem;
            cursor: pointer;
        }

        .pagination button:hover {
            background: #f3f4f6;
        }

        .pagination button.active {
            background: #4f46e5;
            color: white;
            border-color: #4f46e5;
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #6b7280;
            grid-column: 1/-1;
        }

        .aviso {
            background: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
            font-size: 0.9rem;
        }

        .acciones {
            display: flex;
            gap: 0.5rem;
            margin-top: 1rem;
        }

        .btn {
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            font-size: 0.9rem;
            text-decoration: none;
            color: white;
            border: none;
            cursor: pointer;
        }

        .btn-primary {
            background: #4f46e5;
        }

        .btn-primary:hover {
            background: #4338ca;
        }

        .btn-secondary {
            background: #6b7280;
        }

        .btn-secondary:hover {
            background: #4b5563;
        }
    </style>
</head>

<body>
    <nav class="navbar">
        <div class="container">
            <h1>🎬 Buscador de Licitaciones Audiovisuales + Ciencia</h1>
            <div class="subtitle">Contratos públicos para divulgación científica, producción audiovisual, streaming y más</div>
        </div>
    </nav>

    <div class="container">
        <?php
        require_once __DIR__ . '/../Core/Database.php';
        $pdo = Database::getInstance();

        // Procesar acciones
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
        }

        // Obtener estadísticas
        $stats = [
            'total' => $pdo->query("SELECT COUNT(*) FROM resultados")->fetchColumn(),
            'doge' => $pdo->query("SELECT COUNT(*) FROM resultados WHERE fuente_id = (SELECT id FROM fuentes WHERE nombre_corto = 'doge')")->fetchColumn(),
            'boe' => $pdo->query("SELECT COUNT(*) FROM resultados WHERE fuente_id = (SELECT id FROM fuentes WHERE nombre_corto = 'boe')")->fetchColumn(),
            'ultima' => $pdo->query("SELECT MAX(fecha_deteccion) FROM resultados")->fetchColumn()
        ];

        // Palabras clave de ciencia y audiovisual (para clasificar)
        $keywordsCiencia = ['divulgación científica', 'investigación', 'ciencia', 'astronomía', 'cambio climático', 'medio ambiente', 'biodiversidad', 'tecnología'];
        $keywordsAV = ['producción audiovisual', 'vídeo', 'streaming', 'documental', 'grabación', 'cobertura', 'contenido multimedia', 'postproducción'];

        // Parámetros de filtro
        $fuente = $_GET['fuente'] ?? '';
        $busqueda = $_GET['busqueda'] ?? '1';
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

        <!-- Estadísticas -->
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
                <h3><?= $stats['ultima'] ? date('d/m/Y', strtotime($stats['ultima'])) : '-' ?></h3>
                <p>Última detección</p>
            </div>
        </div>

        <!-- Acciones rápidas -->
        <div class="filters">
            <form method="POST" class="filter-group" style="justify-content: space-between;">
                <div style="display: flex; gap: 1rem;">
                    <button type="submit" name="ejecutar_doge" class="btn btn-secondary">🔄 Ejecutar DOGE</button>
                    <button type="submit" name="ejecutar_boe" class="btn btn-secondary">🔄 Ejecutar BOE</button>
                </div>
                <div>
                    <a href="?relevantes=1" class="btn btn-primary">🎯 Solo relevantes</a>
                    <a href="?" class="btn btn-secondary" style="margin-left: 0.5rem;">⟲ Quitar filtros</a>
                </div>
            </form>
        </div>

        <!-- Filtros -->
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
                $tieneCiencia = false;
                $tieneAV = false;

                foreach ($keywords as $kw) {
                    $kwLower = strtolower($kw);
                    foreach ($keywordsCiencia as $c) {
                        if (strpos($kwLower, strtolower($c)) !== false) $tieneCiencia = true;
                    }
                    foreach ($keywordsAV as $a) {
                        if (strpos($kwLower, strtolower($a)) !== false) $tieneAV = true;
                    }
                }

                $esRelevante = $tieneCiencia && $tieneAV;
                $claseRelevancia = $esRelevante ? 'relevante' : 'poco-relevante';
            ?>
                <div class="result-card <?= $claseRelevancia ?>">
                    <div class="card-header">
                        <div class="badge-group">
                            <span class="badge <?= $r['fuente_nombre'] ?>">
                                <?= strtoupper($r['fuente_nombre']) ?>
                            </span>
                            <?php if ($esRelevante): ?>
                                <span class="badge relevante">🎯 RELEVANTE</span>
                            <?php else: ?>
                                <span class="badge no-relevante">ℹ️ Informativo</span>
                            <?php endif; ?>
                        </div>
                        <span class="badge keyword"><?= $r['relevancia'] ?> keywords</span>
                    </div>

                    <h3 class="card-title">
                        <a href="<?= htmlspecialchars($r['url_detalle']) ?>" target="_blank">
                            <?= htmlspecialchars(mb_substr($r['titulo'], 0, 100)) ?>...
                        </a>
                    </h3>

                    <div class="card-meta">
                        <div class="meta-item">
                            <span>🏛️</span>
                            <span><?= htmlspecialchars($r['organismo'] ?: 'No especificado') ?></span>
                        </div>
                        <div class="meta-item">
                            <span>📅</span>
                            <span><?= $r['fecha_publicacion'] ? date('d/m/Y', strtotime($r['fecha_publicacion'])) : 'N/D' ?></span>
                        </div>
                        <?php if ($r['presupuesto']): ?>
                            <div class="meta-item">
                                <span>💰</span>
                                <span><?= number_format($r['presupuesto'], 0) ?> €</span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Resumen ejecutivo -->
                    <div class="resumen">
                        <p>
                            <span>🎯</span>
                            <span class="valor"><?= $esRelevante ? 'POSIBLE OPORTUNIDAD' : 'NO RELEVANTE' ?></span>
                        </p>
                        <p style="font-size: 0.85rem; margin-top: 0.5rem; color: #4b5563;">
                            <?= $esRelevante
                                ? 'Coincide con criterios de ciencia Y audiovisual. Revisar.'
                                : 'Parece ser de personal/oposiciones. Probablemente descartar.'
                            ?>
                        </p>
                    </div>

                    <?php if (!empty($keywords)): ?>
                        <div class="keywords-section">
                            <h4>🔑 Palabras clave detectadas</h4>
                            <div class="keywords-list">
                                <?php foreach ($keywords as $kw):
                                    $clase = '';
                                    foreach ($keywordsCiencia as $c) {
                                        if (strpos(strtolower($kw), strtolower($c)) !== false) $clase = 'ciencia';
                                    }
                                    foreach ($keywordsAV as $a) {
                                        if (strpos(strtolower($kw), strtolower($a)) !== false) $clase = 'audiovisual';
                                    }
                                ?>
                                    <span class="keyword-item <?= $clase ?>"><?= htmlspecialchars($kw) ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>

            <?php if (empty($resultados)): ?>
                <div class="empty-state">
                    <p>📭 No hay resultados que mostrar</p>
                    <p style="font-size: 0.9rem; margin-top: 0.5rem;">Ejecuta DOGE o BOE desde los botones superiores</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Paginación -->
        <?php if ($total > $limit): ?>
            <div class="pagination">
                <?php for ($i = 1; $i <= ceil($total / $limit); $i++): ?>
                    <a href="?page=<?= $i ?>&fuente=<?= urlencode($fuente) ?>&busqueda=<?= urlencode($busqueda) ?>">
                        <button class="<?= $i == $page ? 'active' : '' ?>"><?= $i ?></button>
                    </a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    </div>
</body>

</html>