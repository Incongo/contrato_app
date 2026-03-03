<?php
// Fuentes/DOGE.php
// Diario Oficial de Galicia - Versión con Canales Organizados

require_once __DIR__ . '/../Core/Database.php';
require_once __DIR__ . '/DOGE_Canales.php'; // Importamos la configuración

class DOGE
{
    private $pdo;
    private $fuenteId;
    private $canales;

    public function __construct()
    {
        $this->pdo = Database::getInstance();
        $this->fuenteId = $this->obtenerFuenteId();
        $this->canales = new DOGE_Canales(); // Cargamos la configuración
    }

    private function obtenerFuenteId()
    {
        try {
            $stmt = $this->pdo->prepare("SELECT id FROM fuentes WHERE nombre_corto = 'doge'");
            $stmt->execute();
            $result = $stmt->fetch();

            if (!$result) {
                $stmt = $this->pdo->prepare("INSERT INTO fuentes (nombre, nombre_corto, tipo, url_base, script_asociado) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute(['Diario Oficial de Galicia', 'doge', 'licitacion', 'https://www.xunta.gal/diario-oficial-galicia', 'DOGE.php']);
                return $this->pdo->lastInsertId();
            }
            return $result['id'];
        } catch (PDOException $e) {
            die("Error con fuente DOGE: " . $e->getMessage());
        }
    }

    public function ejecutar($busquedaId)
    {
        echo "\n🔍 Buscando en DOGE (Galicia) con canales organizados...\n";

        try {
            $busqueda = $this->getBusqueda($busquedaId);
            if (!$busqueda) throw new Exception("Búsqueda no encontrada");

            echo "📋 Búsqueda: {$busqueda['nombre']}\n";

            // Usar las palabras clave del usuario O las predefinidas
            $palabrasUsuario = array_map('trim', explode(',', $busqueda['palabras_clave']));
            $palabrasCiencia = $this->canales->getPalabrasCiencia();
            $palabrasAV = $this->canales->getPalabrasAudiovisual();

            // Combinamos todas
            $todasPalabras = array_merge($palabrasUsuario, $palabrasCiencia, $palabrasAV);
            $todasPalabras = array_unique($todasPalabras); // Eliminar duplicados

            echo "📌 Total palabras clave: " . count($todasPalabras) . "\n";

            $totalEncontrados = 0;

            // 1. Probar SECCIONES (prioridad alta)
            echo "\n📑 Probando Secciones:\n";
            $secciones = $this->canales->getSecciones();
            foreach ($secciones as $key => $seccion) {
                echo "   {$seccion['nombre']}: ";
                $encontrados = $this->procesarCanal($seccion['url'], $busqueda['id'], $todasPalabras);
                echo "$encontrados resultados.\n";
                $totalEncontrados += $encontrados;
            }

            // 2. Probar TEMÁTICAS (prioridad media/alta)
            echo "\n🏷️ Probando Temáticas:\n";
            $tematicas = $this->canales->getTematicas();
            $contador = 0;
            foreach ($tematicas as $key => $tematica) {
                echo "   {$tematica['nombre']}: ";
                $encontrados = $this->procesarCanal($tematica['url'], $busqueda['id'], $todasPalabras);
                echo "$encontrados resultados.\n";
                $totalEncontrados += $encontrados;

                $contador++;
                if ($contador % 5 == 0) sleep(1); // Pausa cada 5
            }

            echo "\n✅ DOGE procesado: $totalEncontrados resultados totales\n";
            return $totalEncontrados;
        } catch (Exception $e) {
            echo "❌ Error: " . $e->getMessage() . "\n";
            return 0;
        }
    }

    private function procesarCanal($url, $busquedaId, $palabrasBusqueda)
    {
        $xmlData = $this->obtenerRSS($url);
        if (!$xmlData) {
            return 0;
        }

        $tamaño = strlen($xmlData);
        echo "($tamaño bytes) ";

        $rss = simplexml_load_string($xmlData, 'SimpleXMLElement', LIBXML_NOCDATA | LIBXML_NOWARNING);
        if (!$rss || !isset($rss->channel->item)) {
            return 0;
        }

        return $this->procesarItems($rss->channel->item, $busquedaId, $palabrasBusqueda);
    }

    private function obtenerRSS($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');

        $xmlData = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || !$xmlData) {
            return null;
        }
        return $xmlData;
    }

    private function procesarItems($items, $busquedaId, $palabrasBusqueda)
    {
        $encontrados = 0;

        foreach ($items as $item) {
            $titulo = (string)$item->title;
            $link = (string)$item->link;
            $descripcion = (string)$item->description;
            $fechaPub = (string)$item->pubDate;

            $titulo = html_entity_decode($titulo, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $descripcion = html_entity_decode($descripcion, ENT_QUOTES | ENT_HTML5, 'UTF-8');

            $textoCompleto = $titulo . ' ' . $descripcion;
            $keywordsEncontradas = $this->buscarKeywords($textoCompleto, $palabrasBusqueda);

            if (!empty($keywordsEncontradas)) {
                if ($this->guardarResultado(
                    $busquedaId,
                    $titulo,
                    $descripcion,
                    $this->extraerOrganismo($descripcion),
                    null,
                    date('Y-m-d', strtotime($fechaPub)),
                    null,
                    $link,
                    [],
                    $keywordsEncontradas
                )) {
                    $encontrados++;
                }
            }
        }
        return $encontrados;
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

    private function extraerOrganismo($descripcion)
    {
        if (preg_match('/CONSELLERÍA DE ([^<.,]+)/i', $descripcion, $match)) {
            return 'Consellería de ' . trim($match[1]);
        }
        if (preg_match('/AYUNTAMIENTO DE ([^<.,]+)/i', $descripcion, $match)) {
            return 'Ayuntamiento de ' . trim($match[1]);
        }
        if (preg_match('/DIPUTACIÓN DE ([^<.,]+)/i', $descripcion, $match)) {
            return 'Diputación de ' . trim($match[1]);
        }
        if (preg_match('/MINISTERIO DE ([^<.,]+)/i', $descripcion, $match)) {
            return 'Ministerio de ' . trim($match[1]);
        }
        return 'Xunta de Galicia';
    }

    private function guardarResultado($busquedaId, $titulo, $descripcion, $organismo, $presupuesto, $fechaPub, $fechaLim, $url, $cpvs, $keywords)
    {
        try {
            $stmt = $this->pdo->prepare("SELECT id FROM resultados WHERE url_detalle = ? AND busqueda_id = ?");
            $stmt->execute([$url, $busquedaId]);
            if ($stmt->fetch()) return false;

            $stmt = $this->pdo->prepare(
                "INSERT INTO resultados (busqueda_id, fuente_id, titulo, descripcion_corta, organismo, presupuesto, fecha_publicacion, fecha_limite, url_detalle, codigos_cpv, palabras_coincidentes, relevancia)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );

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
                $cpvs ? implode(',', $cpvs) : null,
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
        echo "✅ DOGE configurado correctamente\n";
        echo "📁 Fuente ID: " . $this->fuenteId . "\n";
        return true;
    }
}
