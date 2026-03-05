<?php
// Fuentes/ZaragozaAPI.php
// Ayuntamiento de Zaragoza - Scraper de contratación pública

require_once __DIR__ . '/../Core/Database.php';

class ZaragozaAPI
{
    private $pdo;
    private $fuenteId;

    private $cpvServicios = [
        '92100000',
        '92111000',
        '92112000',
        '92113000',
        '92220000',
        '79341000',
        '79961000',
        '72300000',
        '72400000'
    ];

    public function __construct()
    {
        $this->pdo = Database::getInstance();
        $this->fuenteId = $this->obtenerFuenteId();
    }

    private function obtenerFuenteId()
    {
        try {
            $stmt = $this->pdo->prepare("SELECT id FROM fuentes WHERE nombre_corto = 'zaragoza'");
            $stmt->execute();
            $result = $stmt->fetch();

            if (!$result) {
                $stmt = $this->pdo->prepare("INSERT INTO fuentes (nombre, nombre_corto, tipo, url_base, script_asociado) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([
                    'Ayuntamiento de Zaragoza - Contratación',
                    'zaragoza',
                    'licitacion',
                    'https://www.zaragoza.es',
                    'ZaragozaAPI.php'
                ]);
                return $this->pdo->lastInsertId();
            }
            return $result['id'];
        } catch (PDOException $e) {
            die("Error con fuente Zaragoza: " . $e->getMessage());
        }
    }

    public function ejecutar($busquedaId, $dias = 90)
    {
        echo "\n🔍 Buscando en Zaragoza...\n";

        try {
            $busqueda = $this->getBusqueda($busquedaId);
            if (!$busqueda) throw new Exception("Búsqueda no encontrada");

            // URL base de búsqueda
            $url = "https://www.zaragoza.es/sede/servicio/contratacion-publica.json?q=audiovisual&rows=100";

            echo "📡 Consultando: $url\n";

            $html = $this->obtenerHTML($url);
            if (!$html) {
                throw new Exception("No se pudo obtener el HTML");
            }

            // Cargar HTML
            $dom = new DOMDocument();
            libxml_use_internal_errors(true); // Ignorar warnings de HTML
            $dom->loadHTML($html);
            libxml_clear_errors();

            $xpath = new DOMXPath($dom);

            // Buscar todos los artículos de contratos (basado en la estructura real)
            $contratos = $xpath->query("//article[contains(@class, 'col-xs-12') and contains(@class, 'col-md-6')]");

            echo "📊 Total contratos encontrados: " . $contratos->length . "\n";

            $palabrasUsuario = array_map('trim', explode(',', $busqueda['palabras_clave']));
            $encontrados = 0;

            foreach ($contratos as $contrato) {
                // Extraer TÍTULO y URL
                $tituloNode = $xpath->query(".//h2/a", $contrato);
                if ($tituloNode->length == 0) continue;

                $titulo = trim($tituloNode->item(0)->nodeValue);
                $urlDetalle = 'https://www.zaragoza.es' . $tituloNode->item(0)->getAttribute('href');

                // Extraer IMPORTE
                $importe = null;
                $importeNodes = $xpath->query(".//*[contains(text(), 'Importe licitacion')]", $contrato);
                if ($importeNodes->length > 0) {
                    $importeTexto = $importeNodes->item(0)->nodeValue;
                    if (preg_match('/(\d{1,3}(?:\.\d{3})*(?:,\d{2})?)€/', $importeTexto, $matches)) {
                        $importe = (float)str_replace(['.', ','], ['', '.'], $matches[1]);
                    }
                }

                // Extraer FECHA LÍMITE
                $fechaLimite = null;
                $fechaNodes = $xpath->query(".//*[contains(text(), 'Plazo de presentación')]", $contrato);
                if ($fechaNodes->length > 0) {
                    $fechaTexto = $fechaNodes->item(0)->nodeValue;
                    if (preg_match('/(\d{2}\/\d{2}\/\d{4})/', $fechaTexto, $matches)) {
                        $partes = explode('/', $matches[1]);
                        $fechaLimite = "{$partes[2]}-{$partes[1]}-{$partes[0]}";
                    }
                }

                // Extraer ORGANISMO
                $organismo = 'Ayuntamiento de Zaragoza';
                $organismoNodes = $xpath->query(".//a[contains(@href, 'entidad')]", $contrato);
                if ($organismoNodes->length > 0) {
                    $organismo = trim($organismoNodes->item(0)->nodeValue);
                }

                // Texto completo para búsqueda
                $textoCompleto = $titulo . ' ' . $organismo;
                $keywordsEncontradas = $this->buscarKeywords($textoCompleto, $palabrasUsuario);

                // Detección especial de Distrito 7
                $esDistrito7 = stripos($textoCompleto, 'distrito 7') !== false;

                if (!empty($keywordsEncontradas) || $esDistrito7) {
                    if ($esDistrito7) {
                        $keywordsEncontradas[] = 'DISTRITO 7';
                    }

                    if ($this->guardarResultado(
                        $busquedaId,
                        $titulo,
                        '', // descripción vacía
                        $organismo,
                        $importe,
                        date('Y-m-d'), // fecha publicación
                        $fechaLimite,
                        $urlDetalle,
                        $keywordsEncontradas
                    )) {
                        $encontrados++;
                        echo "   ✅ " . mb_substr($titulo, 0, 60) . "...\n";
                    }
                }
            }

            echo "✅ Zaragoza procesada: $encontrados nuevos resultados\n";
            return $encontrados;
        } catch (Exception $e) {
            echo "❌ Error: " . $e->getMessage() . "\n";
            return 0;
        }
    }

    private function obtenerHTML($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
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
            // Verificar si ya existe
            $stmt = $this->pdo->prepare("SELECT id FROM resultados WHERE url_detalle = ? AND busqueda_id = ?");
            $stmt->execute([$url, $busquedaId]);
            if ($stmt->fetch()) return false;

            // Insertar
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
        echo "✅ Zaragoza scraper configurado correctamente\n";
        echo "📁 Fuente ID: " . $this->fuenteId . "\n";
        return true;
    }
}
