# JUSTIFICACIÓN TÉCNICA Y DE MÉTODOS

En este documento explico los métodos y funciones que he utilizado en mi proyecto de Gestión de Alumnos, justificando por qué son necesarios para trabajar con la estructura de documentos agrupados en MongoDB.

### 1. CONEXIÓN Y GESTIÓN DEL MOTOR (`db.php`)
Para establecer el canal de comunicación con el servidor, utilizo la clase principal del driver nativo:
`$manager = new MongoDB\Driver\Manager($uri);`
* **Justificación:** He elegido el driver nativo por su velocidad. El Manager es el objeto que mantiene la conexión abierta y me permite lanzar todas las consultas que mi aplicación necesita.

### 2. PROCESAMIENTO DE ARCHIVOS EXTERNOS (`importar.php`)
Para la migración de datos, he utilizado métodos específicos de lectura:
* **CSV:** Utilizo `fopen($fichero_csv, "r")` para abrir el canal de lectura y `fgetcsv($gestor, 1000, ";")` para parsear las líneas. He configurado el punto y coma como delimitador porque así es como vienen estructurados mis datos de origen.
* **XML:** Uso `simplexml_load_file($fichero_xml)` porque es la forma más directa de convertir la estructura de etiquetas en un objeto que puedo recorrer con un bucle `foreach`.

### 3. OPERACIONES DE ESCRITURA EFICIENTES (`BulkWrite`)
Tanto en la importación como en el borrado total, utilizo:
`$bulk = new MongoDB\Driver\BulkWrite;`
* **Justificación:** En mi proyecto, la eficiencia es clave. En lugar de enviar órdenes una a una, utilizo este objeto como un contenedor donde acumulo todas las inserciones o borrados y los ejecuto de golpe con `$manager->executeBulkWrite($namespace, $bulk);`.

### 4. BÚSQUEDA Y FILTRADO POR ID (`ObjectId`)
Dado que MongoDB usa identificadores únicos complejos, utilizo:
`$filtro = ['_id' => new MongoDB\BSON\ObjectId($mongoId)];`
`$query = new MongoDB\Driver\Query($filtro);`
* **Justificación:** Para recuperar un alumno en el formulario de edición, necesito localizar su documento padre. Como el ID de la URL es un texto, debo convertirlo en un objeto `ObjectId` para que Mongo lo reconozca. Luego, ejecuto la consulta mediante `$cursor = $manager->executeQuery($namespace, $query);`.

### 5. MANIPULACIÓN DE ARRAYS EN MONGODB (`guardar.php`)
Al estar los alumnos dentro de un array en el documento de la Fila, he usado lógica avanzada de actualización:
* **Inserción/Push:** `$update = ['$push' => ['Alumnos' => $alumno]];`
* **Upsert:** `$bulk->update($filtro, $update, ['upsert' => true]);`
* **Justificación:** El operador `$push` me permite añadir un alumno al final del array sin tocar a los que ya estaban. Con el parámetro `upsert`, me aseguro de que si la fila no existe todavía en la base de datos, se cree automáticamente en ese mismo instante.

### 6. GESTIÓN DE RESULTADOS (`toArray`)
En varios puntos del proyecto (como en `borrar.php` o `guardar.php`), necesito trabajar con los datos que me devuelve una consulta antes de tomar una decisión:
`$res = $cursor->toArray();`
* **Justificación:** El cursor de Mongo es un iterador. Al convertirlo a un array de PHP con `toArray()`, puedo acceder a los datos de forma inmediata por su índice (como `$res[0]`) y manipular los arrays de alumnos con mayor facilidad en memoria.

### 7. LÓGICA DE ACTUALIZACIÓN COMPLEJA
Cuando muevo a un alumno de fila o edito sus datos, utilizo:
`$queryOld = new MongoDB\Driver\Query(['_id' => new MongoDB\BSON\ObjectId($mongoId)]);`
`$resOld = $manager->executeQuery($namespace, $queryOld)->toArray();`
* **Justificación:** Al trabajar con documentos agrupados, para modificar a un alumno primero tengo que "traerme" la fila completa a PHP, realizar el cambio en el array y luego volver a guardarlo. Este proceso garantiza que no se pierdan datos del resto de alumnos que comparten la misma fila.
