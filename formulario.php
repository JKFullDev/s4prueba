<?php
// formulario.php
require_once 'db.php';

$id = $_GET['id'] ?? null; // Esto será algo como "mongoId-indice"

// Valores por defecto
$datos = [
    'Fila' => '', // Ahora la fila es un dato importante
    'Nombre' => '',
    'Apellidos' => '',
    'Sexo' => 'H',
    'es_profe_sexi' => false
];
$fila_antigua = ''; // Para saber si le cambiamos de sitio luego

// Si hay ID, es que estamos EDITANDO
if ($id) {
    // Separo el ID del documento y el índice del alumno
    $partes = explode('-', $id);
    $mongoId = $partes[0];
    $indice = (int)$partes[1];

    // Busco el documento de la Fila entera
    $filtro = ['_id' => new MongoDB\BSON\ObjectId($mongoId)];
    $query = new MongoDB\Driver\Query($filtro);
    $cursor = $manager->executeQuery($namespace, $query);

    // Cojo el primer resultado
    $resultado = current($cursor->toArray());

    if ($resultado) {
        $filaDoc = (array)$resultado;
        $lista = (array)$filaDoc['Alumnos'];

        // Saco solo al alumno que quiero editar usando el índice
        if (isset($lista[$indice])) {
            $alumnoData = (array)$lista[$indice];

            // Relleno el formulario
            $datos['Fila'] = $filaDoc['Fila'];
            $datos['Nombre'] = $alumnoData['Nombre'];
            $datos['Apellidos'] = $alumnoData['Apellidos'];
            $datos['Sexo'] = $alumnoData['Sexo'];
            $datos['es_profe_sexi'] = $alumnoData['es_profe_sexi'];

            // Me guardo la fila original por si el usuario la cambia
            $fila_antigua = $filaDoc['Fila'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Ficha Alumno</title>
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
            ¿Es profe sexi?
        </label>

        <button type="submit" class="btn btn-edit" style="margin-top:20px">Guardar</button>
        <a href="index.php">Cancelar</a>
    </form>
</body>

</html>