<?php
require_once './includes/db.php';
session_start();

$usuario_id = isset($_GET['id']) ? (int) $_GET['id'] : null;
$usuario_logueado_id = isset($_SESSION['usuario_id']) ? (int) $_SESSION['usuario_id'] : null;

if (!$usuario_id) {
    die("ID de usuario no especificado.");
}

// Obtener datos del usuario
$stmt = $pdo->prepare("
    SELECT correo, localidad, fecha_registro, likes_recibidos, dislikes_recibidos, comentarios_eliminados, baneos, baneo_fin
    FROM usuarios WHERE id = ?
");
$stmt->execute([$usuario_id]);
$usuario = $stmt->fetch();

if (!$usuario) {
    die("Usuario no encontrado.");
}

// Calcular tiempo restante de baneo si aplica
$baneo_activo = false;
$tiempo_restante = '';

if (!empty($usuario['baneo_fin']) && strtotime($usuario['baneo_fin']) > time()) {
    $baneo_activo = true;
    $diferencia = strtotime($usuario['baneo_fin']) - time();
    $horas = floor($diferencia / 3600);
    $minutos = floor(($diferencia % 3600) / 60);
    $tiempo_restante = "$horas horas y $minutos minutos restantes";
}

// Obtener publicaciones del usuario
$stmt = $pdo->prepare("
    SELECT p.id, p.texto, p.fecha_publicacion, pf.id AS finalizada_id
    FROM publicaciones p
    LEFT JOIN publicaciones_finalizadas pf ON pf.publicacion_id = p.id
    WHERE p.usuario_id = ?
    ORDER BY p.fecha_publicacion DESC
");
$stmt->execute([$usuario_id]);
$publicaciones = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Perfil de usuario</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: system-ui, sans-serif;
            margin: 0;
            padding: 1rem;
            background-color: #f9f9f9;
            color: #000;
        }

        main {
            width: 90%;
            max-width: 1000px;
            margin: 2rem auto;
            background-color: #fff;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        h2, h3 {
            text-align: center;
            margin-bottom: 1rem;
        }

        ul {
            list-style: none;
            padding-left: 0;
        }

        li {
            margin-bottom: 15px;
            border-bottom: 1px solid #ccc;
            padding-bottom: 10px;
        }

        a {
            color: #0066cc;
            text-decoration: none;
        }

        a:hover {
            text-decoration: underline;
        }

        small {
            color: #555;
        }
    </style>
</head>
<body>

<?php include 'templates/header.php'; ?>

<main>
    <h2>Perfil de <?= htmlspecialchars($usuario['correo']) ?></h2>

    <section>
        <ul>
            <li><strong>Localidad:</strong> <?= htmlspecialchars($usuario['localidad']) ?></li>
            <li><strong>Fecha de registro:</strong> <?= date('d/m/Y', strtotime($usuario['fecha_registro'])) ?></li>
            <li><strong>👍 Likes recibidos:</strong> <?= $usuario['likes_recibidos'] ?></li>
            <li><strong>👎 Dislikes recibidos:</strong> <?= $usuario['dislikes_recibidos'] ?></li>
            <li><strong>💬 Comentarios eliminados por otros:</strong> <?= $usuario['comentarios_eliminados'] ?></li>
            <li><strong>🚫 Baneos totales:</strong> <?= $usuario['baneos'] ?></li>
            <?php if ($baneo_activo): ?>
                <li style="color: red;"><strong>❗ Usuario actualmente baneado:</strong> <?= $tiempo_restante ?></li>
            <?php endif; ?>
        </ul>
    </section>

    <section>
        <h3>Todas mis Publicaciones</h3>

        <?php if (empty($publicaciones)): ?>
            <p>Este usuario no ha realizado publicaciones todavía.</p>
        <?php else: ?>
            <ul>
                <?php foreach ($publicaciones as $pub): ?>
                    <li>
                        <a href="publicacion.php?id=<?= $pub['id'] ?>">
                            <?= htmlspecialchars(substr($pub['texto'], 0, 50)) ?>...
                        </a><br>
                        <small>Publicado el <?= date('d/m/Y H:i', strtotime($pub['fecha_publicacion'])) ?></small>
                        <?php if ($pub['finalizada_id']): ?>
                            <br><span style="color: green;"><strong>Terminada</strong></span>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>
</main>

<?php include 'templates/footer.php'; ?>

</body>
</html>
