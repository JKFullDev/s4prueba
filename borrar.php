<?php
// Script sencillo para eliminar un documento por su ID

require_once 'db.php';

// Recojo el ID de la URL
$id = $_GET['id'] ?? null;

if ($id) {
    try {
        $bulk = new MongoDB\Driver\BulkWrite;

        // Añado la operación de borrado al paquete
        // Busco por _id (convirtiéndolo a ObjectId para que lo entienda Mongo)
        $bulk->delete(['_id' => new MongoDB\BSON\ObjectId($id)]);

        // Ejecuto la orden de borrado
        $manager->executeBulkWrite($namespace, $bulk);
    } catch (Exception $e) {
        die("Error intentando borrar el alumno: " . $e->getMessage());
    }
}

// Pase lo que pase (se haya borrado o no), vuelvo al listado principal
header("Location: index.php");
exit;
