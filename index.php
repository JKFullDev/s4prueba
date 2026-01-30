<?php
// index.php
require_once 'db.php';

// Pido todas las filas, ordenadas por el número de fila (1, 2, 3...)
$query = new MongoDB\Driver\Query([], ['sort' => ['Fila' => 1]]);
$cursor = $manager->executeQuery($namespace, $query);
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Listado de Alumnos 2DAW</title>
    <link rel="stylesheet" href="css/style.css">
</head>

<body>
    <h1>Listado Alumnos 2ºDAW</h1>

    <div style="margin-bottom: 20px;">
        <a href="formulario.php" class="btn btn-add">Añadir Nuevo</a>
        <a href="importar.php" class="btn">Restaurar Datos</a>
        <a href="borrar_todo.php" class="btn btn-del" onclick="return confirm('¿Seguro que quieres borrar todo?');">Borrar Todo</a>
    </div>

    <table>
        <thead>
            <tr>
                <th>Fila</th>
                <th>Nombre</th>
                <th>Apellidos</th>
                <th>Sexo</th>
                <th>¿Es profe sexy?</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($cursor as $documento): ?>
                <?php
                // Convierto el documento a array para manejarlo mejor
                $filaDoc = (array)$documento;
                $idMongo = (string)$filaDoc['_id']; // El ID del documento padre
                $numeroFila = $filaDoc['Fila'];
                $listaAlumnos = (array)$filaDoc['Alumnos']; // El array de gente
                ?>

                <?php foreach ($listaAlumnos as $indice => $alumno): ?>
                    <?php
                    $alumno = (array)$alumno;
                    // Me creo un ID único combinando el ID de la fila y el índice del alumno
                    // Ejemplo: 65b...cde-0 (Fila tal, alumno 0)
                    $idCompuesto = $idMongo . '-' . $indice;
                    ?>
                    <tr>
                        <td><strong><?= $numeroFila ?></strong></td>
                        <td><?= $alumno['Nombre'] ?></td>
                        <td><?= $alumno['Apellidos'] ?></td>
                        <td><?= $alumno['Sexo'] ?></td>
                        <td><?= $alumno['es_profe_sexi'] ? 'SÍ 🔥' : 'No' ?></td>
                        <td>
                            <a href="formulario.php?id=<?= $idCompuesto ?>" class="btn btn-edit">Editar</a>
                            <a href="borrar.php?id=<?= $idCompuesto ?>" class="btn btn-del" onclick="return confirm('¿Borrar a <?= $alumno['Nombre'] ?>?');">Borrar</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>

</html>