<?php
// Fuentes/CSIC.php
// Consejo Superior de Investigaciones Científicas - Búsqueda en Plataforma

require_once __DIR__ . '/../Core/Database.php';

class CSIC
{
    private $pdo;
    private $fuenteId;
    private $apiBase = 'https://contrataciondelestado.es';

    private $cpvServicios = [
        '92100000',
        '92111000',
        '92112000',
        '92113000',
        '92220000',
        '79341000',
        '79961000'
    ];

    public function __construct()
    {
        $this->pdo = Database::getInstance();
        $this->fuenteId = $this->obtenerFuenteId();
    }

    private function obtenerFuenteId()
    {
        try {
            $stmt = $this->pdo->prepare("SELECT id FROM fuentes WHERE nombre_corto = 'csic'");
            $stmt->execute();
            $result = $stmt->fetch();

            if (!$result) {
                $stmt = $this->pdo->prepare("INSERT INTO fuentes (nombre, nombre_corto, tipo, url_base, script_asociado) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([
                    'CSIC - Contratación',
                    'csic',
                    'licitacion',
                    'https://contrataciondelestado.es',
                    'CSIC.php'
                ]);
                return $this->pdo->lastInsertId();
            }
            return $result['id'];
        } catch (PDOException $e) {
            die("Error con fuente CSIC: " . $e->getMessage());
        }
    }

    public function ejecutar($busquedaId, $dias = 365)
    {
        echo "\n🔍 Buscando en CSIC (Plataforma de Contratación)...\n";

        try {
            $busqueda = $this->getBusqueda($busquedaId);
            if (!$busqueda) throw new Exception("Búsqueda no encontrada");

            $fechaDesde = date('Y-m-d', strtotime("-$dias days"));

            // Construir URL de búsqueda
            $params = [
                'organismo' => 'CSIC',
                'tipo' => 'licitacion',
                'fechaDesde' => $fechaDesde,
                'formato' => 'json'
            ];

            $url = $this->apiBase . '/wps/portal/plataforma/buscador/buscador?' . http_build_query($params);

            echo "📡 Consultando: $url\n";

            $jsonData = $this->callAPI($url);
            if (!$jsonData) {
                throw new Exception("No se pudo obtener datos");
            }

            $datos = json_decode($jsonData, true);

            // La estructura puede variar, necesitamos adaptarla
            $items = $datos['licitaciones'] ?? $datos['resultados'] ?? [];

            echo "📊 Total licitaciones: " . count($items) . "\n";

            $palabrasUsuario = array_map('trim', explode(',', $busqueda['palabras_clave']));
            $encontrados = 0;

            foreach ($items as $item) {
                $titulo = $item['titulo'] ?? $item['objeto'] ?? 'Sin título';
                $cpv = $item['cpv'] ?? '';

                // Verificar si es un servicio audiovisual
                $esServicio = false;
                foreach ($this->cpvServicios as $cpvPrioritario) {
                    if (strpos($cpv, $cpvPrioritario) !== false) {
                        $esServicio = true;
                        break;
                    }
                }

                // Buscar palabras clave
                $textoCompleto = $titulo . ' ' . ($item['descripcion'] ?? '');
                $keywordsEncontradas = $this->buscarKeywords($textoCompleto, $palabrasUsuario);

                if ($esServicio || !empty($keywordsEncontradas)) {
                    if ($esServicio && empty($keywordsEncontradas)) {
                        $keywordsEncontradas = ['CPV servicios'];
                    }

                    if ($this->guardarResultado(
                        $busquedaId,
                        $titulo,
                        $item['descripcion'] ?? '',
                        'CSIC',
                        $item['importe'] ?? null,
                        $item['fechaPublicacion'] ?? date('Y-m-d'),
                        $item['fechaPresentacion'] ?? null,
                        $item['url'] ?? $item['enlace'] ?? '#',
                        $keywordsEncontradas
                    )) {
                        $encontrados++;
                        echo "   ✅ " . mb_substr($titulo, 0, 60) . "...\n";
                    }
                }
            }

            echo "✅ CSIC procesado: $encontrados resultados\n";
            return $encontrados;
        } catch (Exception $e) {
            echo "❌ Error: " . $e->getMessage() . "\n";
            return 0;
        }
    }

    private function callAPI($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);

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
        echo "✅ CSIC configurado correctamente\n";
        echo "📁 Fuente ID: " . $this->fuenteId . "\n";
        return true;
    }
}
