<?php
require_once 'includes/db.php';
session_start();

if (!isset($_SESSION['usuario_id'])) {
    die("Debes iniciar sesión.");
}

$stmt = $pdo->prepare("SELECT correo FROM usuarios WHERE id = ?");
$stmt->execute([$_SESSION['usuario_id']]);
$correo = $stmt->fetchColumn();

if ($correo !== 'root@gmail.com') {
    die("Acceso denegado. No tienes permisos para ver esta página.");
}

$stmt = $pdo->query("
    SELECT d.*, u.correo AS denunciante
    FROM denuncias d
    LEFT JOIN usuarios u ON d.usuario_id = u.id
    WHERE d.tipo_contenido IN ('publicacion', 'comentario', 'imagen')
    ORDER BY d.fecha DESC
");
$denuncias_contenido = $stmt->fetchAll();

$stmt = $pdo->query("
    SELECT du.*, 
           u1.correo AS denunciante,
           u2.correo AS denunciado
    FROM denuncias_usuarios du
    LEFT JOIN usuarios u1 ON du.denunciante_id = u1.id
    LEFT JOIN usuarios u2 ON du.denunciado_id = u2.id
    ORDER BY du.fecha DESC
");
$denuncias_usuarios = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Gestión de denuncias</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        :root {
            --color-principal: #3366cc;
            --color-secundario: #224499;
        }

        body {
            font-family: Arial, sans-serif;
            background: #ffffff;
            color: #333;
            margin: 0;
            padding: 0 0 60px 0;
        }

        h2, h3 {
            margin-left: 5%;
        }

        h2 {
            font-size: 24px;
            margin-top: 30px;
            color: var(--color-secundario);
        }

        h3 {
            font-size: 20px;
            margin-top: 40px;
            color: var(--color-principal);
        }

        p {
            margin-left: 5%;
        }

        .table-wrapper {
            width: 90%;
            margin: 20px auto;
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
            box-shadow: 0 2px 5px rgba(0,0,0,0.08);
            min-width: 600px;
        }

        th, td {
            padding: 12px;
            border: 1px solid #ddd;
            text-align: left;
        }

        th {
            background-color: #f2f2f2;
            font-weight: bold;
        }

        a {
            color: var(--color-principal);
            text-decoration: none;
            font-weight: 500;
        }
        

        a:hover {
            color: var(--color-secundario);
            text-decoration: underline;
        }
        
        .acciones {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.acciones a {
    white-space: nowrap;
}

        
        .contenedor-boton-usuarios {
             margin-left: 5%;
              margin-top: 10px;
        }

       .boton-secundario {
    background-color: var(--color-principal);
    color: white;
    padding: 6px 10px;
    border-radius: 4px;
    font-weight: bold;
    text-decoration: none;
    margin-left: 15px;
    font-size: 14px;
    transition: background-color 0.2s ease;
}

.boton-secundario:hover {
    background-color: var(--color-secundario);
    color: white; 
    text-decoration: none;
}


        @media (max-width: 768px) {
            .table-wrapper {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }
            
              .contenedor-boton-usuarios {
        margin-left: 5%;
        margin-right: 5%;
        text-align: left;
    }

    .boton-secundario {
        display: inline-block;
        width: auto;
        padding: 10px 14px;
        font-size: 14px;
    }
        }
    </style>
</head>
<body>

<?php include 'templates/header.php'; ?>

<main>
    <h2> Lista de denuncias registradas</h2>

<h3> Denuncias sobre contenido</h3>

<?php if (empty($denuncias_contenido)): ?>
    <p>No hay denuncias de contenido activas.</p>
<?php else: ?>
    <div class="table-wrapper">
        <table>
            <tr>
                <th>Fecha</th>
                <th>Tipo</th>
                <th>ID contenido</th>
                <th>Denunciante</th>
                <th>Acciones</th>
            </tr>
            <?php foreach ($denuncias_contenido as $d): ?>
                <tr>
                    <td><?= htmlspecialchars($d['fecha']) ?></td>
                    <td><?= htmlspecialchars($d['tipo_contenido']) ?></td>
                    <td><?= htmlspecialchars($d['id_contenido']) ?></td>
                    <td><?= htmlspecialchars($d['denunciante'] ?? 'Anónimo') ?></td>
                    <td>
                        <div class="acciones">
                            <a href="ver_contenido_denunciado.php?tipo=<?= $d['tipo_contenido'] ?>&id=<?= $d['id_contenido'] ?>">Ver contenido</a>
                            <a href="includes/restaurar_contenido.php?tipo=<?= $d['tipo_contenido'] ?>&id=<?= $d['id_contenido'] ?>" onclick="return confirm('¿Restaurar este contenido?')">Restaurar</a>
                            <a href="includes/eliminar_contenido.php?tipo=<?= $d['tipo_contenido'] ?>&id=<?= $d['id_contenido'] ?>" onclick="return confirm('¿Eliminar definitivamente?')">Eliminar</a>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>
<?php endif; ?>


<h3>Denuncias entre usuarios</h3>
<p style="margin-left:5%;">
    <a href="listar_usuarios.php" class="boton-secundario">📋 Ver lista de usuarios</a>
</p>

<?php if (empty($denuncias_usuarios)): ?>
    <p>No hay denuncias de usuarios activas.</p>
<?php else: ?>
    <div class="table-wrapper">
        <table>
            <tr>
                <th>Fecha</th>
                <th>Denunciante</th>
                <th>Usuario denunciado</th>
                <th>Acciones</th>
            </tr>
            <?php foreach ($denuncias_usuarios as $du): ?>
                <tr>
                    <td><?= htmlspecialchars($du['fecha']) ?></td>
                    <td><?= htmlspecialchars($du['denunciante'] ?? 'Anónimo') ?></td>
                    <td><?= htmlspecialchars($du['denunciado'] ?? 'Desconocido') ?></td>
                    <td>
                        <div class="acciones">
                            <a href="ver_chat_denunciado.php?usuario1=<?= $du['denunciante_id'] ?>&usuario2=<?= $du['denunciado_id'] ?>">Ver conversación</a>
                            <a href="includes/eliminar_chat.php?usuario1=<?= $du['denunciante_id'] ?>&usuario2=<?= $du['denunciado_id'] ?>" onclick="return confirm('¿Eliminar todos los mensajes entre estos usuarios?')">Eliminar</a>
                            <a href="includes/eliminar_denuncia_usuario.php?denunciante_id=<?= $du['denunciante_id'] ?>&denunciado_id=<?= $du['denunciado_id'] ?>" onclick="return confirm('¿Restaurar el chat (quitar denuncia)?')">Restaurar</a>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>
<?php endif; ?>

</main>

<?php include 'templates/footer.php'; ?>
</body>
</html>
