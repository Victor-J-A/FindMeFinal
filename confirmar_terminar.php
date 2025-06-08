<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';

$usuario_id = $_SESSION['usuario_id'];
$publicacion_id = $_POST['publicacion_id'] ?? null;
$mensaje = trim($_POST['mensaje'] ?? '');

if (!$publicacion_id || empty($mensaje)) {
    die("Datos incompletos.");
}

// Verificar que la publicación pertenece al usuario
$stmt = $pdo->prepare("SELECT * FROM publicaciones WHERE id = ? AND usuario_id = ?");
$stmt->execute([$publicacion_id, $usuario_id]);
$publicacion = $stmt->fetch();

if (!$publicacion) {
    die("No autorizado para finalizar esta publicación.");
}

// Guardar el mensaje de finalización
$stmt = $pdo->prepare("INSERT INTO publicaciones_finalizadas (publicacion_id, mensaje) VALUES (?, ?)");
$stmt->execute([$publicacion_id, $mensaje]);

header("Location: ./publicacion.php?id=" . $publicacion_id);
exit;
