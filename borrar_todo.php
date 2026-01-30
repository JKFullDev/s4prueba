<?php
// Este script borra absolutamente todo de la base de datos.
// Es útil para limpiar la tabla antes de hacer una importación nueva.

require_once 'db.php';

try {
    // Creo el "carrito" de operaciones masivas
    $bulk = new MongoDB\Driver\BulkWrite;

    // Añado la orden de borrar
    // El primer parámetro es el filtro: [] (array vacío) significa "selecciona todo"
    // El segundo parámetro ['limit' => 0] significa "no te limites, borra todos los que encuentres"
    // (Si pusiera limit => 1 solo borraría el primero)
    $bulk->delete([], ['limit' => 0]);

    // Ejecuto la orden en la base de datos
    $manager->executeBulkWrite($namespace, $bulk);
} catch (Exception $e) {
    // Si algo falla (que no debería), avisamos
    die("Error al intentar vaciar la base de datos: " . $e->getMessage());
}

// Cuando termine, vuelvo al listado (que estará vacío)
header("Location: index.php");
exit;
