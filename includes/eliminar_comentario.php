<?php
require_once 'auth.php';
require_once 'db.php';

$comentario_id = $_POST['comentario_id'] ?? null;
$publicacion_id = $_POST['publicacion_id'] ?? null;
$usuario_logueado = $_SESSION['usuario_id'] ?? null;

if (!$comentario_id || !$publicacion_id || !$usuario_logueado) {
    die("Datos inválidos.");
}

// Verificar que el usuario logueado es el dueño de la publicación
$stmt = $pdo->prepare("SELECT usuario_id FROM publicaciones WHERE id = ?");
$stmt->execute([$publicacion_id]);
$dueno_id = $stmt->fetchColumn();

if (!$dueno_id || $dueno_id != $usuario_logueado) {
    die("No tienes permiso para eliminar este comentario.");
}

// Verificar autor del comentario
$stmt = $pdo->prepare("SELECT usuario_id, publicacion_id FROM comentarios WHERE id = ?");
$stmt->execute([$comentario_id]);
$comentario = $stmt->fetch();

if (!$comentario || $comentario['publicacion_id'] != $publicacion_id) {
    die("El comentario no pertenece a esta publicación.");
}

$autor_comentario = $comentario['usuario_id'];

$stmt = $pdo->prepare("UPDATE comentarios SET eliminado = 1 WHERE id = ?");
$stmt->execute([$comentario_id]);

if ($autor_comentario != $usuario_logueado) {
    $stmt = $pdo->prepare("UPDATE usuarios SET comentarios_eliminados = comentarios_eliminados + 1 WHERE id = ?");
    $stmt->execute([$autor_comentario]);
}

header("Location: ../publicacion.php?id=" . $publicacion_id);
exit;
