<?php
// importar.php
// Este script se encarga de leer los 3 archivos y organizarlos.
// En lugar de guardar uno a uno. Primero los agrupo en PHP por 'Fila'
// y luego creo un documento por cada fila con todos sus alumnos dentro.

require_once 'db.php';

// Preparo un "BulkWrite". Es como un carrito de la compra donde voy
// metiendo todas las operaciones que quiero hacer de golpe
// Es mas eficiente que ir una a una
$bulk = new MongoDB\Driver\BulkWrite;

// Aquí voy a ir guardando los alumnos temporalmente:
// $filas_agrupadas[1] = [alumno1, alumno2...]
// $filas_agrupadas[2] = [alumno3...]
$filas_agrupadas = [];
$hay_datos = false;

// --- 1. LEER EL CSV (El separador es punto y coma ';') ---
// Busco el archivo en la carpeta data
$fichero_csv = 'data/datos.csv';
if (file_exists($fichero_csv)) {
    if (($gestor = fopen($fichero_csv, "r")) !== FALSE) {
        // Me salto la primera línea porque son los títulos (cabeceras)
        fgetcsv($gestor, 1000, ";");

        while (($datos = fgetcsv($gestor, 1000, ";")) !== FALSE) {
            // El CSV viene así: NOMBRE;APELLIDOS;FILA;SEXO;ES_PROFE_SEXI
            // Ojo: Fila está en la posición 2
            $numFila = (int)$datos[2];

            // Arreglo el booleano del profe sexy
            $esSexy = ($datos[4] == '1' || strtolower($datos[4]) == 'true');

            // Lo añado a mi array temporal en su fila correspondiente
            $filas_agrupadas[$numFila][] = [
                'Nombre' => $datos[0],
                'Apellidos' => $datos[1],
                'Sexo' => $datos[3],
                'es_profe_sexi' => $esSexy
            ];
            $hay_datos = true;
        }
        fclose($gestor);
        echo "¡CSV procesado correctamente!<br>";
    }
}

// --- 2. LEER EL JSON ---
$fichero_json = 'data/datos.json';
if (file_exists($fichero_json)) {
    $contenido = file_get_contents($fichero_json);
    $json = json_decode($contenido, true);
    if ($json) {
        foreach ($json as $item) {
            $numFila = (int)$item['fila'];

            // Añado al grupo de su fila
            $filas_agrupadas[$numFila][] = [
                'Nombre' => $item['nombre'],
                'Apellidos' => $item['apellidos'],
                'Sexo' => $item['sexo'],
                'es_profe_sexi' => (bool)$item['es_profe_sexi']
            ];
            $hay_datos = true;
        }
        echo "¡JSON procesado correctamente!<br>";
    }
}

// --- 3. LEER EL XML ---
// Ojo: El XML que has pasado tiene etiquetas <personas> y dentro <persona>
$fichero_xml = 'data/datos.xml';
if (file_exists($fichero_xml)) {
    $xml = simplexml_load_file($fichero_xml);
    foreach ($xml->persona as $p) {
        $numFila = (int)$p->fila;
        // Casteo a string primero para compararlo bien
        $esSexy = ((string)$p->es_profe_sexi == '1' || strtolower((string)$p->es_profe_sexi) == 'true');

        $filas_agrupadas[$numFila][] = [
            'Nombre' => (string)$p->nombre,
            'Apellidos' => (string)$p->apellidos,
            'Sexo' => (string)$p->sexo,
            'es_profe_sexi' => $esSexy
        ];
        $hay_datos = true;
    }
    echo "¡XML procesado correctamente!<br>";
}

// --- PASO FINAL: GUARDAR EN MONGO ---
if ($hay_datos) {
    // Primero borro todo lo viejo para no duplicar las filas si le doy dos veces
    $bulk->delete([], ['limit' => 0]);

    // Recorro mi array agrupado y creo UN documento por cada Fila
    foreach ($filas_agrupadas as $numeroFila => $listaAlumnos) {
        // Esta es la estructura nueva que pide el profe:
        // Documento = { "Fila": 1, "Alumnos": [ ... todos los de la fila ... ] }
        $documentoFila = [
            'Fila' => $numeroFila,
            'Alumnos' => $listaAlumnos
        ];
        // Añado al paquete de envío
        $bulk->insert($documentoFila);
    }

    try {
        // Ejecuto todo de golpe
        $manager->executeBulkWrite($namespace, $bulk);
        echo "<h2>¡Importación Agrupada Completada!</h2>";
        echo "Se han creado documentos para " . count($filas_agrupadas) . " filas distintas.<br>";
        echo "<br><a href='index.php'>Ir al Listado</a>";
    } catch (Exception $e) {
        die("Ha habido un error guardando en Mongo: " . $e->getMessage());
    }
} else {
    echo "No he encontrado datos en la carpeta data. Revisa que estén los archivos.";
}
