<?php
require_once 'db.php';
session_start();

// Verificar si el usuario es root
$stmt = $pdo->prepare("SELECT correo FROM usuarios WHERE id = ?");
$stmt->execute([$_SESSION['usuario_id'] ?? 0]);
$correo = $stmt->fetchColumn();

if ($correo !== 'root@gmail.com') {
    die("Acceso denegado.");
}

$tipo = $_GET['tipo'] ?? null;
$id = $_GET['id'] ?? null;

$tipos_validos = ['publicacion', 'comentario', 'imagen'];

if (!in_array($tipo, $tipos_validos) || !is_numeric($id)) {
    die("Parámetros inválidos.");
}

$tabla = $tipo . 'es'; 

// Eliminar contenido de la tabla correspondiente
$stmt = $pdo->prepare("DELETE FROM $tabla WHERE id = ?");
$stmt->execute([$id]);

// Eliminar denuncias asociadas
$stmt = $pdo->prepare("DELETE FROM denuncias WHERE tipo_contenido = ? AND id_contenido = ?");
$stmt->execute([$tipo, $id]);

header("Location: ../admin_denuncias.php");
exit;
