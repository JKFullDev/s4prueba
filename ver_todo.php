<?php
// ver_todo.php
require_once 'db.php';

$query = new MongoDB\Driver\Query([]);
$cursor = $manager->executeQuery($namespace, $query);

echo "<pre>"; // Esto hace que se vea ordenado
foreach ($cursor as $doc) {
    print_r($doc); // Vuelca el contenido bruto
}
echo "</pre>";
