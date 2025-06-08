<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';

$usuario_id = $_SESSION['usuario_id'];

// Obtener las publicaciones del usuario que NO estén finalizadas
$stmt = $pdo->prepare("
    SELECT p.id, p.texto, p.fecha_publicacion, p.localidad, p.tipo_animal
    FROM publicaciones p
    WHERE p.usuario_id = ?
    AND p.id NOT IN (SELECT publicacion_id FROM publicaciones_finalizadas)
    ORDER BY p.fecha_publicacion DESC
");
$stmt->execute([$usuario_id]);
$publicaciones = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mis publicaciones activas</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: system-ui, sans-serif;
            margin: 0;
            padding: 1rem;
            background-color: #f7f9fb;
            color: #000;
        }

        main {
            width: 90%;
            max-width: 1000px;
            margin: 2rem auto;
            background: #fff;
            border-radius: 8px;
            padding: 1.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        h2 {
            text-align: center;
            margin-bottom: 2rem;
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
            color: #007bff;
            text-decoration: none;
            font-weight: 500;
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
    <h2> Mis publicaciones activas</h2>

    <?php if (empty($publicaciones)): ?>
        <p>Aún no has publicado nada o todas tus publicaciones han sido finalizadas.</p>
    <?php else: ?>
        <ul>
            <?php foreach ($publicaciones as $p): ?>
                <li>
                    <a href="publicacion.php?id=<?= $p['id'] ?>">
                        <?= htmlspecialchars(substr($p['texto'], 0, 80)) ?>...
                    </a><br>
                    <small>
                         <?= htmlspecialchars($p['localidad']) ?> |
                         <?= htmlspecialchars($p['tipo_animal']) ?> |
                        🕒 <?= date('d/m/Y H:i', strtotime($p['fecha_publicacion'])) ?>
                    </small>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</main>

<?php include 'templates/footer.php'; ?>
</body>
</html>
