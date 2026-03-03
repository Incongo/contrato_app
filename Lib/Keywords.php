<?php
// app/Lib/Keywords.php
class Keywords
{

    // Palabras clave de CIENCIA (lo que documentamos)
    public static function getCiencia()
    {
        return [
            'divulgación científica',
            'cultura científica',
            'comunicación científica',
            'investigación',
            'astronomía',
            'astrofísica',
            'cambio climático',
            'medio ambiente',
            'biodiversidad',
            'naturaleza',
            'tecnología',
            'innovación',
            'ciencia ciudadana',
            'espacio',
            'universo',
            'observatorio',
            'turismo astronómico',
            'rutas astronómicas',
            'sostenibilidad',
            'transición ecológica',
            'ecosistema',
            'fauna',
            'flora',
            'inteligencia artificial',
            'robótica'
        ];
    }

    // Palabras clave de AUDIOVISUAL (lo que hacemos)
    public static function getAudiovisual()
    {
        return [
            'producción audiovisual',
            'servicios audiovisuales',
            'producción de vídeo',
            'vídeo divulgativo',
            'documental',
            'streaming',
            'retransmisión',
            'cobertura audiovisual',
            'grabación de eventos',
            'contenido multimedia',
            'animación científica',
            'infografía',
            'postproducción',
            'edición de vídeo',
            'cámara',
            'realización',
            'contenido educativo',
            'pieza audiovisual',
            'cápsula divulgativa'
        ];
    }

    // Palabras a EXCLUIR (opcional)
    public static function getExcluir()
    {
        return [
            'hardware',
            'software',
            'equipamiento informático',
            'material de oficina'
        ];
    }

    // CPV prioritarios (códigos oficiales)
    public static function getCPVPrioritarios()
    {
        return [
            '92100000', // Servicios cinematográficos y de vídeo
            '92111000', // Producción de películas
            '92112000', // Producción de vídeo
            '92113000', // Postproducción
            '92220000', // Servicios de televisión
            '79341000', // Servicios de publicidad
            '79961000', // Servicios de fotografía
            '73100000', // I+D
            '73200000', // Consultoría I+D
            '73300000', // Diseño y ejecución I+D
            '80500000', // Servicios de formación
            '92500000'  // Servicios de bibliotecas, archivos, museos
        ];
    }

    // Para facilitar edición desde admin (futuro)
    public static function guardarCiencia($lista)
    {
        // Implementar en el futuro
    }
}
