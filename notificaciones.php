<?php
session_start();

require_once 'includes/db.php';
require_once 'includes/auth.php';

// Verificamos que el usuario esté autenticado
$usuario_id = $_SESSION['usuario_id'] ?? null;
if (!$usuario_id) {
    header("Location: login.php");
    exit;
}

// Marcar las notificaciones como "vistas temporalmente" para ocultar el aviso en el header
$_SESSION['notificaciones_vistas'] = true;

// Obtener todas las notificaciones NO leídas ordenadas por fecha
$stmt = $pdo->prepare("
    SELECT n.*, p.id AS publicacion_id, p.texto
    FROM notificaciones n
    LEFT JOIN publicaciones p ON n.publicacion_id = p.id
    WHERE n.usuario_id = ? AND n.leida = 0
    ORDER BY n.fecha ASC
");
$stmt->execute([$usuario_id]);
$notificaciones_brutas = $stmt->fetchAll();

// Filtrar notificaciones duplicadas de tipo 'chat'
$notificaciones_unicas = [];
$chat_vistos = [];

foreach ($notificaciones_brutas as $n) {
    if ($n['tipo'] === 'chat') {
        $clave = $n['chat_id'];
        if (!isset($chat_vistos[$clave])) {
            $notificaciones_unicas[] = $n;
            $chat_vistos[$clave] = true;
        }
    } else {
        $notificaciones_unicas[] = $n;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mis notificaciones</title>
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

        .notificacion-item {
            border-bottom: 1px solid #ccc;
            padding: 1rem 0;
        }

        .notificacion-item a {
            color: #007bff;
            text-decoration: none;
        }

        .notificacion-item a:hover {
            text-decoration: underline;
        }

        .notificacion-item small {
            color: #666;
        }

        @media (max-width: 380px) {
        
            
            .notificacion-item {
                font-size: 14px;
            }

            .notificacion-item a,
            .notificacion-item strong,
            .notificacion-item small {
                font-size: 14px;
            }

            .notificacion-item small {
                font-size: 12px;
            }
        }
    </style>
</head>
<body>

<?php include 'templates/header.php'; ?>

<main>
    <h2>Mis notificaciones nuevas</h2>

    <?php if (empty($notificaciones_unicas)): ?>
        <p>No tienes notificaciones pendientes.</p>
    <?php else: ?>
        <ul>
            <?php foreach ($notificaciones_unicas as $n): ?>
                <li class="notificacion-item">
                    <strong>Tipo:</strong> <?= htmlspecialchars($n['tipo']) ?><br>
                    <strong>Mensaje:</strong> <?= htmlspecialchars($n['mensaje']) ?><br>

                    <?php if ($n['tipo'] === 'publicacion' && $n['publicacion_id']): ?>
                        <a href="publicacion.php?id=<?= $n['publicacion_id'] ?>&notificacion=<?= $n['id'] ?>"> Ver publicación</a><br>
                    <?php elseif ($n['tipo'] === 'chat'): ?>
                        <a href="mis_chats.php?notificacion=<?= $n['id'] ?>"> Ver conversación</a><br>
                    <?php elseif ($n['tipo'] === 'comentario' && $n['publicacion_id']): ?>
                        <a href="publicacion.php?id=<?= $n['publicacion_id'] ?>&notificacion=<?= $n['id'] ?>"> Ver comentario</a><br>
                    <?php endif; ?>

                    <small><em><?= date('d/m/Y H:i', strtotime($n['fecha'])) ?></em></small>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</main>

<?php include 'templates/footer.php'; ?>
</body>
</html>
