<?php
session_start();
require_once 'includes/db.php';

$usuario_logueado = $_SESSION['usuario_id'] ?? null;

if (!$usuario_logueado) {
    die("Debes estar logueado para denunciar.");
}

$usuario_denunciado_id = isset($_POST['usuario_denunciado_id']) ? (int) $_POST['usuario_denunciado_id'] : null;

if (!$usuario_denunciado_id) {
    die("ID de usuario inválido.");
}

// Verificar que el usuario a denunciar existe
$stmt = $pdo->prepare("SELECT id FROM usuarios WHERE id = ?");
$stmt->execute([$usuario_denunciado_id]);
if (!$stmt->fetch()) {
    die("El usuario que intentas denunciar no existe.");
}

// Verificar si ya lo ha denunciado
$stmt = $pdo->prepare("SELECT COUNT(*) FROM denuncias WHERE usuario_id = ? AND id_contenido = ? AND tipo_contenido = 'usuario'");
$stmt->execute([$usuario_logueado, $usuario_denunciado_id]);
if ($stmt->fetchColumn() > 0) {
    header("Location: perfil.php?id=$usuario_denunciado_id&mensaje=ya_denunciado");
    exit;
}

// Insertar denuncia
$stmt = $pdo->prepare("INSERT INTO denuncias (usuario_id, tipo_contenido, id_contenido, fecha) VALUES (?, 'usuario', ?, NOW())");
$stmt->execute([$usuario_logueado, $usuario_denunciado_id]);

header("Location: perfil.php?id=$usuario_denunciado_id&mensaje=denuncia_ok");
exit;
