<?php
// borrar.php
require_once 'db.php';

$idCompuesto = $_GET['id'] ?? null;

if ($idCompuesto) {
    // Saco el ID de la fila y la posición del alumno
    $partes = explode('-', $idCompuesto);
    $mongoId = $partes[0];
    $indice = (int)$partes[1];

    // 1. Leo la fila completa
    $query = new MongoDB\Driver\Query(['_id' => new MongoDB\BSON\ObjectId($mongoId)]);
    $cursor = $manager->executeQuery($namespace, $query);
    $res = $cursor->toArray();

    if (!empty($res)) {
        $doc = (array)$res[0];
        $alumnos = (array)$doc['Alumnos'];

        // 2. Si existe ese índice, lo borro
        if (isset($alumnos[$indice])) {
            unset($alumnos[$indice]);
            // Importante: reindexar el array para que no queden huecos [0, 2, 3...]
            $alumnos = array_values($alumnos);

            // 3. Actualizo el documento en Mongo con el array nuevo
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
?>s