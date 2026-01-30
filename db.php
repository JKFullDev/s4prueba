<?php
// db.php
// Aquí es donde configuro la conexión a la base de datos

try {
    $usuario  = "jorge";  
    $password = "1234"; 
    $host     = "192.168.108.100"; 
    $puerto   = "27017"; 
    $auth_db  = "videojuegos_db";           
    $dbName   = "videojuegos_db_juancarlos"; // Nombre de la base de datos a usar
    

    // Construyo la direccion completa para conectarme
    $uri = "mongodb://$usuario:$password@$host:$puerto/?authSource=$auth_db";

    // Creo el Manager, que es el 'jefe' de la conexión en el driver nativo de PHP
    $manager = new MongoDB\Driver\Manager($uri);

    // Defino el nombre de la colección (la tabla) para usarla en el resto de archivos
    $collName = 'filas';
    $namespace = "$dbName.$collName";
} catch (Exception $e) {
    // Si algo falla, que se pare todo y me diga por qué
    die("Error fatal de conexión: " . $e->getMessage());
}
