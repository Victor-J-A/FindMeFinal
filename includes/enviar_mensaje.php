<?php
require_once 'auth.php';
require_once 'db.php';

$chat_id = $_POST['chat_id'] ?? null;
$mensaje = trim($_POST['mensaje'] ?? '');
$receptor = $_POST['receptor'] ?? null;
$emisor_id = $_SESSION['usuario_id'] ?? null;

if (!$chat_id || empty($mensaje) || !$receptor) {
    die("Datos del mensaje incompletos.");
}

if (!in_array($receptor, ['creador', 'participante'])) {
    die("Receptor no válido.");
}

// Verificar que el usuario participa en el chat
$stmt = $pdo->prepare("SELECT * FROM chats WHERE id = ? AND (creador_id = ? OR participante_id = ?)");
$stmt->execute([$chat_id, $emisor_id, $emisor_id]);
$chat = $stmt->fetch();

if (!$chat) {
    die("No estás autorizado a enviar mensajes en este chat.");
}

// Identificar al otro usuario
$otro_usuario_id = ($chat['creador_id'] == $emisor_id) ? $chat['participante_id'] : $chat['creador_id'];

// Verificar si hay una denuncia entre ambos
$stmt = $pdo->prepare("SELECT 1 FROM denuncias_usuarios WHERE 
    (denunciante_id = ? AND denunciado_id = ?) 
    OR 
    (denunciante_id = ? AND denunciado_id = ?)");
$stmt->execute([$emisor_id, $otro_usuario_id, $otro_usuario_id, $emisor_id]);
$denuncia_existente = $stmt->fetchColumn();

if ($denuncia_existente) {
    die("No puedes enviar mensajes a este usuario porque hay una denuncia activa.");
}

// Insertar el mensaje
$stmt = $pdo->prepare("
    INSERT INTO mensajes_chat (chat_id, emisor_id, receptor, mensaje)
    VALUES (?, ?, ?, ?)
");
$stmt->execute([$chat_id, $emisor_id, $receptor, $mensaje]);

// Insertar notificación para el otro usuario (si no es el emisor)
if ($otro_usuario_id != $emisor_id) {
    $stmt = $pdo->prepare("
        INSERT INTO notificaciones (usuario_id, tipo, mensaje, chat_id, leida)
        VALUES (?, 'chat', 'Nuevo mensaje privado', ?, 0)
    ");
    $stmt->execute([$otro_usuario_id, $chat_id]);
}

header("Location: ../chat.php?id=" . $chat_id);
exit;
