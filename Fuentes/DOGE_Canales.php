<?php
// Fuentes/DOGE_Canales.php
// Configuración de TODOS los canales RSS del DOGE

class DOGE_Canales
{
    // ============================================
    // 1. SECCIONES COMPLETAS (basado en tu captura)
    // ============================================
    public static function getSecciones()
    {
        return [
            'sumario' => 'https://www.xunta.gal/diario-oficial-galicia/rss/Sumario_gl.rss',
            'disposiciones' => 'https://www.xunta.gal/diario-oficial-galicia/rss/Seccion1_gl.rss',      // I. Disposiciones generales
            'autoridades_personal_a' => 'https://www.xunta.gal/diario-oficial-galicia/rss/Seccion2a_gl.rss', // II.A Cesamentos
            'autoridades_personal_b' => 'https://www.xunta.gal/diario-oficial-galicia/rss/Seccion2b_gl.rss', // II.B Nomeamentos
            'autoridades_personal_c' => 'https://www.xunta.gal/diario-oficial-galicia/rss/Seccion2c_gl.rss', // II.C Substitucións
            'otras_disposiciones' => 'https://www.xunta.gal/diario-oficial-galicia/rss/Seccion3_gl.rss', // III. Outras disposicións
            'oposiciones' => 'https://www.xunta.gal/diario-oficial-galicia/rss/Seccion4_gl.rss',         // IV. Oposicións e concursos
            'administracion_justicia' => 'https://www.xunta.gal/diario-oficial-galicia/rss/Seccion5_gl.rss', // V. Administración de xustiza
            'anuncios_autonomicos' => 'https://www.xunta.gal/diario-oficial-galicia/rss/Seccion6a_gl.rss',   // VI.A Administración autonómica
            'anuncios_locales' => 'https://www.xunta.gal/diario-oficial-galicia/rss/Seccion6b_gl.rss',       // VI.B Administración local
            'anuncios_otros' => 'https://www.xunta.gal/diario-oficial-galicia/rss/Seccion6c_gl.rss',         // VI.C Outros anuncios
        ];
    }

    // ============================================
    // 2. TEMÁTICAS (las que aparecen en tu captura)
    // ============================================
    public static function getTematicas()
    {
        return [
            // Administración pública
            'administracion_publica' => 'https://www.xunta.gal/diario-oficial-galicia/rss/Taxonomia21008_gl.rss',

            // Cultura, ocio y deporte
            'cultura' => 'https://www.xunta.gal/diario-oficial-galicia/rss/Taxonomia21002_gl.rss',

            // Enseñanza y formación
            'educacion' => 'https://www.xunta.gal/diario-oficial-galicia/rss/Taxonomia21003_gl.rss',

            // Salud, asistencia sanitaria y servicios sociales
            'salud' => 'https://www.xunta.gal/diario-oficial-galicia/rss/Taxonomia21004_gl.rss',

            // Ciencia y tecnología
            'ciencia_tecnologia' => 'https://www.xunta.gal/diario-oficial-galicia/rss/Taxonomia21001_gl.rss',

            // Economía, empresa y empleo
            'economia_empresa' => 'https://www.xunta.gal/diario-oficial-galicia/rss/Taxonomia21006_gl.rss',

            // Medio ambiente
            'medio_ambiente' => 'https://www.xunta.gal/diario-oficial-galicia/rss/Taxonomia21005_gl.rss',

            // Territorio, vivienda y transporte
            'territorio' => 'https://www.xunta.gal/diario-oficial-galicia/rss/Taxonomia21007_gl.rss',

            // Otras temáticas de tu captura
            'agricultura' => 'https://www.xunta.gal/diario-oficial-galicia/rss/Taxonomia22001_gl.rss',
            'pesca' => 'https://www.xunta.gal/diario-oficial-galicia/rss/Taxonomia22002_gl.rss',
            'industria' => 'https://www.xunta.gal/diario-oficial-galicia/rss/Taxonomia22003_gl.rss',
            'energia' => 'https://www.xunta.gal/diario-oficial-galicia/rss/Taxonomia22004_gl.rss',
            'minas' => 'https://www.xunta.gal/diario-oficial-galicia/rss/Taxonomia22005_gl.rss',
            'comercio' => 'https://www.xunta.gal/diario-oficial-galicia/rss/Taxonomia22006_gl.rss',
            'turismo' => 'https://www.xunta.gal/diario-oficial-galicia/rss/Taxonomia22007_gl.rss',
            'consumo' => 'https://www.xunta.gal/diario-oficial-galicia/rss/Taxonomia22008_gl.rss',
        ];
    }

