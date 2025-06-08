<?php
require_once 'auth.php';
require_once 'db.php';

$usuario_id = $_SESSION['usuario_id'];
$publicacion_id = $_POST['publicacion_id'] ?? null;
$tipo = $_POST['tipo'] ?? null;

if (!in_array($tipo, ['like', 'dislike']) || !is_numeric($publicacion_id)) {
    die("Datos inválidos.");
}

$stmt = $pdo->prepare("INSERT INTO valoraciones (usuario_id, publicacion_id, tipo)
                       VALUES (?, ?, ?)
                       ON DUPLICATE KEY UPDATE tipo = VALUES(tipo)");
$stmt->execute([$usuario_id, $publicacion_id, $tipo]);

// Obtener el autor de la publicación
$stmt = $pdo->prepare("SELECT usuario_id FROM publicaciones WHERE id = ?");
$stmt->execute([$publicacion_id]);
$autor_id = $stmt->fetchColumn();

if (!$autor_id) {
    die("La publicación no existe.");
}

// Recontar los likes y dislikes totales que ha recibido el autor
$stmt = $pdo->prepare("
    SELECT
        SUM(CASE WHEN tipo = 'like' THEN 1 ELSE 0 END) AS likes,
        SUM(CASE WHEN tipo = 'dislike' THEN 1 ELSE 0 END) AS dislikes
    FROM valoraciones
    WHERE publicacion_id IN (
        SELECT id FROM publicaciones WHERE usuario_id = ?
    )
");
$stmt->execute([$autor_id]);
$recuento = $stmt->fetch();

$stmt = $pdo->prepare("UPDATE usuarios SET likes_recibidos = ?, dislikes_recibidos = ? WHERE id = ?");
$stmt->execute([$recuento['likes'], $recuento['dislikes'], $autor_id]);

header("Location: ../publicacion.php?id=" . $publicacion_id);
exit;
