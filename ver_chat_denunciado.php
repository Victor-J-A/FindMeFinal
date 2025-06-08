<?php
require_once 'includes/db.php';
session_start();

// Verificar si el usuario es root
$stmt = $pdo->prepare("SELECT correo FROM usuarios WHERE id = ?");
$stmt->execute([$_SESSION['usuario_id'] ?? 0]);
if ($stmt->fetchColumn() !== 'root@gmail.com') {
    die("Acceso denegado.");
}

// Validar parámetros
$usuario1 = $_GET['usuario1'] ?? null;
$usuario2 = $_GET['usuario2'] ?? null;

if (!is_numeric($usuario1) || !is_numeric($usuario2)) {
    die("Parámetros inválidos.");
}

// Obtener el chat entre los dos usuarios
$stmt = $pdo->prepare("
    SELECT * FROM chats 
    WHERE (creador_id = ? AND participante_id = ?) 
       OR (creador_id = ? AND participante_id = ?)
");
$stmt->execute([$usuario1, $usuario2, $usuario2, $usuario1]);
$chat = $stmt->fetch();

if (!$chat) {
    die("No se encontró el chat entre estos usuarios.");
}

// Obtener mensajes del chat
$stmt = $pdo->prepare("SELECT * FROM mensajes_chat WHERE chat_id = ? ORDER BY fecha_envio ASC");
$stmt->execute([$chat['id']]);
$mensajes = $stmt->fetchAll();

// Obtener correos de ambos usuarios
$stmt = $pdo->prepare("SELECT id, correo FROM usuarios WHERE id IN (?, ?)");
$stmt->execute([$usuario1, $usuario2]);
$usuarios = $stmt->fetchAll(PDO::FETCH_KEY_PAIR); // [id => correo]
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Conversación denunciada</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
#chat-denunciado {
    font-family: "Segoe UI", sans-serif;
    background-color: #f8f9fa;
    margin: 0;
    padding: 0;
}

#chat-denunciado main {
    max-width: 800px;
    margin: 60px auto;
    background: white;
    padding: 2rem;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

#chat-denunciado h2 {
    text-align: center;
    color: #0d6efd;
    margin-bottom: 1.5rem;
}

#chat-denunciado .chat-info {
    font-size: 1rem;
    margin-bottom: 1.2rem;
}

#chat-denunciado .chat-box {
    border: 1px solid #ccc;
    padding: 1rem;
    max-height: 400px;
    overflow-y: auto;
    background-color: #f1f1f1;
    border-radius: 6px;
}

#chat-denunciado .mensaje {
    display: inline-block;
    max-width: 70%;
    padding: 0.6rem 0.9rem;
    border-radius: 10px;
    margin: 0.4rem 0;
    word-wrap: break-word;
    background-color: #eee;
}

#chat-denunciado .usuario1 {
    background-color: #007bff;
    color: white;
    margin-left: auto;
    text-align: left;
    border-top-right-radius: 0;
    display: block;
    width: fit-content;
}

#chat-denunciado .usuario2 {
    background-color: #dceeff;
    color: #000;
    margin-right: auto;
    text-align: left;
    border-top-left-radius: 0;
    display: block;
    width: fit-content;
}

#chat-denunciado .mensaje strong {
    display: block;
    margin-bottom: 0.3rem;
}

#chat-denunciado .mensaje small {
    display: block;
    margin-top: 0.4rem;
    font-size: 0.75rem;
    opacity: 0.8;
}

#chat-denunciado .usuario1 small {
    color: rgba(255,255,255,0.9);
}

#chat-denunciado .usuario2 small {
    color: #333;
}

#chat-denunciado a {
    display: inline-block;
    margin-top: 1.5rem;
    text-decoration: none;
    color: #0d6efd;
}
    </style>
</head>
<body id="chat-denunciado">

<?php include 'templates/header.php'; ?>

<main>
    <h2> Conversación denunciada</h2>

    <p class="chat-info"><strong>ID del Chat:</strong> <?= $chat['id'] ?></p>

    <section class="chat-box">
        <?php if (empty($mensajes)): ?>
            <p>No hay mensajes en este chat.</p>
        <?php else: ?>
            <?php foreach ($mensajes as $m): ?>
<div class="mensaje <?= $m['emisor_id'] == $usuario1 ? 'usuario1' : 'usuario2' ?>">
                    <strong><?= htmlspecialchars($usuarios[$m['emisor_id']] ?? 'Usuario desconocido') ?>:</strong>
                    <?= nl2br(htmlspecialchars($m['mensaje'])) ?>
                    <small><?= date('d/m/Y H:i', strtotime($m['fecha_envio'])) ?></small>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </section>

    <p><a href="admin_denuncias.php">← Volver a administración</a></p>
</main>

<?php include 'templates/footer.php'; ?>

</body>
</html>
