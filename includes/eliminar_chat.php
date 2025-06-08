<?php
session_start();
require_once 'db.php';

// Verificar que el usuario es root
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
$stmt = $pdo->prepare("SELECT id FROM chats WHERE 
    (creador_id = ? AND participante_id = ?) 
    OR (creador_id = ? AND participante_id = ?)");
$stmt->execute([$usuario1, $usuario2, $usuario2, $usuario1]);
$chat = $stmt->fetch();

if (!$chat) {
    die("Chat no encontrado.");
}

// Eliminar mensajes del chat
$stmt = $pdo->prepare("DELETE FROM mensajes_chat WHERE chat_id = ?");
$stmt->execute([$chat['id']]);

$stmt = $pdo->prepare("DELETE FROM chats WHERE id = ?");
$stmt->execute([$chat['id']]);

// Eliminar la denuncia entre usuarios (si existe)
$stmt = $pdo->prepare("DELETE FROM denuncias_usuarios WHERE 
    (denunciante_id = ? AND denunciado_id = ?) 
    OR (denunciante_id = ? AND denunciado_id = ?)");
$stmt->execute([$usuario1, $usuario2, $usuario2, $usuario1]);

header("Location: ../admin_denuncias.php");
exit;
