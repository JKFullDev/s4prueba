<?php
// importar.php
// Este script se encarga de leer los 3 archivos y organizarlos
// En lugar de guardar uno a uno, primero los agrupo en PHP por 'Fila'
// y luego creo un documento por cada fila con todos sus alumnos dentro

require_once 'db.php';

// Preparo un "BulkWrite". Es como un carrito de la compra donde voy
// metiendo todas las operaciones que quiero hacer de golpe
// Es mas eficiente que ir una a una
$bulk = new MongoDB\Driver\BulkWrite;

// Este array será mi almacén temporal.
// La estructura será: $filas_agrupadas[numero_fila] = [lista de alumnos]
$filas_agrupadas = []; 
$hay_datos = false; // Bandera para saber si he encontrado algo o no

// ------------------------------------------------
// 1. CSV (Separado por puntos y coma ';')
// ------------------------------------------------
$fichero_csv = 'data/datos.csv';

// Primero compruebo que el fichero existe 
if (file_exists($fichero_csv)) {
    // Intento abrir el archivo en modo lectura ('r')
    if (($gestor = fopen($fichero_csv, "r")) !== FALSE) {
        
        // La primera línea son las cabeceras, así que la leo pero no hago nada con ella
        // Esto mueve el puntero a la segunda línea
        fgetcsv($gestor, 1000, ";"); 
        
        // Ahora sí, recorro el resto del archivo línea a línea
        while (($datos = fgetcsv($gestor, 1000, ";")) !== FALSE) {
            // El CSV viene así: NOMBRE;APELLIDOS;FILA;SEXO;ES_PROFE_SEXI
            // La fila está en la posición 2 del array (empezando por 0)
            $numFila = (int)$datos[2]; 
            
            // El CSV a veces trae "1" o "true" como texto asi que
            // aquí lo convierto a un booleano de verdad (true/false)
            $esSexy = ($datos[4] == '1' || strtolower($datos[4]) == 'true');

            // En lugar de insertarlo ya en Mongo lo añado a mi array de PHP, 
            // dentro de la clave de su número de fila
            $filas_agrupadas[$numFila][] = [
                'Nombre' => $datos[0],
                'Apellidos' => $datos[1],
                'Sexo' => $datos[3],
                'es_profe_sexi' => $esSexy
            ];
            $hay_datos = true; // Marco que he encontrado contenido
        }
        fclose($gestor); // Cierro el archivo
        echo "<div>¡CSV importado correctamente!</div>";
    }
}

// ---------------------------------------------
// 2. JSON
// ---------------------------------------------
$fichero_json = 'data/datos.json';

if (file_exists($fichero_json)) {
    //  Meto todo el contenido del fichero de texto en una variable 
    $contenido = file_get_contents($fichero_json);
    // Decodifico el texto JSON a un array asociativo de PHP
    $json = json_decode($contenido, true);
    
    if ($json) {
        foreach ($json as $item) {
            $numFila = (int)$item['fila'];
            
            // Lo añado al grupo de su fila correspondiente
            $filas_agrupadas[$numFila][] = [
                'Nombre' => $item['nombre'],
                'Apellidos' => $item['apellidos'],
                'Sexo' => $item['sexo'],
                // Aseguro que sea booleano
                'es_profe_sexi' => (bool)$item['es_profe_sexi']
            ];
            $hay_datos = true;
        }
        echo "<div>¡JSON importado correctamente!</div>";
    }
}

// -------------------------------
// 3. XML
// -------------------------------
$fichero_xml = 'data/datos.xml';

if (file_exists($fichero_xml)) {
    // Cargo el XML y me devuelve un objeto iterable, no un array simple
    $xml = simplexml_load_file($fichero_xml);
    
    // Recorro cada etiqueta <persona>
    foreach ($xml->persona as $p) {
        $numFila = (int)$p->fila;
        
        // En XML todo es texto así que compruebo si pone '1' o 'true'
        $esSexy = ((string)$p->es_profe_sexi == '1' || strtolower((string)$p->es_profe_sexi) == 'true');

        // Los castings (string) son obligatorios porque SimpleXML devuelve objetos raros
        $filas_agrupadas[$numFila][] = [
            'Nombre' => (string)$p->nombre,
            'Apellidos' => (string)$p->apellidos,
            'Sexo' => (string)$p->sexo,
            'es_profe_sexi' => $esSexy
        ];
        $hay_datos = true;
    }
    echo "<div>¡XML importado correctamente!</div>";
}

// -------------------------------------------
// PASO FINAL: VOLCADO A MONGODB
// -------------------------------------------
if ($hay_datos) {
    // Antes de guardar lo nuevo, borro TODO lo que hubiera antes en la colección
    // para para evitar duplicados
    $bulk->delete([], ['limit' => 0]); 

    // Ahora recorro mi array de filas que he ido llenando
    // $numeroFila es la clave (ej: 1, 2, 3...) y $listaAlumnos es el array con la gente
    foreach ($filas_agrupadas as $numeroFila => $listaAlumnos) {
        
        // Creo el documento final con la estructura "Agrupada" que pide el ejercicio
        $documentoFila = [
            'Fila' => $numeroFila,
            'Alumnos' => $listaAlumnos // Esto es un array de arrays
        ];
        
        // Añado este documento a la cola de envío
        $bulk->insert($documentoFila);
    }

    try {
        // Envío todas las inserciones a la base de datos de una vez
        $manager->executeBulkWrite($namespace, $bulk);
        
        echo "<h2>¡Importación completada con éxito!</h2>";
        echo "<p>Se han generado documentos para " . count($filas_agrupadas) . " filas distintas.</p>";
        echo "<br><a href='index.php'>Volver al Listado</a>";
        
    } catch (Exception $e) {
        die("Ha habido un error al escribir en Mongo: " . $e->getMessage());
    }
} else {
    echo "¡No he encontrado datos. Por favor, revisa que los archivos están en la carpeta 'data'!";
}
?>
