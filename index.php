<?php
// index.php
// Esta es la página principal. Muestra la tabla con todos los alumnos.
// Como están agrupados por filas, la lógica de visualización es un poco especial (doble bucle).

require_once 'db.php';

// Preparo la consulta a la base de datos.
// Pido TODOS los documentos ([]), pero ordenados por el campo 'Fila' de forma ascendente (1).
$query = new MongoDB\Driver\Query([], ['sort' => ['Fila' => 1]]);

// Ejecuto la consulta y obtengo un cursor (un iterador) con los resultados.
$cursor = $manager->executeQuery($namespace, $query);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Listado de Alumnos por Fila</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <h1>Gestión de Alumnos (Vista Agrupada)</h1>

    <div style="margin-bottom: 20px;">
        <a href="formulario.php" class="btn btn-add">Añadir Nuevo Alumno</a>
        <a href="importar.php" class="btn">Restaurar Datos Originales</a>
        <a href="borrar_todo.php" class="btn btn-del" onclick="return confirm('¡CUIDADO! ¿Seguro que quieres borrar TODA la base de datos?');">Borrar Todo</a>
    </div>

    <table>
        <thead>
            <tr>
                <th>Fila</th>
                <th>Nombre</th>
                <th>Apellidos</th>
                <th>Sexo</th>
                <th>¿Sexy?</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($cursor as $documento): ?>
                <?php
                // Convierto el objeto BSON que devuelve Mongo a un Array PHP para trabajar cómodo
                $filaDoc = (array)$documento;
                
                // Necesito el ID del documento padre (la fila) para luego poder identificarla
                $idMongo = (string)$filaDoc['_id']; 
                $numeroFila = $filaDoc['Fila'];
                
                // Extraigo el array de alumnos que hay dentro de este documento
                $listaAlumnos = (array)$filaDoc['Alumnos']; 
                ?>
                
                <?php foreach ($listaAlumnos as $indice => $alumno): ?>
                    <?php 
                        $alumno = (array)$alumno; 
                        
                        // EL TRUCO DEL ALMENDRUCO:
                        // Para editar a un alumno concreto, necesito saber 2 cosas:
                        // 1. En qué documento está (idMongo)
                        // 2. En qué posición del array está (indice)
                        // Me invento un ID compuesto uniéndolos con un guion. Luego lo separaré.
                        $idCompuesto = $idMongo . '-' . $indice;
                    ?>
                    <tr>
                        <td><strong><?= $numeroFila ?></strong></td>
                        <td><?= $alumno['Nombre'] ?></td>
                        <td><?= $alumno['Apellidos'] ?></td>
                        <td><?= $alumno['Sexo'] ?></td>
                        <td>
                            <?php if ($alumno['es_profe_sexi']): ?>
                                <span class="sexy">SÍ 🔥</span>
                            <?php else: ?>
                                No
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="formulario.php?id=<?= $idCompuesto ?>" class="btn btn-edit">Editar</a>
                            <a href="borrar.php?id=<?= $idCompuesto ?>" class="btn btn-del" onclick="return confirm('¿Seguro que quieres borrar a <?= $alumno['Nombre'] ?>?');">Borrar</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>
