<?php
// guardar.php
// Este archivo es el "cerebro". Recibe los datos del formulario y decide qué hacer.
// Tiene que manejar tres situaciones: Nuevo Alumno, Editar Alumno, y Mover Alumno de Fila.

require_once 'db.php';

// Solo proceso si los datos vienen por POST (si alguien entra por la URL no hago nada)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Recojo los datos básicos
    $idCompuesto = $_POST['id'] ?? ''; 
    $filaNueva = (int)$_POST['fila'];
    // Recupero la fila antigua para comparar
    $filaAntigua = $_POST['fila_antigua'] !== '' ? (int)$_POST['fila_antigua'] : null;

    // Creo el array con la estructura del alumno tal cual se guardará en Mongo
    $alumno = [
        'Nombre' => $_POST['nombre'],
        'Apellidos' => $_POST['apellidos'],
        'Sexo' => $_POST['sexo'],
        'es_profe_sexi' => isset($_POST['es_profe_sexi']) // isset devuelve true si el checkbox se marcó
    ];

    $bulk = new MongoDB\Driver\BulkWrite;

    // ----------------------------------------------------------------------
    // CASO 1: ES UN ALUMNO NUEVO (No hay ID)
    // ----------------------------------------------------------------------
    if (empty($idCompuesto)) {
        // Busco si ya existe un documento para esa fila.
        // Si existe, uso $push para añadir al alumno al final del array 'Alumnos'.
        // Si NO existe, la opción 'upsert' => true le dice a Mongo que cree el documento nuevo.
        $filtro = ['Fila' => $filaNueva];
        $update = ['$push' => ['Alumnos' => $alumno]];
        $bulk->update($filtro, $update, ['upsert' => true]);
    } 
    // ----------------------------------------------------------------------
    // CASO 2: ESTOY EDITANDO UNO EXISTENTE
    // ----------------------------------------------------------------------
    else {
        // Parto el ID para saber dónde está ahora mismo
        $partes = explode('-', $idCompuesto);
        $mongoId = $partes[0];
        $indice = (int)$partes[1];

        // SUB-CASO A: EL USUARIO LO HA MOVIDO DE FILA
        // (La fila nueva es distinta a la antigua)
        if ($filaAntigua !== null && $filaNueva != $filaAntigua) {
            
            // PASO 1: Sacarlo de la fila vieja.
            // Leo el documento antiguo de la base de datos
            $queryOld = new MongoDB\Driver\Query(['_id' => new MongoDB\BSON\ObjectId($mongoId)]);
            $resOld = $manager->executeQuery($namespace, $queryOld)->toArray();
            $docOld = (array)$resOld[0];
            $arrAlumnosOld = (array)$docOld['Alumnos'];
            
            // Elimino el elemento del array usando su índice
            unset($arrAlumnosOld[$indice]); 
            // ¡IMPORTANTE! Reindexo el array (0, 1, 2...). Si no, Mongo guarda un objeto raro con huecos.
            $arrAlumnosOld = array_values($arrAlumnosOld); 
            
            // Guardo la fila vieja actualizada (ya sin el alumno)
            $bulk->update(
                ['_id' => new MongoDB\BSON\ObjectId($mongoId)],
                ['$set' => ['Alumnos' => $arrAlumnosOld]]
            );

            // PASO 2: Meterlo en la fila nueva.
            // Uso otro BulkWrite para no mezclar operaciones y asegurar el orden.
            $bulk2 = new MongoDB\Driver\BulkWrite;
            $bulk2->update(
                ['Fila' => $filaNueva],
                ['$push' => ['Alumnos' => $alumno]],
                ['upsert' => true] // Por si la fila nueva estaba vacía y no existía
            );
            $manager->executeBulkWrite($namespace, $bulk2);

        } 
        // SUB-CASO B: SE QUEDA EN LA MISMA FILA
        // (Solo ha cambiado el nombre, o si es sexy o no)
        else {
            // Leo el documento actual para tener el array fresco
            $query = new MongoDB\Driver\Query(['_id' => new MongoDB\BSON\ObjectId($mongoId)]);
            $res = $manager->executeQuery($namespace, $query)->toArray();
            $doc = (array)$res[0];
            $arrAlumnos = (array)$doc['Alumnos'];
            
            // Sobrescribo los datos del alumno en su posición exacta
            $arrAlumnos[$indice] = $alumno;
            
            // Guardo el array completo de nuevo con '$set'
            $bulk->update(
                ['_id' => new MongoDB\BSON\ObjectId($mongoId)],
                ['$set' => ['Alumnos' => $arrAlumnos]]
            );
        }
    }

    // Si ha quedado alguna operación pendiente en el carrito principal, la ejecuto.
    if ($bulk->count() > 0) {
        $manager->executeBulkWrite($namespace, $bulk);
    }

    // Todo listo, vuelvo a la pantalla principal
    header("Location: index.php");
    exit();
}
?>
