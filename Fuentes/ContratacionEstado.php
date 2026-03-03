<?php
// app/Fuentes/ContratacionEstado.php
// Versión: 1.0 - Procesamiento ZIP + Preparación para RSS
// Fecha: 2026-03-03

require_once __DIR__ . '/../Core/Database.php';
require_once __DIR__ . '/../Lib/Keywords.php';

class ContratacionEstado
{
    private $pdo;
    private $fuenteId;
    private $log = [];

    public function __construct()
    {
        $this->pdo = Database::getInstance();
        $this->fuenteId = $this->obtenerFuenteId();
    }

    public function probar()
    {
        echo "✅ ContratacionEstado funciona correctamente\n";
        echo "📁 Fuente ID: " . $this->fuenteId . "\n";
        return true;
    }
    /**
     * Obtener el ID de la fuente desde la BD
     */
    private function obtenerFuenteId()
    {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT id FROM fuentes WHERE nombre_corto = 'contratacion_estado'"
            );
            $stmt->execute();
            $result = $stmt->fetch();

            // Si no existe la fuente, la creamos
            if (!$result) {
                $stmt = $this->pdo->prepare(
                    "INSERT INTO fuentes (nombre, nombre_corto, tipo, url_base, script_asociado) 
                     VALUES (?, ?, ?, ?, ?)"
                );
                $stmt->execute([
                    'Plataforma de Contratación del Estado',
                    'contratacion_estado',
                    'licitacion',
                    'https://contrataciondelestado.es',
                    'ContratacionEstado.php'
                ]);
                return $this->pdo->lastInsertId();
            }

