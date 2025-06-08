<?php
session_start();
require_once 'db.php';

$denunciante_id = $_SESSION['usuario_id'] ?? null;
$denunciado_id = $_POST['denunciado_id'] ?? null;

if (!$denunciante_id || !$denunciado_id || $denunciante_id == $denunciado_id) {
    die("Datos inválidos.");
}

// Evitar duplicados con UNIQUE o IGNORE
$stmt = $pdo->prepare("INSERT IGNORE INTO denuncias_usuarios (denunciante_id, denunciado_id) VALUES (?, ?)");
$stmt->execute([$denunciante_id, $denunciado_id]);

// Redirigir de vuelta al chat con el ID original
// Primero buscamos el ID del chat común entre ambos
$stmt = $pdo->prepare("
    SELECT id FROM chats 
    WHERE (creador_id = ? AND participante_id = ?) 
       OR (creador_id = ? AND participante_id = ?)
    LIMIT 1
");
$stmt->execute([$denunciante_id, $denunciado_id, $denunciado_id, $denunciante_id]);
$chat = $stmt->fetch();

if ($chat) {
    header("Location: ../chat.php?id=" . $chat['id']);
} else {
    echo "Denuncia realizada, pero no se encontró el chat para redirigir.";
}
exit;
