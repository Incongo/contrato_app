<?php
// Fuentes/BOPB_RSS.php
// Butlletí Oficial de la Província de Barcelona - RSS por secciones

require_once __DIR__ . '/../Core/Database.php';

class BOPB_RSS
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
    // URLs de los RSS por sección (CORREGIDAS Y AMPLIADAS)
    private $secciones = [
        'administracio_local' => 'https://bop.diba.cat/dades-obertes/butlleti-del-dia/administracio-local/feed',
        'generalitat' => 'https://bop.diba.cat/dades-obertes/butlleti-del-dia/generalitat-catalunya/feed', // CORREGIDA
        'administracio_estat' => 'https://bop.diba.cat/dades-obertes/butlleti-del-dia/administracio-general-estat/feed',
        'justicia' => 'https://bop.diba.cat/dades-obertes/butlleti-del-dia/administracio-justicia/feed',
        'entitats' => 'https://bop.diba.cat/dades-obertes/butlleti-del-dia/entitats-diverses/feed',
        // AÑADIDAS (si existen)
        'altres' => 'https://bop.diba.cat/dades-obertes/butlleti-del-dia/altres/feed'
    ];

    public function __construct()
    {
        $this->pdo = Database::getInstance();
        $this->fuenteId = $this->obtenerFuenteId();
    }

    private function obtenerFuenteId()
    {
        try {
            $stmt = $this->pdo->prepare("SELECT id FROM fuentes WHERE nombre_corto = 'bopb'");
            $stmt->execute();
            $result = $stmt->fetch();

            if (!$result) {
                $stmt = $this->pdo->prepare("INSERT INTO fuentes (nombre, nombre_corto, tipo, url_base, script_asociado) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([
                    'Butlletí Oficial de la Província de Barcelona',
                    'bopb',
                    'licitacion',
                    'https://bop.diba.cat',
                    'BOPB_RSS.php'
                ]);
                return $this->pdo->lastInsertId();
            }
            return $result['id'];
        } catch (PDOException $e) {
            die("Error con fuente BOPB: " . $e->getMessage());
        }
    }

    /**
     * Método principal: ejecutar búsqueda
     */
    public function ejecutar($busquedaId, $dias = 7)
    {
        echo "\n🔍 Buscando en BOPB (Barcelona)...\n";

        try {
            $busqueda = $this->getBusqueda($busquedaId);
            if (!$busqueda) throw new Exception("Búsqueda no encontrada");

            $totalEncontrados = 0;

            // Probar cada sección
            foreach ($this->secciones as $nombre => $url) {
                echo "\n📡 Sección: $nombre\n";
                $encontrados = $this->procesarSeccion($url, $busqueda, $dias);
                echo "   → $encontrados resultados\n";
                $totalEncontrados += $encontrados;
            }

            echo "✅ BOPB procesado: $totalEncontrados resultados totales\n";
            return $totalEncontrados;
        } catch (Exception $e) {
            echo "❌ Error: " . $e->getMessage() . "\n";
            return 0;
        }
    }

    /**
     * Procesar una sección RSS
     */
    private function procesarSeccion($url, $busqueda, $dias)
    {
        $xmlData = $this->obtenerRSS($url);
        if (!$xmlData) {
            return 0;
        }

        $rss = simplexml_load_string($xmlData, 'SimpleXMLElement', LIBXML_NOCDATA | LIBXML_NOWARNING);
        if (!$rss || !isset($rss->channel->item)) {
            return 0;
        }

        return $this->procesarItems($rss->channel->item, $busqueda, $dias);
    }

    /**
     * Obtener RSS con cURL
     */
    private function obtenerRSS($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');

        $xmlData = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || !$xmlData) {
            echo "      HTTP $httpCode\n";
            return null;
        }

        return $xmlData;
    }

    /**
     * Procesar items del RSS (MEJORADO)
     */
    private function procesarItems($items, $busqueda, $dias)
    {
        $palabrasBusqueda = array_map('trim', explode(',', $busqueda['palabras_clave']));
        $fechaLimite = strtotime("-$dias days");
        $encontrados = 0;

        foreach ($items as $item) {
            $titulo = (string)$item->title;
            $link = (string)$item->link;
            $descripcion = (string)$item->description;
            $fechaPub = (string)$item->pubDate;

            // Decodificar entidades HTML
            $titulo = html_entity_decode($titulo, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $descripcion = html_entity_decode($descripcion, ENT_QUOTES | ENT_HTML5, 'UTF-8');

            // Filtrar por fecha
            $fechaPubTimestamp = strtotime($fechaPub);
            if ($fechaPubTimestamp < $fechaLimite) {
                continue;
            }

            // Buscar palabras clave
            $textoCompleto = $titulo . ' ' . $descripcion;
            $keywordsEncontradas = $this->buscarKeywords($textoCompleto, $palabrasBusqueda);

            if (!empty($keywordsEncontradas)) {
                // Extraer organismo (MEJORADO)
                $organismo = $this->extraerOrganismo($descripcion, $titulo);

                // Extraer título real (a veces el título es el organismo)
                $tituloReal = $this->extraerTituloReal($titulo, $descripcion);

                if ($this->guardarResultado(
                    $busqueda['id'],
                    $tituloReal,
                    $descripcion,
                    $organismo,
                    null, // presupuesto (no suele venir en RSS)
                    date('Y-m-d', $fechaPubTimestamp),
                    null, // fecha límite
                    $link,
                    [], // cpvs
                    $keywordsEncontradas
                )) {
                    $encontrados++;

                    // Mostrar el resultado encontrado (para depuración)
                    echo "     ✅ " . mb_substr($tituloReal, 0, 60) . "...\n";
                }
            }
        }

        return $encontrados;
    }

    /**
     * Buscar keywords en texto
     */
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

    /**
     * Extraer organismo de la descripción o título (MEJORADO)
     */
    private function extraerOrganismo($descripcion, $titulo)
    {
        $texto = $descripcion . ' ' . $titulo;

        // Patrones para organismos catalanes
        $patrones = [
            '/Ajuntament de ([^<.,]+)/i',
            '/Consell Comarcal de ([^<.,]+)/i',
            '/Diputació de ([^<.,]+)/i',
            '/Generalitat de ([^<.,]+)/i',
            '/Departament de ([^<.,]+)/i',
            '/Consorci de ([^<.,]+)/i'
        ];

        foreach ($patrones as $patron) {
            if (preg_match($patron, $texto, $match)) {
                return trim($match[0]);
            }
        }

        return 'Diputació de Barcelona';
    }

    /**
     * Extraer título real (quitar el organismo si está al principio)
     */
    private function extraerTituloReal($titulo, $descripcion)
    {
        // Si el título es muy corto, usar la descripción
        if (strlen($titulo) < 20 && !empty($descripcion)) {
            return $descripcion;
        }

        // Quitar patrones comunes al inicio del título
        $tituloLimpio = preg_replace('/^[^\-]+-\s*/', '', $titulo);
        $tituloLimpio = preg_replace('/^Anunci .+? - /i', '', $tituloLimpio);

        return $tituloLimpio;
    }

    /**
     * Guardar resultado en BD
     */
    private function guardarResultado($busquedaId, $titulo, $descripcion, $organismo, $presupuesto, $fechaPub, $fechaLim, $url, $cpvs, $keywords)
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
                    codigos_cpv, palabras_coincidentes, relevancia
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
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
                count($keywords)
            ]);

            return true;
        } catch (PDOException $e) {
            error_log("Error guardando: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener datos de búsqueda
     */
    private function getBusqueda($busquedaId)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM busquedas WHERE id = ? AND activo = 1");
        $stmt->execute([$busquedaId]);
        return $stmt->fetch();
    }

    /**
     * Método de prueba
     */
    public function probar()
    {
        echo "✅ BOPB RSS configurado correctamente\n";
        echo "📁 Fuente ID: " . $this->fuenteId . "\n";
        echo "📡 Secciones disponibles:\n";
        foreach ($this->secciones as $nombre => $url) {
            echo "   - $nombre: $url\n";
        }
        return true;
    }
}
