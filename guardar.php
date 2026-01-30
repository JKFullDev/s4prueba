<?php
// guardar.php
// Este es el "controlador" que recibe los datos del formulario y decide
// si hace un INSERT (nuevo) o un UPDATE (editar).

require_once 'db.php';

// Solo proceso si me llaman por POST (para evitar accesos directos)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Recojo el ID. Si viene vacío, es una insercion
    $id = $_POST['id'] ?? '';

    // Aquí monto el documento BSON (el array) tal cual lo quiero guardar en Mongo
    $documento = [
        'Numero' => (int)$_POST['numero'], // Importante: guardar números como números
        'Alumno' => [
            'Nombre' => $_POST['nombre'],
            'Apellidos' => $_POST['apellidos'],
            'Sexo' => $_POST['sexo'],
            // El checkbox: isset devuelve true si está marcado, false si no
            'es_profe_sexi' => isset($_POST['es_profe_sexi'])
        ]
    ];

    // Preparo el paquete de escritura
    $bulk = new MongoDB\Driver\BulkWrite;

    if (!empty($id)) {
        // === CASO ACTUALIZAR ===
        // Si tengo ID, le digo a Mongo que busque este ID y le cambie ($set) estos datos"
        $filter = ['_id' => new MongoDB\BSON\ObjectId($id)];
        $update = ['$set' => $documento]; // $set es vital para no borrar campos si hubiera otros extra
        $bulk->update($filter, $update);
    } else {
        // === CASO INSERTAR ===
        // Si no tengo ID, simplemente lo añado. Mongo le creará el _id automaticamente
        $bulk->insert($documento);
    }

    try {
        // Ejecuto la operación
        $manager->executeBulkWrite($namespace, $bulk);

        // Si todo ha ido bien, redirijo al usuario a la lista principal
        header("Location: index.php");
        exit; // Termino el script para asegurarme que no se ejecuta nada más
    } catch (Exception $e) {
        die("Ha habido un error al guardar en la base de datos: " . $e->getMessage());
    }
}
