<?php
// Fuentes/CSIC_PLACSP.php
// CSIC a través del feed ATOM de PLACSP

require_once __DIR__ . '/../Core/Database.php';

class CSIC_PLACSP
{
    private $pdo;
    private $fuenteId;

    // Variantes del nombre del CSIC
    private $organismosCSIC = [
        'CSIC',
        'Consejo Superior de Investigaciones Científicas',
        'AGENCIA ESTATAL CONSEJO SUPERIOR DE INVESTIGACIONES CIENTIFICICAS',
        'AGENCIA ESTATAL CSIC'
    ];

    // CPVs de servicios audiovisuales
    private $cpvPrioritarios = [
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
            $stmt = $this->pdo->prepare("SELECT id FROM fuentes WHERE nombre_corto = 'csic_placsp'");
            $stmt->execute();
            $result = $stmt->fetch();

            if (!$result) {
                $stmt = $this->pdo->prepare("INSERT INTO fuentes (nombre, nombre_corto, tipo, url_base, script_asociado) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([
                    'CSIC (vía PLACSP)',
                    'csic_placsp',
                    'licitacion',
                    'https://contrataciondelsectorpublico.gob.es',
                    'CSIC_PLACSP.php'
                ]);
                return $this->pdo->lastInsertId();
            }
            return $result['id'];
        } catch (PDOException $e) {
            die("Error con fuente CSIC_PLACSP: " . $e->getMessage());
        }
    }

    public function ejecutar($busquedaId, $dias = 360)
    {
        echo "\n🔍 Buscando CSIC en PLACSP...\n";

        try {
            $busqueda = $this->getBusqueda($busquedaId);
            if (!$busqueda) throw new Exception("Búsqueda no encontrada");

            $palabrasUsuario = array_map('trim', explode(',', $busqueda['palabras_clave']));
            $encontrados = 0;

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

            // Filtrar por fecha
            $fechaLimite = date('Y-m-d', strtotime("-$dias days"));

            foreach ($items as $entry) {
                $titulo = (string)$entry->title;
                $link = (string)$entry->link['href'];
                $descripcion = (string)$entry->summary;
                $fechaPub = (string)$entry->updated;
                $contenidoCompleto = $titulo . ' ' . $descripcion;

                // Filtrar por fecha
                if (strtotime($fechaPub) < strtotime($fechaLimite)) {
                    continue;
                }

                // Verificar si es del CSIC
                $esCSIC = false;
                foreach ($this->organismosCSIC as $org) {
                    if (stripos($contenidoCompleto, $org) !== false) {
                        $esCSIC = true;
                        break;
                    }
                }

                if (!$esCSIC) {
                    continue;
                }

                // Verificar CPV
                $tieneCPV = false;
                foreach ($this->cpvPrioritarios as $cpv) {
                    if (strpos($contenidoCompleto, $cpv) !== false) {
                        $tieneCPV = true;
                        break;
                    }
                }

                // Buscar palabras clave
                $keywordsEncontradas = $this->buscarKeywords($contenidoCompleto, $palabrasUsuario);

                if ($tieneCPV || !empty($keywordsEncontradas)) {
                    if ($tieneCPV && empty($keywordsEncontradas)) {
                        $keywordsEncontradas = ['CPV prioritario'];
                    }

                    if ($this->guardarResultado(
                        $busquedaId,
                        $titulo,
                        $descripcion,
                        'CSIC',
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

            echo "\n✅ CSIC (PLACSP) procesado: $encontrados resultados\n";
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
        echo "✅ CSIC_PLACSP configurado correctamente\n";
        echo "📁 Fuente ID: " . $this->fuenteId . "\n";
        echo "📡 Buscando organismos: " . implode(', ', $this->organismosCSIC) . "\n";
        return true;
    }
}
