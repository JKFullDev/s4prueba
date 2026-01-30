<?php
// index.php
require_once 'db.php';

$filter = [];
$options = ['sort' => ['Numero' => 1]];

$query = new MongoDB\Driver\Query($filter, $options);
$cursor = $manager->executeQuery($namespace, $query);
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Listado Alumnos</title>
    <link rel="stylesheet" href="css/style.css">
</head>

<body>
    <h1>Gestión de Alumnos (Mongo Nativo)</h1>

    <div style="margin-bottom: 20px;">
        <a href="formulario.php" class="btn btn-add">AÑADIR NUEVO ALUNO</a>

        <a href="importar.php" style="margin-left: 10px; font-size: 0.9em;">IMPORTAR DATOS</a>

        <a href="borrar_todo.php" class="btn btn-del" style="margin-left: 10px;"
            onclick="return confirm('¡ATENCIÓN!\n\n¿Estás seguro de que quieres BORRAR A TODOS los alumnos?\n\nEsta acción no se puede deshacer.');">
            BORRAR TODOS
        </a>
    </div>

    <table>
        <thead>
            <tr>
                <th>Núm.</th>
                <th>Nombre</th>
                <th>Apellidos</th>
                <th>Sexo</th>
                <th>¿Es Profe Sexy?</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($cursor as $documento): ?>
                <?php
                $fila = (array)$documento;
                $alumno = (array)$fila['Alumno'];
                $id = (string)$fila['_id'];
                ?>
                <tr>
                    <td><?= $fila['Numero'] ?></td>
                    <td><?= $alumno['Nombre'] ?></td>
                    <td><?= $alumno['Apellidos'] ?></td>
                    <td><?= $alumno['Sexo'] ?></td>
                    <td>
                        <?php if ($alumno['es_profe_sexi']): ?>
                            <span class="sexy">SÍ</span>
                        <?php else: ?>
                            No
                        <?php endif; ?>
                    </td>
                    <td>
                        <a href="formulario.php?id=<?= $id ?>" class="btn btn-edit">Editar</a>
                        <a href="borrar.php?id=<?= $id ?>" class="btn btn-del" onclick="return confirm('¿Seguro que quieres borrar a <?= $alumno['Nombre'] ?>?');">Borrar</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>

</html>