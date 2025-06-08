<?php
require_once 'auth.php';
require_once 'db.php';

$publicacion_id = $_POST['publicacion_id'] ?? null;
$destinatario_id = $_POST['destinatario_id'] ?? null;
$creador_id = $_SESSION['usuario_id'];

if (!$publicacion_id || !$destinatario_id) {
    die("Datos incompletos.");
}

if ($destinatario_id == $creador_id) {
    die("No puedes iniciar un chat contigo mismo.");
}

// Verificar si el usuario es dueño de la publicación
$stmt = $pdo->prepare("SELECT usuario_id FROM publicaciones WHERE id = ?");
$stmt->execute([$publicacion_id]);
$dueno = $stmt->fetchColumn();

if (!$dueno) {
    die("La publicación no existe.");
}

if ($dueno != $creador_id) {
    die("No puedes iniciar un chat en una publicación que no es tuya.");
}

// Verificar si ya existe un chat entre estas personas para esta publicación
$stmt = $pdo->prepare("SELECT id FROM chats WHERE publicacion_id = ? AND creador_id = ? AND participante_id = ?");
$stmt->execute([$publicacion_id, $creador_id, $destinatario_id]);
$chat_existente = $stmt->fetchColumn();

if (!$chat_existente) {
    $stmt = $pdo->prepare("INSERT INTO chats (publicacion_id, creador_id, participante_id) VALUES (?, ?, ?)");
    $stmt->execute([$publicacion_id, $creador_id, $destinatario_id]);
    $chat_id = $pdo->lastInsertId();
} else {
    $chat_id = $chat_existente;
}

header("Location: ../chat.php?id=" . $chat_id);
exit;
