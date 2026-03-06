<?php
// Fuentes/DIBAAPI.php
// Diputación de Barcelona - API de Contratación
// Documentación: https://api.diba.cat/dadesobertes/cido/v1/contractacions

require_once __DIR__ . '/../Core/Database.php';

class DIBAAPI
{
    private $pdo;
    private $fuenteId;
    private $apiBase = 'https://api.diba.cat/dadesobertes/cido/v1/contractacions';

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
    // Mapeo de IDs de materias a nombres descriptivos (basado en los IDs de tu URL)
    private $materias = [
        230 => 'Publicitat',
        235 => 'Comunicació',
        244 => 'Disseny gràfic',
        246 => 'Producció audiovisual',
        252 => 'Fotografia',
        255 => 'Multimèdia',
        277 => 'Màrqueting',
        278 => 'Publicitat institucional',
        281 => 'Continguts digitals'
    ];

    public function __construct()
    {
        $this->pdo = Database::getInstance();
        $this->fuenteId = $this->obtenerFuenteId();
    }

    private function obtenerFuenteId()
    {
        try {
            $stmt = $this->pdo->prepare("SELECT id FROM fuentes WHERE nombre_corto = 'diba'");
            $stmt->execute();
            $result = $stmt->fetch();

            if (!$result) {
                $stmt = $this->pdo->prepare("INSERT INTO fuentes (nombre, nombre_corto, tipo, url_base, script_asociado) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([
                    'Diputació de Barcelona - Contractació',
                    'diba',
                    'licitacion',
                    'https://api.diba.cat',
                    'DIBAAPI.php'
                ]);
                return $this->pdo->lastInsertId();
            }
            return $result['id'];
        } catch (PDOException $e) {
            die("Error con fuente DIBA: " . $e->getMessage());
        }
    }

    /**
     * Método principal: ejecutar búsqueda
     */
    public function ejecutar($busquedaId, $dias = 360)
    {
        echo "\n🔍 Buscando en Diputació de Barcelona (API)...\n";

        try {
            $busqueda = $this->getBusqueda($busquedaId);
            if (!$busqueda) throw new Exception("Búsqueda no encontrada");

            // Construir URL con filtros
            $fechaLimite = date('Y-m-d', strtotime("-$dias days"));

            $params = [
                'filter[idEstat]' => '2,3', // 2=En licitación, 3=Publicadas
                'filter[idProcediment]' => '1,2,3,5,6,7,8,10,11,13', // Procedimientos abiertos
                'filter[materies.id]' => implode(',', array_keys($this->materias)), // Las que nos interesan
                // 'filter[maxDataPublicacioDocument]' => ">=$fechaLimite", // Últimos X días
                'sort' => '-maxDataPublicacioDocument'
            ];

            $url = $this->apiBase . '?' . http_build_query($params);

            echo "📡 Consultando API: $url\n";

            // Llamada a la API
            $jsonData = $this->callAPI($url);
            if (!$jsonData) {
                throw new Exception("No se pudo obtener datos de la API");
            }

            $datos = json_decode($jsonData, true);
            if (!isset($datos['data']) || !is_array($datos['data'])) {
                throw new Exception("Formato de respuesta inválido");
            }

            echo "📊 Total registros: " . count($datos['data']) . "\n";

            // Procesar cada contrato
            $encontrados = 0;
            foreach ($datos['data'] as $item) {
                if ($this->procesarItem($item, $busquedaId)) {
                    $encontrados++;
                }
            }

            echo "✅ DIBA procesada: $encontrados nuevos resultados\n";
            return $encontrados;
        } catch (Exception $e) {
            echo "❌ Error: " . $e->getMessage() . "\n";
            return 0;
        }
    }

    /**
     * Llamada a la API con cURL
     */
    private function callAPI($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: application/json',
            'Content-Type: application/json'
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || !$response) {
            echo "   HTTP $httpCode\n";
            return null;
        }

        return $response;
    }

    /**
     * Procesar un item individual
     */
    private function procesarItem($item, $busquedaId)
    {
        try {
            // Extraer campos básicos
            $titulo = $item['titol'] ?? $item['objecte'] ?? 'Sin título';
            $descripcion = $item['objecte'] ?? '';
            $organismo = $item['ens']['nom'] ?? 'Diputació de Barcelona';
            $presupuesto = $item['pressupost']['import'] ?? null;
            $fechaPublicacion = $item['maxDataPublicacioDocument'] ?? $item['dataPublicacio'] ?? null;
            $fechaLimite = $item['dataPresentacio'] ?? null;
            $url = $item['enllacExpedient'] ?? 'https://www.diba.cat';

            // Extraer materias para clasificar
            $materiasItem = [];
            if (isset($item['materies']) && is_array($item['materies'])) {
                foreach ($item['materies'] as $m) {
                    if (isset($this->materias[$m['id']])) {
                        $materiasItem[] = $this->materias[$m['id']];
                    }
                }
            }

            // Keywords a buscar (de la búsqueda del usuario)
            $palabrasBusqueda = array_map('trim', explode(',', $this->getBusqueda($busquedaId)['palabras_clave']));

            // Buscar coincidencias
            $textoCompleto = $titulo . ' ' . $descripcion . ' ' . implode(' ', $materiasItem);
            $keywordsEncontradas = $this->buscarKeywords($textoCompleto, $palabrasBusqueda);

            // Siempre guardamos si tiene materias de nuestro interés, aunque no haya keywords
            if (!empty($materiasItem)) {
                // Añadir las materias a las keywords encontradas
                $keywordsEncontradas = array_merge($keywordsEncontradas, $materiasItem);
            }

            if (empty($keywordsEncontradas)) {
                return false;
            }

            // Guardar en BD
            return $this->guardarResultado(
                $busquedaId,
                $titulo,
                $descripcion,
                $organismo,
                $presupuesto,
                $fechaPublicacion ? date('Y-m-d', strtotime($fechaPublicacion)) : null,
                $fechaLimite ? date('Y-m-d', strtotime($fechaLimite)) : null,
                $url,
                $materiasItem,
                $keywordsEncontradas
            );
        } catch (Exception $e) {
            error_log("Error procesando item DIBA: " . $e->getMessage());
            return false;
        }
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
     * Guardar resultado en BD
     */
    private function guardarResultado($busquedaId, $titulo, $descripcion, $organismo, $presupuesto, $fechaPub, $fechaLim, $url, $materias, $keywords)
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

            $palabrasCoincidentes = json_encode($keywords, JSON_UNESCAPED_UNICODE);

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
                $materias ? implode(',', $materias) : null,
                $palabrasCoincidentes,
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
        echo "✅ DIBA API configurada correctamente\n";
        echo "📁 Fuente ID: " . $this->fuenteId . "\n";
        echo "📡 Endpoint: " . $this->apiBase . "\n";
        echo "📋 Materias a filtrar: " . implode(', ', $this->materias) . "\n";
        return true;
    }
}
