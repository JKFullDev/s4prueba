<?php
// guardar.php
// Aquí gestiono si inserto, actualizo o muevo de fila.

require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $idCompuesto = $_POST['id'] ?? ''; // mongoId-indice
    $filaNueva = (int)$_POST['fila'];
    $filaAntigua = $_POST['fila_antigua'] !== '' ? (int)$_POST['fila_antigua'] : null;

    // Preparo los datos del alumno
    $alumno = [
        'Nombre' => $_POST['nombre'],
        'Apellidos' => $_POST['apellidos'],
        'Sexo' => $_POST['sexo'],
        'es_profe_sexi' => isset($_POST['es_profe_sexi'])
    ];

    $bulk = new MongoDB\Driver\BulkWrite;

    // --- CASO 1: ES UN ALUMNO NUEVO ---
    if (empty($idCompuesto)) {
        // Busco la fila donde quiere ir. Si existe, hago push. Si no, la creo (upsert).
        $filtro = ['Fila' => $filaNueva];
        $update = ['$push' => ['Alumnos' => $alumno]];
        // upsert: true significa "si no encuentras la fila, créala nueva"
        $bulk->update($filtro, $update, ['upsert' => true]);
    }
    // --- CASO 2: EDITANDO UNO EXISTENTE ---
    else {
        // Parto el ID
        $partes = explode('-', $idCompuesto);
        $mongoId = $partes[0];
        $indice = (int)$partes[1];

        // SUB-CASO A: EL USUARIO CAMBIÓ LA FILA (Hay que moverlo)
        if ($filaAntigua !== null && $filaNueva != $filaAntigua) {

            // 1. Lo borro de la fila vieja.
            // Para hacerlo fácil: leo la fila vieja, borro en PHP y guardo el array entero.
            $queryOld = new MongoDB\Driver\Query(['_id' => new MongoDB\BSON\ObjectId($mongoId)]);
            $resOld = $manager->executeQuery($namespace, $queryOld)->toArray();
            $docOld = (array)$resOld[0];
            $arrAlumnosOld = (array)$docOld['Alumnos'];

            unset($arrAlumnosOld[$indice]); // Lo quito
            $arrAlumnosOld = array_values($arrAlumnosOld); // Reordeno índices

            // Guardo la fila vieja sin el alumno
            $bulk->update(
                ['_id' => new MongoDB\BSON\ObjectId($mongoId)],
                ['$set' => ['Alumnos' => $arrAlumnosOld]]
            );

            // 2. Lo añado a la fila nueva
            // Hago otro bulk para que no se líe
            $bulk2 = new MongoDB\Driver\BulkWrite;
            $bulk2->update(
                ['Fila' => $filaNueva],
                ['$push' => ['Alumnos' => $alumno]],
                ['upsert' => true] // Por si la fila nueva no existía
            );
            $manager->executeBulkWrite($namespace, $bulk2);
        }
        // SUB-CASO B: SIGUE EN LA MISMA FILA (Solo actualizo datos)
        else {
            // Leo el documento actual
            $query = new MongoDB\Driver\Query(['_id' => new MongoDB\BSON\ObjectId($mongoId)]);
            $res = $manager->executeQuery($namespace, $query)->toArray();
            $doc = (array)$res[0];
            $arrAlumnos = (array)$doc['Alumnos'];

            // Machaco los datos viejos con los nuevos en esa posición
            $arrAlumnos[$indice] = $alumno;

            // Guardo el array 'Alumnos' actualizado
            $bulk->update(
                ['_id' => new MongoDB\BSON\ObjectId($mongoId)],
                ['$set' => ['Alumnos' => $arrAlumnos]]
            );
        }
    }

    // Ejecuto las operaciones pendientes
    if ($bulk->count() > 0) {
        $manager->executeBulkWrite($namespace, $bulk);
    }

    // Vuelvo al listado
    header("Location: index.php");
    exit();
}
