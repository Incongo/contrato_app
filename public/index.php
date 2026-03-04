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
        $dias = (int)($_GET['dias'] ?? 30);
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

        // NUEVO FILTRO POR DÍAS
        if ($dias > 0) {
            $where[] = "fecha_publicacion >= DATE_SUB(CURDATE(), INTERVAL $dias DAY)";
            // No añadimos parámetro porque es un número
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

                <!-- NUEVO FILTRO DE DÍAS -->
                <select name="dias">
                    <option value="7" <?= $dias == 7 ? 'selected' : '' ?>>Últimos 7 días</option>
                    <option value="15" <?= $dias == 15 ? 'selected' : '' ?>>Últimos 15 días</option>
                    <option value="30" <?= $dias == 30 ? 'selected' : '' ?>>Últimos 30 días</option>
                    <option value="90" <?= $dias == 90 ? 'selected' : '' ?>>Últimos 90 días</option>
                    <option value="0" <?= $dias == 0 ? 'selected' : '' ?>>Todos</option>
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
                            <span>Pub: <?= $r['fecha_publicacion'] ? date('d/m/Y', strtotime($r['fecha_publicacion'])) : 'N/D' ?></span>
                        </div>
                        <div class="meta-item">
                            <span>🔍</span>
                            <span>Det: <?= isset($r['ultima_deteccion']) ? date('d/m/Y', strtotime($r['ultima_deteccion'])) : 'N/D' ?></span>
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
                    <a href="?page=<?= $i ?>&fuente=<?= urlencode($fuente) ?>&busqueda=<?= urlencode($busqueda) ?>&dias=<?= $dias ?>">
                        <button class="<?= $i == $page ? 'active' : '' ?>"><?= $i ?></button>
                    </a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    </div>
</body>

</html>