            return $result['id'];
        } catch (PDOException $e) {
            die("Error con la fuente: " . $e->getMessage());
        }
    }

    /**
     * Método principal: ejecutar búsqueda para una búsqueda específica
     */
    public function ejecutar($busquedaId, $params = [])
    {
        $inicio = microtime(true);

        echo "\n🔍 Ejecutando búsqueda ID: $busquedaId\n";

        try {
            // 1. Obtener datos de la búsqueda
            $busqueda = $this->getBusqueda($busquedaId);
            if (!$busqueda) {
                throw new Exception("Búsqueda no encontrada");
            }

            echo "📋 Búsqueda: {$busqueda['nombre']}\n";
            echo "📌 Palabras clave: {$busqueda['palabras_clave']}\n";

            // 2. Buscar vía RSS
            $resultados = $this->buscarNuevasRSS($busquedaId);

            // 3. Guardar log
            $tiempo = microtime(true) - $inicio;
            $this->guardarLog($busquedaId, $resultados, $tiempo);

            echo "✅ Procesado: $resultados resultados encontrados en " . round($tiempo, 2) . "s\n";

            return $resultados;
        } catch (Exception $e) {
            echo "❌ Error: " . $e->getMessage() . "\n";
            $this->guardarLog($busquedaId, 0, microtime(true) - $inicio, $e->getMessage());
            return 0;
        }
    }

    /**
     * Procesar archivo ZIP
     */
    private function procesarZip($zipPath, $busqueda)
    {
        if (!class_exists('ZipArchive')) {
            throw new Exception("La extensión ZipArchive no está instalada");
        }

        $zip = new ZipArchive();
        $encontrados = 0;
        $procesados = 0;

        if ($zip->open($zipPath) !== true) {
            throw new Exception("No se puede abrir el ZIP");
        }

        $totalArchivos = $zip->numFiles;
        echo "📊 Total archivos en ZIP: $totalArchivos\n";

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $filename = $zip->getNameIndex($i);

            // Solo procesar archivos .atom (XML)
            if (pathinfo($filename, PATHINFO_EXTENSION) !== 'atom') {
                continue;
            }

            $procesados++;
            $contenido = $zip->getFromIndex($i);

            if ($this->procesarArchivo($contenido, $filename, $busqueda)) {
                $encontrados++;

                // Mostrar progreso cada 10 resultados
                if ($encontrados % 10 === 0) {
                    echo "   → $encontrados resultados encontrados...\n";
                }
            }

            // Liberar memoria cada 50 archivos
            if ($i % 50 === 0) {
                gc_collect_cycles();
            }
        }

        $zip->close();
        echo "📈 Procesados $procesados archivos, encontrados $encontrados resultados\n";

        return $encontrados;
    }

    /**
     * Procesar un archivo individual (XML .atom)
     */
    private function procesarArchivo($contenido, $filename, $busqueda)
    {
        try {
            // 1. Extraer CPVs
            $cpvs = $this->extraerCPVs($contenido);

            // 2. Verificar si tiene CPV relevante (rápido)
            if (!$this->tieneCPVRelevante($cpvs)) {
                return false;
            }

            // 3. Extraer título y texto para keywords
            $titulo = $this->extraerTitulo($contenido);
            $descripcion = $this->extraerDescripcion($contenido);
            $textoCompleto = $titulo . ' ' . $descripcion;

            // 4. Obtener palabras clave de la búsqueda
            $palabrasBusqueda = explode(',', $busqueda['palabras_clave']);
            $palabrasBusqueda = array_map('trim', $palabrasBusqueda);

            // 5. Verificar si ALGUNA palabra clave de la búsqueda aparece
            $keywordsEncontradas = $this->buscarKeywords($textoCompleto, $palabrasBusqueda);

            if (empty($keywordsEncontradas)) {
                return false;
            }

            // 6. Extraer TODOS los datos mínimos
            $datos = $this->extraerDatosMinimos($contenido, $filename);

            // 7. Guardar en BD
            return $this->guardarResultado(
                $busqueda['id'],
                $datos,
                $keywordsEncontradas,
                count($keywordsEncontradas)
            );
        } catch (Exception $e) {
            error_log("Error procesando $filename: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Extraer CPVs del XML
     */
    private function extraerCPVs($xml)
    {
        $cpvs = [];
        // Patrón para encontrar códigos CPV (8 dígitos)
        preg_match_all('/<cbc:ItemClassificationCode[^>]*>(\d{8})<\/cbc:ItemClassificationCode>/', $xml, $matches);

        if (!empty($matches[1])) {
            $cpvs = array_unique($matches[1]);
        }

        return $cpvs;
    }

    /**
     * Verificar si tiene algún CPV prioritario
     */
    private function tieneCPVRelevante($cpvs)
    {
        $prioritarios = Keywords::getCPVPrioritarios();
        foreach ($cpvs as $cpv) {
            if (in_array($cpv, $prioritarios)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Buscar keywords en un texto
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
     * Extraer título del XML
     */
    private function extraerTitulo($xml)
    {
        $patrones = [
            '/<cbc:Title[^>]*>(.*?)<\/cbc:Title>/',
            '/<cbc:ContractTitle[^>]*>(.*?)<\/cbc:ContractTitle>/',
            '/<title[^>]*>(.*?)<\/title>/i'
        ];

        foreach ($patrones as $patron) {
            if (preg_match($patron, $xml, $match)) {
                return trim($match[1]);
            }
        }

        return 'Sin título descriptivo';
    }

    /**
     * Extraer descripción corta
     */
    private function extraerDescripcion($xml)
    {
        $patrones = [
            '/<cbc:Description[^>]*>(.*?)<\/cbc:Description>/',
            '/<cbc:ContractDescription[^>]*>(.*?)<\/cbc:ContractDescription>/',
            '/<summary[^>]*>(.*?)<\/summary>/i'
        ];

        foreach ($patrones as $patron) {
            if (preg_match($patron, $xml, $match)) {
                return trim($match[1]);
            }
        }

        return '';
    }

    /**
     * Extraer organismo
     */
    private function extraerOrganismo($xml)
    {
        $patrones = [
            '/<cac:ContractingParty>.*?<cbc:PartyName>(.*?)<\/cbc:PartyName>.*?<\/cac:ContractingParty>/s',
            '/<cbc:OrganizationName[^>]*>(.*?)<\/cbc:OrganizationName>/'
        ];

        foreach ($patrones as $patron) {
            if (preg_match($patron, $xml, $match)) {
                return trim(strip_tags($match[1]));
            }
        }

        return 'No especificado';
    }

    /**
     * Extraer presupuesto
     */
    private function extraerPresupuesto($xml)
    {
        $patrones = [
            '/<cbc:EstimatedOverallContractAmount[^>]*>(.*?)<\/cbc:EstimatedOverallContractAmount>/',
            '/<cbc:TotalAmount[^>]*>(.*?)<\/cbc:TotalAmount>/'
        ];

        foreach ($patrones as $patron) {
            if (preg_match($patron, $xml, $match)) {
                $numero = preg_replace('/[^0-9,.]/', '', $match[1]);
                $numero = str_replace(',', '.', $numero);
                if (is_numeric($numero)) {
                    return floatval($numero);
                }
            }
        }

        return null;
    }

    /**
     * Extraer fechas
     */
    private function extraerFechas($xml)
    {
        $fechas = [
            'publicacion' => null,
            'limite' => null
        ];

        // Fecha publicación
        if (preg_match('/<cbc:IssueDate[^>]*>(.*?)<\/cbc:IssueDate>/', $xml, $match)) {
            $fechas['publicacion'] = $match[1];
        }

        // Fecha límite
        if (preg_match('/<cbc:ReceivedDate[^>]*>(.*?)<\/cbc:ReceivedDate>/', $xml, $match)) {
            $fechas['limite'] = $match[1];
        }

        return $fechas;
    }

    /**
     * Extraer ID real (idEvl) para la URL
     */
    private function extraerIdEvl($xml)
    {
        // Buscar el patrón exacto de idEvl
        if (preg_match('/idEvl=([^&\s"<]+)/', $xml, $match)) {
            return trim($match[1]);
        }

        // Buscar en campos específicos
        if (preg_match('/<cbc:ID[^>]*>(.*?)<\/cbc:ID>/', $xml, $match)) {
            return trim($match[1]);
        }

        return null;
    }

    /**
     * Extraer lugar de ejecución
     */
    private function extraerLugarEjecucion($xml)
    {
        $patrones = [
            '/<cbc:PlaceOfPerformance[^>]*>(.*?)<\/cbc:PlaceOfPerformance>/',
            '/<cbc:CountrySubentity[^>]*>(.*?)<\/cbc:CountrySubentity>/'
        ];

        foreach ($patrones as $patron) {
            if (preg_match($patron, $xml, $match)) {
                return trim($match[1]);
            }
        }

        return null;
    }

    /**
     * Extraer tipo de contrato
     */
    private function extraerTipoContrato($xml)
    {
        if (preg_match('/<cbc:ContractType[^>]*>(.*?)<\/cbc:ContractType>/', $xml, $match)) {
            return trim($match[1]);
        }
        return null;
    }

    /**
     * Extraer TODOS los datos mínimos de una vez
     */
    private function extraerDatosMinimos($xml, $filename)
    {
        $cpvs = $this->extraerCPVs($xml);
        $fechas = $this->extraerFechas($xml);
        $idEvl = $this->extraerIdEvl($xml);

        // Construir URL permanente (formato correcto)
        $url = $idEvl
            ? 'https://contrataciondelestado.es/wps/poc?uri=deeplink:detalle_licitacion&idEvl=' . urlencode($idEvl)
            : null;

        return [
            'titulo' => $this->extraerTitulo($xml),
            'descripcion' => $this->extraerDescripcion($xml),
            'organismo' => $this->extraerOrganismo($xml),
            'presupuesto' => $this->extraerPresupuesto($xml),
            'fecha_publicacion' => $fechas['publicacion'],
            'fecha_limite' => $fechas['limite'],
            'url_detalle' => $url,
            'lugar_ejecucion' => $this->extraerLugarEjecucion($xml),
            'tipo_contrato' => $this->extraerTipoContrato($xml),
            'cpvs' => $cpvs,
            'id_original' => $idEvl,
            'filename' => $filename
        ];
    }

    /**
     * Guardar resultado en BD
     */
    private function guardarResultado($busquedaId, $datos, $keywordsEncontradas, $relevancia)
    {
        try {
            // Si no hay URL, no guardamos
            if (empty($datos['url_detalle'])) {
                return false;
            }

            // Verificar si ya existe (por URL)
            $stmt = $this->pdo->prepare(
                "SELECT id FROM resultados WHERE url_detalle = ? AND busqueda_id = ?"
            );
            $stmt->execute([$datos['url_detalle'], $busquedaId]);
            if ($stmt->fetch()) {
                return false; // Ya existe
            }

            // Insertar
            $stmt = $this->pdo->prepare(
                "INSERT INTO resultados (
                    busqueda_id, fuente_id, titulo, descripcion_corta, organismo,
                    presupuesto, fecha_publicacion, fecha_limite, url_detalle,
                    lugar_ejecucion, codigos_cpv, tipo_contrato, relevancia,
                    palabras_coincidentes
                ) VALUES (
                    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
                )"
            );

            $palabrasCoincidentes = json_encode($keywordsEncontradas, JSON_UNESCAPED_UNICODE);

            $stmt->execute([
                $busquedaId,
                $this->fuenteId,
                $datos['titulo'],
                mb_substr($datos['descripcion'], 0, 500, 'UTF-8'), // Limitar longitud
                $datos['organismo'],
                $datos['presupuesto'],
                $datos['fecha_publicacion'],
                $datos['fecha_limite'],
                $datos['url_detalle'],
                $datos['lugar_ejecucion'],
                $datos['cpvs'] ? implode(',', $datos['cpvs']) : null,
                $datos['tipo_contrato'],
                $relevancia,
                $palabrasCoincidentes
            ]);

            return true;
        } catch (PDOException $e) {
            error_log("Error guardando resultado: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener datos de una búsqueda
     */
    private function getBusqueda($busquedaId)
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM busquedas WHERE id = ? AND activo = 1"
        );
        $stmt->execute([$busquedaId]);
        return $stmt->fetch();
    }

    /**
     * Guardar log de ejecución
     */
    private function guardarLog($busquedaId, $resultados, $tiempo, $error = null)
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO ejecuciones_log (fuente_id, busqueda_id, resultados_encontrados, tiempo_ejecucion, errores)
             VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $this->fuenteId,
            $busquedaId,
            $resultados,
            $tiempo,
            $error
        ]);
    }

    /**
     * BUSCAR NUEVAS LICITACIONES VÍA RSS (con cURL)
     * Este es el método que usaremos en producción
     */

    public function buscarNuevasRSS($busquedaId, $dias = 7)
    {
        $resultados = [];

        // 1. Obtener datos de la búsqueda
        $busqueda = $this->getBusqueda($busquedaId);
        if (!$busqueda) {
            throw new Exception("Búsqueda no encontrada");
        }

        echo "📡 Consultando RSS de contrataciondelestado.es con cURL...\n";

        // 2. URL del RSS
        $rssUrl = 'https://contrataciondelestado.es/sindicacion/rss/licitaciones.xml';

        // 3. Inicializar cURL
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $rssUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // ¡Clave! Ignorar SSL
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); // Ignorar verificación de host
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);  // Seguir redirecciones
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');

        $xmlData = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($httpCode !== 200 || !$xmlData) {
            throw new Exception("Error al obtener RSS (HTTP $httpCode): $error");
        }

        // 4. Parsear XML (suprimiendo warnings de entidades HTML)
        $rss = simplexml_load_string($xmlData, 'SimpleXMLElement', LIBXML_NOCDATA | LIBXML_NOERROR | LIBXML_NOWARNING);
        if (!$rss || !isset($rss->channel)) {
            // Intentar limpiar entidades HTML
            $xmlData = preg_replace('/&(?:[a-zA-Z][a-zA-Z0-9]+);/', '', $xmlData);
            $rss = simplexml_load_string($xmlData);
            if (!$rss) {
                throw new Exception("No se pudo parsear el RSS");
            }
        }

        $encontrados = 0;
        $palabrasBusqueda = array_map('trim', explode(',', $busqueda['palabras_clave']));

        // 5. Procesar cada item del RSS
        if (!isset($rss->channel->item)) {
            echo "⚠️ El RSS no contiene items\n";
            return 0;
        }

        foreach ($rss->channel->item as $item) {
            $titulo = (string)$item->title;
            $link = (string)$item->link;
            $descripcion = (string)$item->description;
            $fechaPub = (string)$item->pubDate;

            // Limpiar entidades HTML si las hay
            $titulo = html_entity_decode($titulo, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $descripcion = html_entity_decode($descripcion, ENT_QUOTES | ENT_HTML5, 'UTF-8');

            // Buscar palabras clave en título y descripción
            $textoCompleto = $titulo . ' ' . $descripcion;
            $keywordsEncontradas = $this->buscarKeywords($textoCompleto, $palabrasBusqueda);

            if (!empty($keywordsEncontradas)) {
                // Hay coincidencia, obtener más detalles
                $detalles = $this->obtenerDetalleLicitacion($link);

                if ($detalles) {
                    // Guardar resultado
                    $this->guardarResultadoRSS(
                        $busquedaId,
                        $titulo,
                        $descripcion,
                        $detalles['organismo'] ?? 'No especificado',
                        $detalles['presupuesto'] ?? null,
                        date('Y-m-d', strtotime($fechaPub)),
                        $detalles['fecha_limite'] ?? null,
                        $link,
                        $detalles['cpvs'] ?? [],
                        $keywordsEncontradas
                    );
                    $encontrados++;

                    if ($encontrados % 5 === 0) {
                        echo "   → $encontrados resultados...\n";
                    }
                }
            }
        }

        echo "✅ Búsqueda RSS completada: $encontrados resultados nuevos\n";
        return $encontrados;
    }

    /**
     * Obtener detalles adicionales de una licitación desde su página
     */
    private function obtenerDetalleLicitacion($url)
    {
        // Por ahora, intentamos extraer el ID de la URL
        $idEvl = null;
        if (preg_match('/idEvl=([^&\s"<]+)/', $url, $match)) {
            $idEvl = $match[1];
        }

        // En el futuro podríamos hacer una segunda petición para obtener más detalles
        // Por ahora devolvemos datos mínimos con el ID correcto
        return [
            'organismo' => 'Consultar web',
            'presupuesto' => null,
            'fecha_limite' => null,
            'cpvs' => [],
            'idEvl' => $idEvl
        ];
    }

    /**
     * Guardar resultado proveniente de RSS
     */
    private function guardarResultadoRSS($busquedaId, $titulo, $descripcion, $organismo, $presupuesto, $fechaPub, $fechaLim, $url, $cpvs, $keywords)
    {
        try {
            // Verificar si ya existe
            $stmt = $this->pdo->prepare(
                "SELECT id FROM resultados WHERE url_detalle = ? AND busqueda_id = ?"
            );
            $stmt->execute([$url, $busquedaId]);
            if ($stmt->fetch()) {
                return false;
            }

            // Insertar
            $stmt = $this->pdo->prepare(
                "INSERT INTO resultados (
                busqueda_id, fuente_id, titulo, descripcion_corta, organismo,
                presupuesto, fecha_publicacion, fecha_limite, url_detalle,
                codigos_cpv, palabras_coincidentes, relevancia
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
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
}
