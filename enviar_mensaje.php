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

// Insertar el mensaje
$stmt = $pdo->prepare("
    INSERT INTO mensajes_chat (chat_id, emisor_id, receptor, mensaje)
    VALUES (?, ?, ?, ?)
");
$stmt->execute([$chat_id, $emisor_id, $receptor, $mensaje]);

header("Location: ../chat.php?id=" . $chat_id);
exit;
