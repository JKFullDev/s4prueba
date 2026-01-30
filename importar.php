<?php
// importar.php
// Este script es el encargado de leer los ficheros externos (CSV, JSON, XML)
// y meterlos todos de golpe en nuestra base de datos Mongo

require_once 'db.php';

// Preparo un "BulkWrite". Esto es como un carrito de la compra donde voy metiendo
// todas las operaciones (inserts) para enviarlas al servidor de una sola vez.
// Es mucho más eficiente que ir insertando uno a uno.
$bulk = new MongoDB\Driver\BulkWrite;

// Variables para controlar si hemos encontrado algo
$hay_datos = false;
$contador = 0;

// === 1. LEER EL CSV ===
// Busco el archivo dentro de la carpeta 'data' 
$fichero_csv = 'data/datos.csv';

if (file_exists($fichero_csv)) {
    // Abro el archivo en modo lectura
    if (($gestor = fopen($fichero_csv, "r")) !== FALSE) {

        // Voy leyendo línea a línea. El separador es la coma
        while (($datos = fgetcsv($gestor, 1000, ",")) !== FALSE) {

            // Los CSV a veces traen el booleano como texto "true" o "1".
            // Aquí lo convierto a un booleano de verdad (true/false) para que Mongo no se líe
            $esSexy = (strtolower($datos[4]) == 'true' || $datos[4] == '1');

            // Construyo el array con la estructura exacta que pide el enunciado
            // 'Alumno' es un array dentro de otro (documento embebido)
            $doc = [
                'Numero' => (int)$datos[0], // Fuerzo que sea numero entero
                'Alumno' => [
                    'Nombre' => $datos[1],
                    'Apellidos' => $datos[2],
                    'Sexo' => $datos[3],
                    'es_profe_sexi' => $esSexy
                ]
            ];

            // Añado este documento a mi "carrito" de inserciones
            $bulk->insert($doc);
            $hay_datos = true;
            $contador++;
        }
        fclose($gestor); // Cierro el archivo, siempre hay que ser limpio.
        echo "¡CSV procesado correctamente!<br>";
    }
} else {
    echo "¡No es posible encontrar el archivo CSV en la carpeta 'data'!<br>";
}

// === 2. LEER EL JSON ===
$fichero_json = 'data/datos.json';

if (file_exists($fichero_json)) {
    // Leo todo el contenido del fichero de golpe
    $contenido = file_get_contents($fichero_json);
    // Decodifico el texto JSON a un array de PHP
    $json = json_decode($contenido, true);

    if ($json) {
        foreach ($json as $item) {
            // El JSON ya viene con la estructura bonita, así que mapeo directo
            $doc = [
                'Numero' => (int)$item['numero'],
                'Alumno' => [
                    'Nombre' => $item['alumno']['nombre'],
                    'Apellidos' => $item['alumno']['apellidos'],
                    'Sexo' => $item['alumno']['sexo'],
                    // Me aseguro de que esto sea booleano
                    'es_profe_sexi' => (bool)$item['alumno']['es_profe_sexi']
                ]
            ];
            $bulk->insert($doc);
            $hay_datos = true;
            $contador++;
        }
        echo "¡JSON procesado correctamente!<br>";
    }
} else {
    echo "¡No es posible encontrar el archivo JSON en la carpeta 'data'!<br>";
}

// === 3. LEER EL XML ===
$fichero_xml = 'data/datos.xml';

if (file_exists($fichero_xml)) {
    // Cargo el XML como un objeto simple
    $xml = simplexml_load_file($fichero_xml);

    // Recorro cada etiqueta <fila>
    foreach ($xml->fila as $fila) {
        // En XML todo es texto, así que compruebo manualmente si pone "true" o "1"
        $esSexy = ((string)$fila->alumno->es_profe_sexi === 'true' || (string)$fila->alumno->es_profe_sexi === '1');

        $doc = [
            // Los casting (int), (string) son vitales aquí porque simplexml devuelve objetos raros
            'Numero' => (int)$fila->numero,
            'Alumno' => [
                'Nombre' => (string)$fila->alumno->nombre,
                'Apellidos' => (string)$fila->alumno->apellidos,
                'Sexo' => (string)$fila->alumno->sexo,
                'es_profe_sexi' => $esSexy
            ]
        ];
        $bulk->insert($doc);
        $hay_datos = true;
        $contador++;
    }
    echo "¡XML procesado correctamente!<br>";
} else {
    echo "¡No es posible encontrar el archivo XML en la carpeta 'data'!<br>";
}

// === GUARDAR TODO EN MONGO ===
if ($hay_datos) {
    try {
        // Ejecuto el BulkWrite. Aquí es donde realmente se guardan los datos
        $result = $manager->executeBulkWrite($namespace, $bulk);

        echo "<h2>¡Importación completada!</h2>";
        echo "Se han insertado un total de <strong>$contador</strong> alumnos en la base de datos.<br>";
        echo "<br><a href='index.php'>VOLVER AL LISTADO</a>";
    } catch (MongoDB\Driver\Exception\Exception $e) {
        echo "¡Error fatal! | " . $e->getMessage();
    }
} else {
    echo "¡No se han encontrado datos en ningún archivo. Revisa la carpeta 'data'!";
}
