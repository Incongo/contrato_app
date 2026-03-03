<?php
// Fuentes/DOGE_Canales.php
// Configuración de canales RSS del DOGE basada en la captura

class DOGE_Canales
{

    // Secciones que pueden contener licitaciones de servicios audiovisuales
    public static function getSecciones()
    {
        return [
            'sumario' => [
                'nombre' => 'Sumario completo',
                'url' => 'https://www.xunta.gal/diario-oficial-galicia/rss/Sumario_gl.rss',
                'relevancia' => 'baja' // Demasiado general
            ],
            'disposiciones' => [
                'nombre' => 'III. Outras disposicións',
                'url' => 'https://www.xunta.gal/diario-oficial-galicia/rss/Seccion3_gl.rss',
                'relevancia' => 'media' // Puede incluir convocatorias
            ],
            'oposiciones' => [
                'nombre' => 'IV. Oposicións e concursos',
                'url' => 'https://www.xunta.gal/diario-oficial-galicia/rss/Seccion4_gl.rss',
                'relevancia' => 'alta' // ¡Concursos públicos!
            ],
            'anuncios_autonomicos' => [
                'nombre' => 'VI. Anuncios - a) Administración autonómica',
                'url' => 'https://www.xunta.gal/diario-oficial-galicia/rss/Seccion6a_gl.rss',
                'relevancia' => 'muy_alta' // LICITACIONES
            ],
            'anuncios_locales' => [
                'nombre' => 'VI. Anuncios - b) Administración local',
                'url' => 'https://www.xunta.gal/diario-oficial-galicia/rss/Seccion6b_gl.rss',
                'relevancia' => 'muy_alta' // Ayuntamientos
            ],
            'anuncios_otros' => [
                'nombre' => 'VI. Anuncios - c) Outros anuncios',
                'url' => 'https://www.xunta.gal/diario-oficial-galicia/rss/Seccion6c_gl.rss',
                'relevancia' => 'media'
            ]
        ];
    }

    // Temáticas prioritarias para audiovisual + ciencia
    public static function getTematicas()
    {
        return [
            'ciencia_tecnologia' => [
                'nombre' => 'Ciencia e tecnoloxía',
                'url' => 'https://www.xunta.gal/diario-oficial-galicia/rss/Taxonomia21001_gl.rss', // Asumiendo código
                'relevancia' => 'muy_alta',
                'keywords_extra' => ['investigación', 'divulgación', 'científico']
            ],
            'cultura' => [
                'nombre' => 'Cultura, ocio e deporte',
                'url' => 'https://www.xunta.gal/diario-oficial-galicia/rss/Taxonomia21002_gl.rss',
                'relevancia' => 'alta',
                'keywords_extra' => ['cultural', 'audiovisual', 'eventos']
            ],
            'educacion' => [
                'nombre' => 'Ensino e formación',
                'url' => 'https://www.xunta.gal/diario-oficial-galicia/rss/Taxonomia21003_gl.rss',
                'relevancia' => 'alta',
                'keywords_extra' => ['educativo', 'formación', 'didáctico']
            ],
            'salud' => [
                'nombre' => 'Saúde, asistencia sanitaria e servizos sociais',
                'url' => 'https://www.xunta.gal/diario-oficial-galicia/rss/Taxonomia21004_gl.rss',
                'relevancia' => 'media',
                'keywords_extra' => ['salud', 'sanitario', 'divulgación médica']
            ],
            'medio_ambiente' => [
                'nombre' => 'Medio ambiente',
                'url' => 'https://www.xunta.gal/diario-oficial-galicia/rss/Taxonomia21005_gl.rss',
                'relevancia' => 'muy_alta', // Cambio climático, naturaleza
                'keywords_extra' => ['cambio climático', 'biodiversidad', 'sostenibilidad']
            ],
            'economia_empresa' => [
                'nombre' => 'Economía, empresa e emprego',
                'url' => 'https://www.xunta.gal/diario-oficial-galicia/rss/Taxonomia21006_gl.rss',
                'relevancia' => 'media',
                'keywords_extra' => ['empresa', 'emprendimiento']
            ],
            'territorio' => [
                'nombre' => 'Territorio, vivenda e transporte',
                'url' => 'https://www.xunta.gal/diario-oficial-galicia/rss/Taxonomia21007_gl.rss',
                'relevancia' => 'baja'
            ]
        ];
    }

