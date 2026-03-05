<?php
// public/detalle.php
// Muestra el detalle de una convocatoria

require_once __DIR__ . '/../Core/Database.php';
require_once __DIR__ . '/../Core/Auth.php';
require_once __DIR__ . '/../Core/AuthMiddleware.php';

session_start();
AuthMiddleware::protegerPagina();

$pdo = Database::getInstance();
$id = $_GET['id'] ?? 0;

if (!$id) {
    header('Location: index.php');
    exit;
}

// Obtener el detalle
$stmt = $pdo->prepare("
    SELECT r.*, f.nombre_corto as fuente_nombre, f.nombre as fuente_nombre_completo
    FROM resultados r
    JOIN fuentes f ON r.fuente_id = f.id
    WHERE r.id = ?
");
$stmt->execute([$id]);
$resultado = $stmt->fetch();

if (!$resultado) {
    header('Location: index.php');
    exit;
}

// Decodificar palabras clave
$keywords = json_decode($resultado['palabras_coincidentes'], true) ?? [];

// Formatear presupuesto
$presupuesto = $resultado['presupuesto'] ? number_format($resultado['presupuesto'], 2) . ' €' : 'No especificado';

// Fechas
$fechaPub = $resultado['fecha_publicacion'] ? date('d/m/Y', strtotime($resultado['fecha_publicacion'])) : 'No disponible';
$fechaLim = $resultado['fecha_limite'] ? date('d/m/Y', strtotime($resultado['fecha_limite'])) : 'No especificado';
$fechaDet = date('d/m/Y H:i', strtotime($resultado['fecha_deteccion']));

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle - <?= htmlspecialchars(mb_substr($resultado['titulo'], 0, 50)) ?></title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .detalle-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 2rem;
            background: white;
            border-radius: 1rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .detalle-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #e5e7eb;
        }
        .detalle-titulo {
            font-size: 1.5rem;
            color: #1f2937;
            line-height: 1.4;
        }
        .badge-fuente {
            padding: 0.5rem 1rem;
            border-radius: 2rem;
            font-size: 0.9rem;
            font-weight: 600;
        }
        .badge-fuente.doge { background: #dcfce7; color: #166534; }
        .badge-fuente.boe { background: #dbeafe; color: #1e40af; }
        .badge-fuente.bopb { background: #fef3c7; color: #92400e; }
        .badge-fuente.diba { background: #e0e7ff; color: #3730a3; }
        .badge-fuente.zaragoza { background: #fce7f3; color: #9d174d; }
        .badge-fuente.csic { background: #d1fae5; color: #065f46; }
        .badge-fuente.bdns { background: #ffedd5; color: #9a3412; }
        
        .detalle-seccion {
            margin-bottom: 2rem;
        }
        .detalle-seccion h3 {
            font-size: 1.1rem;
            color: #374151;
            margin-bottom: 0.5rem;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 0.5rem;
        }
        .detalle-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
            background: #f9fafb;
            padding: 1.5rem;
            border-radius: 0.5rem;
        }
        .info-item {
            display: flex;
            flex-direction: column;
        }
        .info-label {
            font-size: 0.8rem;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .info-value {
            font-size: 1.1rem;
            font-weight: 500;
            color: #1f2937;
        }
        .detalle-descripcion {
            background: #f9fafb;
            padding: 1.5rem;
            border-radius: 0.5rem;
            line-height: 1.6;
            white-space: pre-wrap;
        }
        .keywords-list {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-top: 0.5rem;
        }
        .keyword-tag {
            background: #e0e7ff;
            color: #3730a3;
            padding: 0.25rem 0.75rem;
            border-radius: 1rem;
            font-size: 0.85rem;
        }
        .botones-accion {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }
        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.2s;
        }
        .btn-primary {
            background: #4f46e5;
            color: white;
        }
        .btn-primary:hover {
            background: #4338ca;
        }
        .btn-secondary {
            background: #6b7280;
            color: white;
        }
        .btn-secondary:hover {
            background: #4b5563;
        }
        .btn-outline {
            border: 1px solid #e5e7eb;
            color: #374151;
        }
        .btn-outline:hover {
            background: #f3f4f6;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <a href="index.php" class="btn btn-secondary">← Volver</a>
        </div>
    </nav>

    <div class="detalle-container">
        <div class="detalle-header">
            <h1 class="detalle-titulo"><?= htmlspecialchars($resultado['titulo']) ?></h1>
            <span class="badge-fuente <?= $resultado['fuente_nombre'] ?>">
                <?= strtoupper($resultado['fuente_nombre']) ?>
            </span>
        </div>

        <div class="detalle-seccion">
            <h3>📋 Información general</h3>
            <div class="detalle-info">
                <div class="info-item">
                    <span class="info-label">Organismo</span>
                    <span class="info-value"><?= htmlspecialchars($resultado['organismo'] ?: 'No especificado') ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Presupuesto</span>
                    <span class="info-value"><?= $presupuesto ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Fecha publicación</span>
                    <span class="info-value"><?= $fechaPub ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Fecha límite</span>
                    <span class="info-value"><?= $fechaLim ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Fecha detección</span>
                    <span class="info-value"><?= $fechaDet ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Fuente</span>
                    <span class="info-value"><?= htmlspecialchars($resultado['fuente_nombre_completo']) ?></span>
                </div>
            </div>
        </div>

        <?php if (!empty($resultado['descripcion_corta'])): ?>
        <div class="detalle-seccion">
            <h3>📄 Descripción</h3>
            <div class="detalle-descripcion">
                <?= nl2br(htmlspecialchars($resultado['descripcion_corta'])) ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if (!empty($keywords)): ?>
        <div class="detalle-seccion">
            <h3>🔑 Palabras clave detectadas</h3>
            <div class="keywords-list">
                <?php foreach ($keywords as $kw): ?>
                    <span class="keyword-tag"><?= htmlspecialchars($kw) ?></span>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <div class="botones-accion">
            <a href="<?= htmlspecialchars($resultado['url_detalle']) ?>" target="_blank" class="btn btn-primary">
                🔗 Ver original
            </a>
            <a href="index.php" class="btn btn-outline">← Volver</a>
        </div>
    </div>
</body>
</html>