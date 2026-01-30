<?php
// ver_todo.php
// Este script muestra exactamente lo que hay guardado en la base de datos.
// Ya que no podemos usar Mongo Compass

require_once 'db.php';

$query = new MongoDB\Driver\Query([]);
$cursor = $manager->executeQuery($namespace, $query);

echo "<pre>"; // Esto hace que se vea ordenado
foreach ($cursor as $doc) {
    print_r($doc); // Vuelca el contenido bruto
}
echo "</pre>";
