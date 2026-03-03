<?php
// Fuentes/BOE.php
// Boletín Oficial del Estado - RSS de licitaciones

require_once __DIR__ . '/../Core/Database.php';

class BOE
{
    private $pdo;
    private $fuenteId;

    public function __construct()
    {
        $this->pdo = Database::getInstance();
        $this->fuenteId = $this->obtenerFuenteId();
    }

    private function obtenerFuenteId()
    {
        $stmt = $this->pdo->prepare(
            "SELECT id FROM fuentes WHERE nombre_corto = 'boe'"
        );
        $stmt->execute();
        $result = $stmt->fetch();

        if (!$result) {
            $stmt = $this->pdo->prepare(
                "INSERT INTO fuentes (nombre, nombre_corto, tipo, url_base, script_asociado) 
                 VALUES (?, ?, ?, ?, ?)"
            );
            $stmt->execute([
                'Boletín Oficial del Estado',
                'boe',
                'licitacion',
                'https://www.boe.es',
                'BOE.php'
            ]);
            return $this->pdo->lastInsertId();
        }
        return $result['id'];
    }

    public function ejecutar($busquedaId)
    {
        echo "\n🔍 Buscando en BOE...\n";

        // RSS de licitaciones del BOE
        $rssUrl = 'https://www.boe.es/rss/boe.php?s=2'; // Sección 2: Licitaciones

        $xmlData = file_get_contents($rssUrl);
        if (!$xmlData) {
            echo "❌ No se pudo obtener RSS\n";
            return 0;
        }

        $rss = simplexml_load_string($xmlData);
        if (!$rss || !isset($rss->channel->item)) {
            echo "❌ RSS inválido\n";
            return 0;
        }

        $busqueda = $this->getBusqueda($busquedaId);
        $palabrasBusqueda = array_map('trim', explode(',', $busqueda['palabras_clave']));

        $encontrados = 0;
        foreach ($rss->channel->item as $item) {
            $titulo = (string)$item->title;
            $link = (string)$item->link;
            $descripcion = (string)$item->description;

            // Buscar palabras clave
            $texto = $titulo . ' ' . $descripcion;
            $keywordsEncontradas = $this->buscarKeywords($texto, $palabrasBusqueda);

            if (!empty($keywordsEncontradas)) {
                if ($this->guardarResultado($busquedaId, $titulo, $descripcion, $link, $keywordsEncontradas)) {
                    $encontrados++;
                }
            }
        }

        echo "✅ BOE procesado: $encontrados resultados\n";
        return $encontrados;
    }

    private function buscarKeywords($texto, $keywords)
    {
        $encontradas = [];
        $textoLower = mb_strtolower($texto, 'UTF-8');

        foreach ($keywords as $keyword) {
            $keywordLower = mb_strtolower(trim($keyword), 'UTF-8');
            if (strpos($textoLower, $keywordLower) !== false) {
                $encontradas[] = $keyword;
            }
        }
        return $encontradas;
    }

    private function guardarResultado($busquedaId, $titulo, $descripcion, $url, $keywords)
    {
        try {
            $stmt = $this->pdo->prepare(
                "INSERT INTO resultados (busqueda_id, fuente_id, titulo, descripcion_corta, url_detalle, palabras_coincidentes, relevancia)
                 VALUES (?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt->execute([
                $busquedaId,
                $this->fuenteId,
                $titulo,
                mb_substr($descripcion, 0, 500),
                $url,
                json_encode($keywords),
                count($keywords)
            ]);
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }

    private function getBusqueda($busquedaId)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM busquedas WHERE id = ?");
        $stmt->execute([$busquedaId]);
        return $stmt->fetch();
    }

    public function probar()
    {
        echo "✅ BOE configurado correctamente\n";
        return true;
    }
}
