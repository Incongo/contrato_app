<?php
// Fuentes/TED.php
// Tenders Electronic Daily - API v3 con query DSL

require_once __DIR__ . '/../Core/Database.php';

class TED
{
    private $pdo;
    private $fuenteId;

    // CPVs de servicios audiovisuales
    private $cpvPrioritarios = [
        '92100000', // Servicios cinematográficos y de vídeo
        '92111000', // Servicios de producción de películas
        '92112000', // Servicios de producción de vídeo
        '92113000', // Servicios de postproducción
        '92220000', // Servicios de televisión
        '79341000', // Servicios de publicidad
        '79961000'  // Servicios de fotografía
    ];

    public function __construct()
    {
        $this->pdo = Database::getInstance();
        $this->fuenteId = $this->obtenerFuenteId();
    }

    private function obtenerFuenteId()
    {
        try {
            $stmt = $this->pdo->prepare("SELECT id FROM fuentes WHERE nombre_corto = 'ted'");
            $stmt->execute();
            $result = $stmt->fetch();

            if (!$result) {
                $stmt = $this->pdo->prepare("INSERT INTO fuentes (nombre, nombre_corto, tipo, url_base, script_asociado) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([
                    'TED - Unión Europea',
                    'ted',
                    'licitacion',
                    'https://ted.europa.eu',
                    'TED.php'
                ]);
                return $this->pdo->lastInsertId();
            }
            return $result['id'];
        } catch (PDOException $e) {
            die("Error con fuente TED: " . $e->getMessage());
        }
    }

    public function ejecutar($busquedaId, $dias = 30)
    {
        echo "\n🔍 Buscando en TED (Unión Europea)...\n";

        try {
            $busqueda = $this->getBusqueda($busquedaId);
            if (!$busqueda) throw new Exception("Búsqueda no encontrada");

            $palabrasUsuario = array_map('trim', explode(',', $busqueda['palabras_clave']));
            $encontrados = 0;

            // ============================================================
            // CONSTRUCCIÓN DE LA CONSULTA DSL (según tu corrección)
            // ============================================================

            // 1. Fecha en formato simple YYYY-MM-DD
            $fechaDesde = date('Y-m-d', strtotime("-$dias days"));

            // 2. Lista de CPVs para la query
            $cpvList = implode(',', $this->cpvPrioritarios);

            // 3. Construir la query DSL
            $query = "cpv:($cpvList) AND publicationDate>$fechaDesde AND buyerCountry:ES";

            echo "📡 Query: $query\n";

            // 4. Datos para el POST (formato correcto)
            $postData = [
                'query' => $query,
                'page' => 0,
                'size' => 100,
                'sort' => 'publicationDate:desc'
            ];

            $url = 'https://api.ted.europa.eu/v3/notices/search';
            $jsonData = $this->callAPI($url, $postData);

            if (!$jsonData) {
                throw new Exception("No se pudo obtener datos");
            }

            $datos = json_decode($jsonData, true);
            $items = $datos['notices'] ?? [];

            echo "   → " . count($items) . " licitaciones\n";

            foreach ($items as $item) {
                $titulo = $item['title'] ?? 'Sin título';

                // Construir URL del detalle
                $noticeId = $item['noticeId'] ?? $item['id'] ?? null;
                $urlDetalle = $noticeId ? "https://ted.europa.eu/udl?uri=TED:NOTICE:$noticeId:TEXT:ES:HTML" : '#';

                $descripcion = $item['description'] ?? $item['summary'] ?? '';
                $fechaPub = $item['publicationDate'] ?? $item['date'] ?? date('Y-m-d');

                // Extraer organismo (buyer)
                $organismo = 'Unión Europea';
                if (isset($item['buyer']['name'])) {
                    $organismo = $item['buyer']['name'];
                }

                $textoCompleto = $titulo . ' ' . $descripcion . ' ' . $organismo;
                $keywordsEncontradas = $this->buscarKeywords($textoCompleto, $palabrasUsuario);

                if (!empty($keywordsEncontradas)) {
                    if ($this->guardarResultado(
                        $busquedaId,
                        $titulo,
                        $descripcion,
                        $organismo,
                        null,
                        date('Y-m-d', strtotime($fechaPub)),
                        null,
                        $urlDetalle,
                        $keywordsEncontradas
                    )) {
                        $encontrados++;
                        echo "     ✅ " . mb_substr($titulo, 0, 60) . "...\n";
                    }
                }
            }

            echo "\n✅ TED procesado: $encontrados resultados nuevos\n";
            return $encontrados;
        } catch (Exception $e) {
            echo "❌ Error: " . $e->getMessage() . "\n";
            return 0;
        }
    }

    private function callAPI($url, $postData = null)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');

        $headers = ['Accept: application/json'];

        if ($postData) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
            $headers[] = 'Content-Type: application/json';
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || !$response) {
            echo "   HTTP $httpCode\n";
            return null;
        }
        return $response;
    }

    private function buscarKeywords($texto, $keywords)
    {
        $encontradas = [];
        $textoLower = mb_strtolower($texto, 'UTF-8');

        foreach ($keywords as $keyword) {
            $keyword = trim($keyword);
            if (empty($keyword)) continue;

            $keywordLower = mb_strtolower($keyword, 'UTF-8');
            if (strpos($textoLower, $keywordLower) !== false) {
                $encontradas[] = $keyword;
            }
        }
        return $encontradas;
    }

    private function guardarResultado($busquedaId, $titulo, $descripcion, $organismo, $presupuesto, $fechaPub, $fechaLim, $url, $keywords)
    {
        try {
            $stmt = $this->pdo->prepare("SELECT id FROM resultados WHERE url_detalle = ? AND busqueda_id = ?");
            $stmt->execute([$url, $busquedaId]);
            if ($stmt->fetch()) return false;

            $stmt = $this->pdo->prepare("
                INSERT INTO resultados (
                    busqueda_id, fuente_id, titulo, descripcion_corta, organismo,
                    presupuesto, fecha_publicacion, fecha_limite, url_detalle,
                    palabras_coincidentes, relevancia
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $busquedaId,
                $this->fuenteId,
                $titulo,
                mb_substr($descripcion, 0, 500, 'UTF-8'),
                $organismo,
                $presupuesto,
                $fechaPub,
                $fechaLim,
                $url,
                json_encode($keywords, JSON_UNESCAPED_UNICODE),
                count($keywords)
            ]);

            return true;
        } catch (PDOException $e) {
            error_log("Error guardando: " . $e->getMessage());
            return false;
        }
    }

    private function getBusqueda($busquedaId)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM busquedas WHERE id = ? AND activo = 1");
        $stmt->execute([$busquedaId]);
        return $stmt->fetch();
    }

    public function probar()
    {
        echo "✅ TED configurado correctamente\n";
        echo "📁 Fuente ID: " . $this->fuenteId . "\n";
        echo "📡 CPVs a consultar: " . implode(', ', $this->cpvPrioritarios) . "\n";
        echo "⚠️ Límites: 600 visualizaciones/6min, 700 requests/minuto\n";
        return true;
    }
}
