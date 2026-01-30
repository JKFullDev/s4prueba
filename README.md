# JUSTIFICACIÓN DE MÉTODOS

En este documento detallo algunas de las decisiones que he tomado para el desarrollo de la aplicación.

### 1. ESCRITURA MASIVA CON `MongoDB\Driver\BulkWrite`
Para las operaciones de importación, inserción, actualización y borrado, he utilizado la clase `BulkWrite`.

* **Código:** `$bulk = new MongoDB\Driver\BulkWrite;`
* **Justificación:** Esta clase permite agrupar múltiples operaciones de escritura en un solo "paquete". En lugar de realizar una conexión y petición al servidor por cada alumno, el `BulkWrite` acumula las operaciones en memoria y las envía de una sola vez.

### 2. EJECUCIÓN DE ESCRITURA (`executeBulkWrite`)
* **Código:** `$manager->executeBulkWrite($namespace, $bulk);`
* **Justificación:** Es el método encargado de "disparar" el paquete de operaciones preparado anteriormente hacia el servidor MongoDB. Se utiliza el `$namespace` (formato `base_datos.coleccion`) para indicar exactamente dónde se tienen que aplicar los cambios.

### 3. IDENTIFICADORES ÚNICOS (`MongoDB\BSON\ObjectId`)
* **Código:** `['_id' => new MongoDB\BSON\ObjectId($id)]`
* **Justificación:** MongoDB utiliza objetos BSON en lugar de IDs numéricos autoincrementales simples. Entonces para buscar o modificar un documento específico, es **necesaria** esta clase porque si pasáramos el ID como una simple cadena de texto (`string`), MongoDB no encontraría el documento porque los tipos de datos no coincidirían.

### 4. CONSULTAS Y FILTRADO (`Query` y `executeQuery`)
* **Código:**
    ```php
    $query = new MongoDB\Driver\Query($filter, $options);
    $cursor = $manager->executeQuery($namespace, $query);
    ```
* **Justificación:** La clase `Query` encapsula tanto el filtro de búsqueda (el "WHERE" de SQL) como las opciones de consulta (como `sort` para ordenar). Al separarlo de la ejecución, permite reutilizar la consulta. `executeQuery` devuelve un cursor iterable que permite recorrer los resultados uno a uno.

### 5. ACTUALIZACIÓN DE CAMPOS (`$set`)
* **Código:** `$update = ['$set' => $documento];`
* **Justificación:** En la operación de edición, utilizo el operador atómico `$set`. Esto indica a MongoDB que debe **modificar** los campos especificados dentro del documento existente, sin sobrescribir el documento completo ni borrar campos que no estemos enviando.

### 6. LECTURA DE ARCHIVOS (CSV, JSON, XML)
Para la integración de datos he utilizado las funciones nativas de PHP para cada formato:

* **CSV (`fopen`, `fgetcsv`):**
    * **Justificación:** `fgetcsv` lee el archivo línea por línea, parseando automáticamente los separadores (comas). Es preferible a leer todo el archivo en memoria con `file_get_contents` porque maneja mejor archivos grandes.
* **JSON (`file_get_contents`, `json_decode`):**
    * **Justificación:** Al ser un formato ligero, leo el contenido completo y uso `json_decode($json, true)`. El segundo parámetro `true` es para convertir los objetos JSON en **arrays asociativos**.
* **XML (`simplexml_load_file`):**
    * **Justificación:** Esta función convierte la estructura del XML en un objeto PHP iterable, ayudando al acceso a los nodos `<alumno>` y sus hijos sin tener que parsear el texto manualmente.

### 7. GESTIÓN DE ERRORES (`try...catch`)
* **Código:**
    ```php
    catch (MongoDB\Driver\Exception\Exception $e) { ... }
    ```
* **Justificación:** Como estamos trabajamos con conexiones a bases de datos externas, hay muchas posibilidades de error. Por esto es mejor capturar el error del driver de Mongo para evitar que la página muestre el error al usuario entre otras razones.
<br><br>
---
<i>Hecho por Juan Carlos Alonso Hernando</i>