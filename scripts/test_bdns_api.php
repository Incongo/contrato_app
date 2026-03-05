<?php
// scripts/test_bdns_api.php
// Prueba de conexión a la API real de BDNS

echo "🔍 PROBANDO API DE BDNS\n";
echo "======================\n\n";

// Posibles endpoints de BDNS
$endpoints = [
    'https://www.pap.hacienda.gob.es/bdnstrans/rest/convocatorias',
    'https://www.pap.hacienda.gob.es/bdnstrans/api/convocatorias',
    'https://www.infosubvenciones.es/bdnstrans/rest/convocatorias',
    'https://www.pap.hacienda.gob.es/bdnstrans/GE/es/convocatorias/buscar?formato=json'
];

// Parámetros de prueba (servicios audiovisuales)
$params = [
    'texto' => 'producción audiovisual',
    'tipo' => 'subvencion',
    'pagina' => 1,
    'tamPagina' => 10,
    'formato' => 'json'
];

foreach ($endpoints as $endpoint) {
    echo "\n📡 Probando: $endpoint\n";
    
    // Construir URL con parámetros
    $url = $endpoint . '?' . http_build_query($params);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: application/json',
        'Accept-Language: es-ES,es;q=0.9'
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    curl_close($ch);
    
    echo "   HTTP: $httpCode\n";
    echo "   Tipo: $contentType\n";
    
    if ($response) {
        $tamaño = strlen($response);
        echo "   Tamaño: $tamaño bytes\n";
        
        // Intentar decodificar JSON
        $json = json_decode($response, true);
        if ($json) {
            echo "   ✅ Es JSON válido\n";
            echo "   Estructura: " . (is_array($json) ? 'array' : 'objeto') . "\n";
            if (is_array($json)) {
                echo "   Elementos: " . count($json) . "\n";
                if (count($json) > 0) {
                    echo "\n   Primer elemento:\n";
                    print_r(array_slice($json[0] ?? $json, 0, 5));
                }
            }
        } else {
            echo "   ❌ No es JSON válido\n";
            echo "   Primeros 200 caracteres:\n";
            echo substr($response, 0, 200) . "\n";
        }
    } else {
        echo "   ❌ Sin respuesta\n";
    }
}

echo "\n✅ Prueba completada\n";