    // ============================================
    // 3. TIPOS DE DOCUMENTO (si existen)
    // ============================================
    public static function getTiposDocumento()
    {
        return [
            'anuncios' => 'https://www.xunta.gal/diario-oficial-galicia/rss/TipoAnuncio_gl.rss',
            'resoluciones' => 'https://www.xunta.gal/diario-oficial-galicia/rss/TipoResolucion_gl.rss',
            'decretos' => 'https://www.xunta.gal/diario-oficial-galicia/rss/TipoDecreto_gl.rss',
            'leyes' => 'https://www.xunta.gal/diario-oficial-galicia/rss/TipoLei_gl.rss',
            'edictos' => 'https://www.xunta.gal/diario-oficial-galicia/rss/TipoEdicto_gl.rss',
        ];
    }

    // ============================================
    // 4. POR COLECTIVOS (de tu captura)
    // ============================================
    public static function getColectivos()
    {
        return [
            'agricultores' => 'https://www.xunta.gal/diario-oficial-galicia/rss/ColectivoAgricultores_gl.rss',
            'asociaciones' => 'https://www.xunta.gal/diario-oficial-galicia/rss/ColectivoAsociacions_gl.rss',
            'ciudadanos' => 'https://www.xunta.gal/diario-oficial-galicia/rss/ColectivoCidadans_gl.rss',
            'entidades_locales' => 'https://www.xunta.gal/diario-oficial-galicia/rss/ColectivoEntidadesLocais_gl.rss',
            'empresas' => 'https://www.xunta.gal/diario-oficial-galicia/rss/ColectivoEmpresas_gl.rss',
            'investigadores' => 'https://www.xunta.gal/diario-oficial-galicia/rss/ColectivoInvestigadores_gl.rss',
            'jovenes' => 'https://www.xunta.gal/diario-oficial-galicia/rss/ColectivoMozos_gl.rss',
            'autonomos' => 'https://www.xunta.gal/diario-oficial-galicia/rss/ColectivoAutonomos_gl.rss',
            'desempleados' => 'https://www.xunta.gal/diario-oficial-galicia/rss/ColectivoDesempregados_gl.rss',
            'discapacidad' => 'https://www.xunta.gal/diario-oficial-galicia/rss/ColectivoDiscapacidade_gl.rss',
            'emprendedores' => 'https://www.xunta.gal/diario-oficial-galicia/rss/ColectivoEmprendedores_gl.rss',
            'mujeres' => 'https://www.xunta.gal/diario-oficial-galicia/rss/ColectivoMulleres_gl.rss',
        ];
    }

    // ============================================
    // 5. POR RANGOS NORMATIVOS
    // ============================================
    public static function getRangos()
    {
        return [
            'leyes' => 'https://www.xunta.gal/diario-oficial-galicia/rss/RangoLei_gl.rss',
            'decretos' => 'https://www.xunta.gal/diario-oficial-galicia/rss/RangoDecreto_gl.rss',
            'ordenes' => 'https://www.xunta.gal/diario-oficial-galicia/rss/RangoOrde_gl.rss',
            'resoluciones' => 'https://www.xunta.gal/diario-oficial-galicia/rss/RangoResolucion_gl.rss',
            'acuerdos' => 'https://www.xunta.gal/diario-oficial-galicia/rss/RangoAcordo_gl.rss',
            'anuncios' => 'https://www.xunta.gal/diario-oficial-galicia/rss/RangoAnuncio_gl.rss',
        ];
    }

    // ============================================
    // 6. OBTENER TODAS LAS URLs
    // ============================================
    public static function getTodasLasUrls()
    {
        $urls = [];

        foreach (self::getSecciones() as $nombre => $url) {
            $urls["seccion_$nombre"] = $url;
        }

        foreach (self::getTematicas() as $nombre => $url) {
            $urls["tema_$nombre"] = $url;
        }

        foreach (self::getTiposDocumento() as $nombre => $url) {
            $urls["tipo_$nombre"] = $url;
        }

        foreach (self::getColectivos() as $nombre => $url) {
            $urls["colectivo_$nombre"] = $url;
        }

        foreach (self::getRangos() as $nombre => $url) {
            $urls["rango_$nombre"] = $url;
        }

        return $urls;
    }

    // ============================================
    // 7. OBTENER URLs POR GRUPO (opcional)
    // ============================================
    public static function getUrlsPorGrupo($grupo)
    {
        switch ($grupo) {
            case 'secciones':
                return self::getSecciones();
            case 'tematicas':
                return self::getTematicas();
            case 'tipos':
                return self::getTiposDocumento();
            case 'colectivos':
                return self::getColectivos();
            case 'rangos':
                return self::getRangos();
            default:
                return [];
        }
    }
}
