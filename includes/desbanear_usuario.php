<?php
session_start();
require_once 'db.php';

// dolo root puede ejecutar la acción
$stmt = $pdo->prepare("SELECT correo FROM usuarios WHERE id = ?");
$stmt->execute([$_SESSION['usuario_id'] ?? 0]);
if ($stmt->fetchColumn() !== 'root@gmail.com') {
    die("Acceso denegado.");
}

$id = $_GET['id'] ?? null;

if (!$id || !is_numeric($id)) {
    die("ID de usuario no válido.");
}

// Quitar el baneo 
$stmt = $pdo->prepare("UPDATE usuarios SET baneo_fin = NULL WHERE id = ?");
$stmt->execute([$id]);

header("Location: ../listar_usuarios.php");
exit;