    // Tipos de documentos que nos interesan
    public static function getTiposDocumento()
    {
        return [
            'anuncio' => [
                'nombre' => 'Anuncio',
                'patron' => '/Anuncio/',
                'relevancia' => 'alta'
            ],
            'resolucion' => [
                'nombre' => 'Resolución',
                'patron' => '/Resolución/',
                'relevancia' => 'media' // Puede ser convocatoria
            ],
            'convenio' => [
                'nombre' => 'Convenio',
                'patron' => '/Convenio/',
                'relevancia' => 'alta' // Colaboraciones
            ],
            'contrato' => [
                'nombre' => 'Contrato',
                'patron' => '/Contrato/',
                'relevancia' => 'muy_alta' // DIRECTAMENTE LO QUE BUSCAMOS
            ],
            'licitacion' => [
                'nombre' => 'Licitación',
                'patron' => '/Licitación/i',
                'relevancia' => 'muy_alta'
            ],
            'concurso' => [
                'nombre' => 'Concurso',
                'patron' => '/Concurso/',
                'relevancia' => 'muy_alta'
            ]
        ];
    }

    // Palabras clave de CIENCIA (lo que documentamos)
    public static function getPalabrasCiencia()
    {
        return [
            // Divulgación general
            'divulgación científica',
            'cultura científica',
            'comunicación científica',
            'investigación',
            'I+D',
            'I+D+i',

            // Astronomía y espacio
            'astronomía',
            'astrofísica',
            'espacio',
            'telescopio',
            'observatorio astronómico',
            'turismo astronómico',

            // Medio ambiente y clima
            'cambio climático',
            'medio ambiente',
            'sostenibilidad',
            'transición ecológica',
            'biodiversidad',
            'naturaleza',
            'ecosistema',
            'conservación ambiental',

            // Tecnología
            'tecnología',
            'innovación',
            'inteligencia artificial',
            'ciencia ciudadana'
        ];
    }

    // Palabras clave de AUDIOVISUAL (lo que hacemos)
    public static function getPalabrasAudiovisual()
    {
        return [
            // Servicios generales
            'producción audiovisual',
            'servicios audiovisuales',
            'producción de vídeo',
            'contenido multimedia',
            'realización audiovisual',

            // Tipos de trabajo
            'vídeo divulgativo',
            'documental',
            'streaming',
            'retransmisión',
            'cobertura audiovisual',
            'grabación de eventos',
            'animación',
            'infografía',
            'postproducción',
            'edición de vídeo',

            // Contenido específico
            'contenido educativo',
            'material didáctico',
            'campaña audiovisual',
            'pieza audiovisual',

            // Equipo y personal
            'cámara',
            'equipo de grabación',
            'servicios de fotografía'
        ];
    }

    // Combinar todas las palabras clave (para filtrado)
    public static function getTodasPalabras()
    {
        return array_merge(
            self::getPalabrasCiencia(),
            self::getPalabrasAudiovisual()
        );
    }

    // Obtener URLs relevantes para una búsqueda
    public static function getUrlsRelevantes($relevancia_minima = 'media')
    {
        $urls = [];

        $secciones = self::getSecciones();
        foreach ($secciones as $key => $seccion) {
            if ($this->compararRelevancia($seccion['relevancia'], $relevancia_minima)) {
                $urls[$key] = $seccion['url'];
            }
        }

        $tematicas = self::getTematicas();
        foreach ($tematicas as $key => $tematica) {
            if ($this->compararRelevancia($tematica['relevancia'], $relevancia_minima)) {
                $urls[$key] = $tematica['url'];
            }
        }

        return $urls;
    }

    private static function compararRelevancia($actual, $minima)
    {
        $niveles = ['baja' => 1, 'media' => 2, 'alta' => 3, 'muy_alta' => 4];
        return $niveles[$actual] >= $niveles[$minima];
    }
}
