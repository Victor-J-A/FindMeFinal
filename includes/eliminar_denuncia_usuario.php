<?php
session_start();
require_once 'db.php';

// Verificar que el usuario es root
$stmt = $pdo->prepare("SELECT correo FROM usuarios WHERE id = ?");
$stmt->execute([$_SESSION['usuario_id'] ?? 0]);
if ($stmt->fetchColumn() !== 'root@gmail.com') {
    die("Acceso denegado.");
}

// Obtener IDs desde la URL
$denunciante_id = $_GET['denunciante_id'] ?? null;
$denunciado_id = $_GET['denunciado_id'] ?? null;

if (!is_numeric($denunciante_id) || !is_numeric($denunciado_id)) {
    die("Parámetros inválidos.");
}

// Eliminar la denuncia entre los usuarios (independientemente del orden)
$stmt = $pdo->prepare("
    DELETE FROM denuncias_usuarios
    WHERE (denunciante_id = ? AND denunciado_id = ?)
       OR (denunciante_id = ? AND denunciado_id = ?)
");
$stmt->execute([$denunciante_id, $denunciado_id, $denunciado_id, $denunciante_id]);

// Redirigir de vuelta al panel de denuncias
header("Location: ../admin_denuncias.php");
exit;
