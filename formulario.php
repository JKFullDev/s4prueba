<?php
// formulario.php
// Este archivo sirve para dos cosas: CREAR (nuevo) y EDITAR (existente)
// Todo depende de si recibo un ID por la URL o no

require_once 'db.php';

// Recojo el ID de la URL. Si no viene nada, asumo que es null (Crear)
$id = $_GET['id'] ?? null;

// Defino una estructura de datos vacía por defecto
// Así el formulario no falla al intentar pintar variables que no existen
$datos = [
    'Numero' => '',
    'Alumno' => [
        'Nombre' => '',
        'Apellidos' => '',
        'Sexo' => 'H', // Valor por defecto
        'es_profe_sexi' => false
    ]
];

// Si tengo ID, significa que quiero EDITAR por lo que toca ir a buscar a Mongo
if ($id) {
    try {
        // Construyo un filtro buscando por el _id de Mongo
        // Importante: Hay que convertir el string ID a un objeto ObjectId
        $filter = ['_id' => new MongoDB\BSON\ObjectId($id)];

        $query = new MongoDB\Driver\Query($filter);
        $cursor = $manager->executeQuery($namespace, $query);

        // Convierto el resultado a array para manejarlo fácil
        $resultado = $cursor->toArray();

        if (!empty($resultado)) {
            // Si lo encuentro, sobrescribo mi variable $datos con lo que hay en la BBDD
            $encontrado = (array)$resultado[0];
            // Aseguro que 'Alumno' sea array
            $encontrado['Alumno'] = (array)$encontrado['Alumno'];
            $datos = $encontrado;
        }
    } catch (Exception $e) {
        echo "Error recuperando el alumno: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title><?= $id ? 'Editar Alumno' : 'Nuevo Alumno' ?></title>
    <link rel="stylesheet" href="css/style.css">
</head>

<body>
    <h1><?= $id ? 'Editar Alumno' : 'Nuevo Alumno' ?></h1>

    <form action="guardar.php" method="POST">
        <input type="hidden" name="id" value="<?= $id ?>">

        <label>Número de Lista:</label>
        <input type="number" name="numero" required value="<?= $datos['Numero'] ?>">

        <label>Nombre:</label>
        <input type="text" name="nombre" required value="<?= $datos['Alumno']['Nombre'] ?>">

        <label>Apellidos:</label>
        <input type="text" name="apellidos" required value="<?= $datos['Alumno']['Apellidos'] ?>">

        <label>Sexo:</label>
        <select name="sexo">
            <option value="H" <?= $datos['Alumno']['Sexo'] == 'H' ? 'selected' : '' ?>>Hombre</option>
            <option value="M" <?= $datos['Alumno']['Sexo'] == 'M' ? 'selected' : '' ?>>Mujer</option>
        </select>

        <label style="margin-top: 20px;">
            <input type="checkbox" name="es_profe_sexi" value="1" <?= $datos['Alumno']['es_profe_sexi'] ? 'checked' : '' ?>>
            ¿Es el profesor sexy?
        </label>

        <button type="submit">Guardar Datos</button>

        <div style="margin-top: 15px;">
            <a href="index.php">Cancelar y volver</a>
        </div>
    </form>
</body>

</html>