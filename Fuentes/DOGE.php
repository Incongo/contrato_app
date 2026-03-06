<?php
// Fuentes/DOGE.php
// Diario Oficial de Galicia - Versión CORREGIDA

require_once __DIR__ . '/../Core/Database.php';
require_once __DIR__ . '/DOGE_Canales.php'; // Importamos la configuración

class DOGE
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
        echo "\n🔍 Buscando en DOGE (Galicia)...\n";

        try {
            $busqueda = $this->getBusqueda($busquedaId);
            if (!$busqueda) throw new Exception("Búsqueda no encontrada");

            echo "📋 Búsqueda: {$busqueda['nombre']}\n";

            // Usar SOLO las palabras clave del usuario
            $palabrasBusqueda = array_map('trim', explode(',', $busqueda['palabras_clave']));
            echo "📌 Palabras clave: " . implode(', ', $palabrasBusqueda) . "\n";

            // Obtener TODAS las URLs de DOGE_Canales
            $urls = DOGE_Canales::getTodasLasUrls();
            echo "📡 Total canales a probar: " . count($urls) . "\n";

            $totalEncontrados = 0;
            $contador = 0;

            foreach ($urls as $nombre => $url) {
                $contador++;
                echo "\n📡 Canal $contador: $nombre\n";

                $encontrados = $this->procesarCanal($url, $busqueda['id'], $palabrasBusqueda);
                echo "   → $encontrados resultados\n";
                $totalEncontrados += $encontrados;

                // Pausa cada 10 canales para no saturar
                if ($contador % 10 == 0) {
                    echo "   ⏱️ Pausa de 1 segundo...\n";
                    sleep(1);
                }
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
        echo "   Tamaño: $tamaño bytes\n";

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
            echo "   HTTP $httpCode - No se pudo obtener RSS\n";
            return null;
        }
        return $xmlData;
    }

    private function procesarItems($items, $busquedaId, $palabrasBusqueda)
    {
        $encontrados = 0;

        // FILTRO 1: Solo últimos 30 DÍAS de publicación
        $fechaLimitePublicacion = strtotime('-360 days');
        $fechaActual = date('Y-m-d H:i:s');

        foreach ($items as $item) {
            // Obtener fecha de publicación del anuncio
            $fechaPubStr = (string)$item->pubDate;
            $fechaPubTimestamp = strtotime($fechaPubStr);

            // FILTRO POR FECHA DE PUBLICACIÓN (solo últimos 30 días)
            if ($fechaPubTimestamp < $fechaLimitePublicacion) {
                continue; // Es demasiado antiguo, lo saltamos
            }

            $titulo = (string)$item->title;
            $link = (string)$item->link;
            $descripcion = (string)$item->description;

            $titulo = html_entity_decode($titulo, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $descripcion = html_entity_decode($descripcion, ENT_QUOTES | ENT_HTML5, 'UTF-8');

            $textoCompleto = $titulo . ' ' . $descripcion;
            $keywordsEncontradas = $this->buscarKeywords($textoCompleto, $palabrasBusqueda);

            if (!empty($keywordsEncontradas)) {
                // Verificar si ya existe en BD
                $stmt = $this->pdo->prepare("SELECT id, ultima_deteccion FROM resultados WHERE url_detalle = ? AND busqueda_id = ?");
                $stmt->execute([$link, $busquedaId]);
                $existente = $stmt->fetch();

                if ($existente) {
                    // Ya existe: ACTUALIZAMOS la fecha de última detección
                    $upd = $this->pdo->prepare("UPDATE resultados SET ultima_deteccion = ?, relevancia = ?, palabras_coincidentes = ? WHERE id = ?");
                    $upd->execute([
                        $fechaActual,
                        count($keywordsEncontradas),
                        json_encode($keywordsEncontradas, JSON_UNESCAPED_UNICODE),
                        $existente['id']
                    ]);

                    // Solo contamos como NUEVO si ha pasado más de 7 días desde la última detección
                    $ultima = strtotime($existente['ultima_deteccion']);
                    if ($ultima < strtotime('-7 days')) {
                        $encontrados++;
                    }
                } else {
                    // Es NUEVO: insertamos con ambas fechas
                    if ($this->guardarResultado(
                        $busquedaId,
                        $titulo,
                        $descripcion,
                        $this->extraerOrganismo($descripcion, $titulo),
                        null,
                        date('Y-m-d', $fechaPubTimestamp),
                        null,
                        $link,
                        [],
                        $keywordsEncontradas,
                        $fechaActual
                    )) {
                        $encontrados++;
                    }
                }
            }
        }
        return $encontrados;
    }

    private function guardarResultado($busquedaId, $titulo, $descripcion, $organismo, $presupuesto, $fechaPub, $fechaLim, $url, $cpvs, $keywords, $ultimaDeteccion = null)
    {
        try {
            $stmt = $this->pdo->prepare("SELECT id FROM resultados WHERE url_detalle = ? AND busqueda_id = ?");
            $stmt->execute([$url, $busquedaId]);
            if ($stmt->fetch()) return false;

            $stmt = $this->pdo->prepare("
                INSERT INTO resultados (
                    busqueda_id, fuente_id, titulo, descripcion_corta, organismo,
                    presupuesto, fecha_publicacion, fecha_limite, url_detalle,
                    codigos_cpv, palabras_coincidentes, relevancia,
                    fecha_deteccion, ultima_deteccion
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?)
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
                $cpvs ? implode(',', $cpvs) : null,
                json_encode($keywords, JSON_UNESCAPED_UNICODE),
                count($keywords),
                $ultimaDeteccion ?? date('Y-m-d H:i:s')
            ]);
            return true;
        } catch (PDOException $e) {
            error_log("Error guardando: " . $e->getMessage());
            return false;
        }
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

    private function extraerOrganismo($descripcion, $titulo)
    {
        $texto = $descripcion . ' ' . $titulo;

        if (preg_match('/CONSELLERÍA DE ([^<.,]+)/i', $texto, $match)) {
            return 'Consellería de ' . trim($match[1]);
        }
        if (preg_match('/AYUNTAMIENTO DE ([^<.,]+)/i', $texto, $match)) {
            return 'Ayuntamiento de ' . trim($match[1]);
        }
        if (preg_match('/DIPUTACIÓN DE ([^<.,]+)/i', $texto, $match)) {
            return 'Diputación de ' . trim($match[1]);
        }
        if (preg_match('/MINISTERIO DE ([^<.,]+)/i', $texto, $match)) {
            return 'Ministerio de ' . trim($match[1]);
        }
        return 'Xunta de Galicia';
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
        echo "📡 Canales disponibles: " . count(DOGE_Canales::getTodasLasUrls()) . "\n";
        return true;
    }
}
