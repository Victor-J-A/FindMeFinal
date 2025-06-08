<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';

$usuario_id = $_SESSION['usuario_id'];

if (isset($_GET['notificacion'])) {
    $notificacion_id = (int) $_GET['notificacion'];
    $stmt = $pdo->prepare("UPDATE notificaciones SET leida = 1 WHERE id = ? AND usuario_id = ?");
    $stmt->execute([$notificacion_id, $usuario_id]);
}

// Obtener mensajes del moderador
$stmt = $pdo->prepare("SELECT mensaje, fecha FROM mensajes_moderador WHERE destinatario_id = ? ORDER BY fecha DESC");
$stmt->execute([$usuario_id]);
$mensajes_moderador = $stmt->fetchAll();

// Obtener chats donde participa el usuario
$stmt = $pdo->prepare("
    SELECT c.id, p.texto AS titulo, c.creador_id, c.participante_id
    FROM chats c
    JOIN publicaciones p ON c.publicacion_id = p.id
    WHERE c.creador_id = ? OR c.participante_id = ?
    ORDER BY c.id DESC
");
$stmt->execute([$usuario_id, $usuario_id]);
$chats_raw = $stmt->fetchAll();

$chats = [];

foreach ($chats_raw as $chat) {
    $otro_id = ($chat['creador_id'] == $usuario_id) ? $chat['participante_id'] : $chat['creador_id'];

    $stmt_usuario = $pdo->prepare("SELECT correo FROM usuarios WHERE id = ?");
    $stmt_usuario->execute([$otro_id]);
    $correo_otro = $stmt_usuario->fetchColumn();

    $stmt_n = $pdo->prepare("SELECT COUNT(*) FROM notificaciones WHERE usuario_id = ? AND tipo = 'chat' AND chat_id = ? AND leida = 0");
    $stmt_n->execute([$usuario_id, $chat['id']]);
    $hay_nuevo = $stmt_n->fetchColumn() > 0;

    $chats[] = [
        'id' => $chat['id'],
        'titulo' => $chat['titulo'],
        'otro_usuario' => $correo_otro,
        'nuevo' => $hay_nuevo
    ];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mis chats privados</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: system-ui, sans-serif;
            margin: 0;
            padding: 1rem;
            background-color: #f7f7f7;
            color: #000;
        }

        main {
            width: 90%;
            max-width: 1000px;
            margin: 2rem auto;
            background-color: #fff;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        h2, h3 {
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
            color: #0066cc;
            text-decoration: none;
        }

        a:hover {
            text-decoration: underline;
        }

        .moderador {
            background-color: #f8f0e6;
            border: 1px solid #d6a76c;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }

        .moderador li {
            border-bottom: none;
        }

        small {
            color: #555;
        }
    </style>
</head>
<body>

<?php include 'templates/header.php'; ?>

<main>
    <h2>Mis chats privados</h2>

    <?php if (!empty($mensajes_moderador)): ?>
        <section class="moderador">
            <h3> Mensajes del moderador</h3>
            <ul>
                <?php foreach ($mensajes_moderador as $msg): ?>
                    <li>
                        <?= nl2br(htmlspecialchars($msg['mensaje'])) ?><br>
                        <small>🕒 <?= date('d/m/Y H:i', strtotime($msg['fecha'])) ?></small>
                    </li>
                <?php endforeach; ?>
            </ul>
        </section>
    <?php endif; ?>

    <section>
        <?php if (empty($chats)): ?>
            <p>No tienes chats activos por ahora.</p>
        <?php else: ?>
            <ul>
                <?php foreach ($chats as $chat): ?>
                    <li style="<?= $chat['nuevo'] ? 'font-weight: bold;' : '' ?>">
                        <a href="chat.php?id=<?= $chat['id'] ?>">
                            💬 Chat con <?= htmlspecialchars($chat['otro_usuario']) ?>
                            <?= $chat['nuevo'] ? '🔴' : '' ?>
                        </a><br>
                        <small>Sobre publicación: <?= htmlspecialchars(substr($chat['titulo'], 0, 50)) ?>...</small>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>
</main>

<?php include 'templates/footer.php'; ?>

</body>
</html>
