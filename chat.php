<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';

$chat_id = $_GET['id'] ?? null;
$usuario_id = $_SESSION['usuario_id'];

if (isset($_GET['notificacion'])) {
    $notificacion_id = (int) $_GET['notificacion'];
    $stmt = $pdo->prepare("UPDATE notificaciones SET leida = 1 WHERE id = ? AND usuario_id = ?");
    $stmt->execute([$notificacion_id, $usuario_id]);
}

if (!$chat_id) {
    die("Chat no especificado.");
}

$stmt = $pdo->prepare("SELECT * FROM chats WHERE id = ? AND (creador_id = ? OR participante_id = ?)");
$stmt->execute([$chat_id, $usuario_id, $usuario_id]);
$chat = $stmt->fetch();

if (!$chat) {
    die("No tienes acceso a este chat.");
}

$soy_creador = $chat['creador_id'] == $usuario_id;
$otro_usuario_id = $soy_creador ? $chat['participante_id'] : $chat['creador_id'];

$stmt = $pdo->prepare("UPDATE notificaciones SET leida = 1 WHERE usuario_id = ? AND tipo = 'chat' AND chat_id = ?");
$stmt->execute([$usuario_id, $chat_id]);

$stmt = $pdo->prepare("SELECT 1 FROM denuncias WHERE tipo_contenido = 'usuario' AND ((usuario_id = ? AND id_contenido = ?) OR (usuario_id = ? AND id_contenido = ?))");
$stmt->execute([$usuario_id, $otro_usuario_id, $otro_usuario_id, $usuario_id]);
$bloqueado = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT id, correo FROM usuarios WHERE id IN (?, ?)");
$stmt->execute([$usuario_id, $otro_usuario_id]);
$usuarios = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

$nombre_otro_usuario = htmlspecialchars($usuarios[$otro_usuario_id] ?? 'Usuario');

$stmt = $pdo->prepare("SELECT * FROM mensajes_chat WHERE chat_id = ? ORDER BY fecha_envio ASC");
$stmt->execute([$chat_id]);
$mensajes = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Chat privado</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
body {
    font-family: system-ui, sans-serif;
    background-color: #f9f9f9;
    margin: 0;
    padding: 1rem;
    color: #000;
}

