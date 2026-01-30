<?php
// formulario.php
// Este archivo es multiusos: sirve para dar de alta (si no hay ID) o para editar (si hay ID).

require_once 'db.php';

// Recojo el ID de la URL. Si no viene nada, asumo que es null.
$id = $_GET['id'] ?? null;

// Inicializo las variables vacías. Esto es importante para que el HTML no falle
// cuando estoy creando un alumno nuevo (y por tanto no hay datos previos).
$datos = [
    'Fila' => '', 
    'Nombre' => '', 
    'Apellidos' => '', 
    'Sexo' => 'H', 
    'es_profe_sexi' => false
];
$fila_antigua = ''; // Variable auxiliar para saber si el usuario le cambia de fila

// Si me han pasado un ID, significa que estamos EDITANDO. Toca buscar los datos.
if ($id) {
    // Desgloso mi ID compuesto (mongoId-indice) usando el guion como separador
    $partes = explode('-', $id);
    $mongoId = $partes[0];     // El ID real del documento en Mongo
    $indice = (int)$partes[1]; // La posición del alumno en el array (0, 1, 2...)

    // Busco el documento COMPLETO de la fila usando su _id
    // Recuerda: Hay que convertir el string ID a un ObjectId de Mongo
    $filtro = ['_id' => new MongoDB\BSON\ObjectId($mongoId)];
    $query = new MongoDB\Driver\Query($filtro);
    $cursor = $manager->executeQuery($namespace, $query);
    
    // Cojo el primer resultado (debería ser único porque busco por ID)
    $resultado = current($cursor->toArray());

    if ($resultado) {
        $filaDoc = (array)$resultado;
        $lista = (array)$filaDoc['Alumnos'];
        
        // Ahora accedo directamente a la posición del array que me interesa
        if (isset($lista[$indice])) {
            $alumnoData = (array)$lista[$indice];
            
            // Relleno las variables del formulario con lo que he encontrado
            $datos['Fila'] = $filaDoc['Fila']; 
            $datos['Nombre'] = $alumnoData['Nombre'];
            $datos['Apellidos'] = $alumnoData['Apellidos'];
            $datos['Sexo'] = $alumnoData['Sexo'];
            $datos['es_profe_sexi'] = $alumnoData['es_profe_sexi'];
            
            // Me guardo la fila original. Esto es vital para 'guardar.php'
            $fila_antigua = $filaDoc['Fila']; 
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Ficha del Alumno</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <h1><?= $id ? 'Editar' : 'Nuevo' ?> Alumno</h1>
    
    <form action="guardar.php" method="POST">
        <input type="hidden" name="id" value="<?= $id ?>">
        <input type="hidden" name="fila_antigua" value="<?= $fila_antigua ?>">

        <label>Número de Fila:</label>
        <input type="number" name="fila" required value="<?= $datos['Fila'] ?>">

        <label>Nombre:</label>
        <input type="text" name="nombre" required value="<?= $datos['Nombre'] ?>">

        <label>Apellidos:</label>
        <input type="text" name="apellidos" required value="<?= $datos['Apellidos'] ?>">

        <label>Sexo:</label>
        <select name="sexo">
            <option value="H" <?= $datos['Sexo'] == 'H' ? 'selected' : '' ?>>Hombre</option>
            <option value="M" <?= $datos['Sexo'] == 'M' ? 'selected' : '' ?>>Mujer</option>
        </select>

        <label style="margin-top:20px">
            <input type="checkbox" name="es_profe_sexi" value="1" <?= $datos['es_profe_sexi'] ? 'checked' : '' ?>>
            ¿Es el profesor sexy?
        </label>

        <button type="submit" class="btn btn-edit" style="margin-top:20px">Guardar Cambios</button>
        <a href="index.php">Cancelar y Volver</a>
    </form>
</body>
</html>
