<?php
// Fuentes/PLACSP.php
// Plataforma de Contratación del Sector Público - Feed ATOM oficial

require_once __DIR__ . '/../Core/Database.php';

class PLACSP
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
            $stmt = $this->pdo->prepare("SELECT id FROM fuentes WHERE nombre_corto = 'placsp'");
            $stmt->execute();
            $result = $stmt->fetch();

            if (!$result) {
                $stmt = $this->pdo->prepare("INSERT INTO fuentes (nombre, nombre_corto, tipo, url_base, script_asociado) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([
                    'Plataforma de Contratación',
                    'placsp',
                    'licitacion',
                    'https://contrataciondelsectorpublico.gob.es',
                    'PLACSP.php'
                ]);
                return $this->pdo->lastInsertId();
            }
            return $result['id'];
        } catch (PDOException $e) {
            die("Error con fuente PLACSP: " . $e->getMessage());
        }
    }

    public function ejecutar($busquedaId, $dias = 360)
    {
        echo "\n🔍 Buscando en Plataforma de Contratación (feed ATOM)...\n";

        try {
            $busqueda = $this->getBusqueda($busquedaId);
            if (!$busqueda) throw new Exception("Búsqueda no encontrada");

            $palabrasUsuario = array_map('trim', explode(',', $busqueda['palabras_clave']));
            $encontrados = 0;

            // Calcular fecha límite (últimos X días)
            $fechaLimite = date('Y-m-d', strtotime("-$dias days"));

            // URL del feed ATOM completo
            $url = "https://contrataciondelsectorpublico.gob.es/sindicacion/sindicacion_643/licitacionesPerfilesContratanteCompleto3.atom";

            echo "📡 Consultando feed maestro: $url\n";

            $xmlData = $this->obtenerFeed($url);
            if (!$xmlData) {
                throw new Exception("No se pudo obtener el feed");
            }

            $xml = simplexml_load_string($xmlData, 'SimpleXMLElement', LIBXML_NOCDATA | LIBXML_NOWARNING);
            if (!$xml || !isset($xml->entry)) {
                echo "❌ No hay entradas en el feed\n";
                return 0;
            }

            $items = $xml->entry;
            echo "📊 Total licitaciones en feed: " . count($items) . "\n";

            foreach ($items as $entry) {
                $titulo = (string)$entry->title;
                $link = (string)$entry->link['href'];
                $descripcion = (string)$entry->summary;
                $fechaPub = (string)$entry->updated;

                // Filtrar por fecha
                if (strtotime($fechaPub) < strtotime($fechaLimite)) {
                    continue;
                }

                // Buscar CPV en el contenido
                $contieneCPV = false;
                $contenidoCompleto = $titulo . ' ' . $descripcion;
                foreach ($this->cpvPrioritarios as $cpv) {
                    if (strpos($contenidoCompleto, $cpv) !== false) {
                        $contieneCPV = true;
                        break;
                    }
                }

                // Buscar palabras clave del usuario
                $keywordsEncontradas = $this->buscarKeywords($contenidoCompleto, $palabrasUsuario);

                if ($contieneCPV || !empty($keywordsEncontradas)) {
                    if ($contieneCPV && empty($keywordsEncontradas)) {
                        $keywordsEncontradas = ['CPV prioritario'];
                    }

                    if ($this->guardarResultado(
                        $busquedaId,
                        $titulo,
                        $descripcion,
                        'Organismo contratante', // Se puede extraer del XML si existe
                        null,
                        date('Y-m-d', strtotime($fechaPub)),
                        null,
                        $link,
                        $keywordsEncontradas
                    )) {
                        $encontrados++;
                        echo "   ✅ " . mb_substr($titulo, 0, 60) . "...\n";
                    }
                }
            }

            echo "\n✅ PLACSP procesado: $encontrados resultados nuevos\n";
            return $encontrados;
        } catch (Exception $e) {
            echo "❌ Error: " . $e->getMessage() . "\n";
            return 0;
        }
    }

    private function obtenerFeed($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');

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
        echo "✅ PLACSP configurado correctamente\n";
        echo "📁 Fuente ID: " . $this->fuenteId . "\n";
        echo "📡 Endpoint: https://contrataciondelsectorpublico.gob.es/sindicacion/sindicacion_643/licitacionesPerfilesContratanteCompleto3.atom\n";
        return true;
    }
}