main {
    width: 90%;
    max-width: 1000px;
    margin: auto;
    background-color: #fff;
    padding: 1.5rem;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

h2 {
    text-align: center;
    margin-bottom: 1.5rem;
}

#zona-mensajes {
    border: 1px solid #ccc;
    padding: 1rem;
    max-height: 400px;
    overflow-y: auto;
    margin-bottom: 1rem;
    background: linear-gradient(to bottom, #ffffff, #f2f2f2);
    border-radius: 8px;
    display: flex;
    flex-direction: column;
}

.mensaje {
    margin-bottom: 1rem;
    padding: 0.6rem 1rem;
    border-radius: 12px;
    max-width: 80%;
    background-color: #d0e8ff;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    text-align: left;
    align-self: flex-start;
}

.mensaje.tuyo {
    background-color: #007bff;
    color: white;
    align-self: flex-end;
}

.mensaje small {
    color: rgba(0, 0, 0, 0.5);
    display: block;
    margin-top: 4px;
}

.mensaje.tuyo small {
    color: rgba(255, 255, 255, 0.7);
}

.mensaje strong {
    display: inline-block;
    margin-bottom: 6px;
}

.mensaje.tuyo strong {
    margin-bottom: 8px;
}

textarea {
    width: 100%;
    max-width: 100%;
    box-sizing: border-box;
    margin-bottom: 1rem;
    font-family: inherit;
}

button {
    background-color: #007bff;
    color: #fff;
    border: none;
    padding: 0.5rem 1rem;
    cursor: pointer;
    border-radius: 4px;
}

button:hover {
    background-color: #0056b3;
}

.volver {
    display: inline-block;
    background-color: #007bff;
    color: white;
    padding: 0.5rem 1rem;
    text-decoration: none;
    border-radius: 4px;
}

.volver:hover {
    background-color: #0056b3;
}

.denuncia {
    color: white;
    background-color: #dc3545;
    border: none;
    padding: 0.5rem 1rem;
    border-radius: 4px;
    font-size: 15px;
}

.denuncia:hover {
    background-color: #c82333;
}

.denunciado {
    color: red;
    font-weight: bold;
}

.acciones-chat {
    margin-bottom: 1.5rem;
}

.acciones-chat form {
    width: 100%;
    display: flex;
    flex-direction: column;
    align-items: center;
}

.botones-chat {
    display: flex;
    flex-direction: row;
    gap: 1rem;
    justify-content: center;
    margin-top: 0.5rem;
}

.botones-chat button,
.botones-chat a {
    width: auto;
    min-width: 150px;
    text-align: center;
    padding: 0.5rem 1rem;
    border-radius: 4px;
}

@media (max-width: 490px) {
    .botones-chat {
        flex-direction: column;
        width: 100%;
        align-items: center;
    }

    .botones-chat button,
    .botones-chat a {
        width: 80%;
        margin: 0.25rem 0;
    }
}

@media (max-width: 400px) {
    textarea {
        font-size: 14px;
    }
}
    </style>
</head>
<body>

<?php include 'templates/header.php'; ?>

<main>
    <h2>Chat privado con <?= $nombre_otro_usuario ?></h2>

<?php if (!$bloqueado): ?>
    <div class="acciones-chat">
        <form method="POST" action="includes/denunciar_usuario.php" onsubmit="return confirm('¿Estás seguro de que quieres denunciar a este usuario?');" style="margin: 0;">
            <input type="hidden" name="denunciado_id" value="<?= $otro_usuario_id ?>">
            <div class="botones-chat">
                <a href="mis_chats.php" class="volver">← Volver a mis chats</a>
                <button type="submit" class="denuncia">⚠️ Denunciar usuario</button>
                
            </div>
        </form>
    </div>
<?php else: ?>
    <p class="denunciado">Este chat está bloqueado por una denuncia. No se pueden enviar más mensajes.</p>
    <p><a href="mis_chats.php" class="volver">← Volver a mis chats</a></p>
<?php endif; ?>


    <section id="zona-mensajes">
        <?php if (empty($mensajes)): ?>
            <p>No hay mensajes aún.</p>
        <?php else: ?>
            <?php foreach ($mensajes as $index => $msg): ?>
                <?php
                    $es_ultimo = ($index === array_key_last($mensajes));
                    $clase = $msg['emisor_id'] == $usuario_id ? 'mensaje tuyo' : 'mensaje';
                ?>
                <div class="<?= $clase ?>" <?= $es_ultimo ? 'id="ultimo_mensaje"' : '' ?>>
                    <?php if ($msg['emisor_id'] == $usuario_id): ?>
                        <strong>Tú:</strong><br>
                    <?php endif; ?>
                    <?= nl2br(htmlspecialchars($msg['mensaje'])) ?>
                    <small><?= date('d/m/Y H:i', strtotime($msg['fecha_envio'])) ?></small>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </section>

    <?php if (!$bloqueado): ?>
        <form method="POST" action="includes/enviar_mensaje.php">
            <input type="hidden" name="chat_id" value="<?= $chat_id ?>">
            <input type="hidden" name="receptor" value="<?= $soy_creador ? 'participante' : 'creador' ?>">
            <textarea name="mensaje" rows="3" required placeholder="Escribe tu mensaje aquí..."></textarea>
            <button type="submit">Enviar</button>
        </form>
    <?php endif; ?>
</main>

<script>
    window.addEventListener('DOMContentLoaded', () => {
        const ultimo = document.getElementById('ultimo_mensaje');
        if (ultimo) {
            ultimo.scrollIntoView({ behavior: 'smooth', block: 'end' });
        }
    });
</script>

<?php include 'templates/footer.php'; ?>

</body>
</html>
