<?php
// borrar_todo.php
// Este script Borra todos los datos de la colección

require_once 'db.php';

try {
    $bulk = new MongoDB\Driver\BulkWrite;
    
    // Le paso un filtro vacío [] para que seleccione todos los documentos
    // El 'limit' => 0 significa "sin límite", es decir, borralos todos
    $bulk->delete([], ['limit' => 0]);
    
    // Ejecuto la orden de borrado masivo
    $manager->executeBulkWrite($namespace, $bulk);

} catch (Exception $e) {
    die("Error intentando borrar la base de datos: " . $e->getMessage());
}

// Me vuelvo al inicio (que ahora estará vacío)
header("Location: index.php");
exit();
?>
