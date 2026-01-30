<?php
// borrar.php
// Script sencillo para eliminar un alumno.
// Como están dentro de arrays, tengo que leer la fila, quitarlo del array y volver a guardar.

require_once 'db.php';

// Recojo el ID compuesto de la URL
$idCompuesto = $_GET['id'] ?? null;

if ($idCompuesto) {
    // Saco las coordenadas: ID del documento y posición en el array
    $partes = explode('-', $idCompuesto);
    $mongoId = $partes[0];
    $indice = (int)$partes[1];

    // 1. Leo el documento completo de la base de datos
    $query = new MongoDB\Driver\Query(['_id' => new MongoDB\BSON\ObjectId($mongoId)]);
    $cursor = $manager->executeQuery($namespace, $query);
    $res = $cursor->toArray();

    if (!empty($res)) {
        $doc = (array)$res[0];
        $alumnos = (array)$doc['Alumnos'];

        // 2. Compruebo que el índice existe (por seguridad)
        if (isset($alumnos[$indice])) {
            // Lo elimino del array PHP
            unset($alumnos[$indice]);
            
            // 3. Reorganizo los índices del array.
            // Esto es crucial: Si borro el 2, quiero que el 3 pase a ser el 2.
            $alumnos = array_values($alumnos); 

            // 4. Actualizo el documento en Mongo con el array ya limpio
            $bulk = new MongoDB\Driver\BulkWrite;
            $bulk->update(
                ['_id' => new MongoDB\BSON\ObjectId($mongoId)],
                ['$set' => ['Alumnos' => $alumnos]]
            );
            $manager->executeBulkWrite($namespace, $bulk);
        }
    }
}

// Vuelvo al listado
header("Location: index.php");
exit();
?>
