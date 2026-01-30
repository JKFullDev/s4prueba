<?php
// borrar_todo.php
// Limpia la colección entera.
require_once 'db.php';

try {
    $bulk = new MongoDB\Driver\BulkWrite;
    // Borro todo sin filtros
    $bulk->delete([], ['limit' => 0]);
    $manager->executeBulkWrite($namespace, $bulk);
} catch (Exception $e) {
    die("Error borrando todo: " . $e->getMessage());
}

header("Location: index.php");
exit();
