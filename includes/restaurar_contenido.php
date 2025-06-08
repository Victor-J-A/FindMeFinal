<?php
require_once 'db.php';
session_start();

// Verificar si el usuario logueado es root
$stmt = $pdo->prepare("SELECT correo FROM usuarios WHERE id = ?");
$stmt->execute([$_SESSION['usuario_id'] ?? 0]);
$correo = $stmt->fetchColumn();

if ($correo !== 'root@gmail.com') {
    die("Acceso denegado.");
}

// Validar parámetros
$tipo = $_GET['tipo'] ?? null;
$id = $_GET['id'] ?? null;

$tipos_validos = ['publicacion', 'comentario', 'imagen'];

if (!in_array($tipo, $tipos_validos) || !is_numeric($id)) {
    die("Datos inválidos.");
}

// Mapeo correcto del tipo al nombre de tabla
$tabla_map = [
    'publicacion' => 'publicaciones',
    'comentario'  => 'comentarios',
    'imagen'      => 'imagenes'
];

$tabla = $tabla_map[$tipo];

$stmt = $pdo->prepare("UPDATE $tabla SET denunciado = 0 WHERE id = ?");
$stmt->execute([$id]);

$stmt = $pdo->prepare("DELETE FROM denuncias WHERE tipo_contenido = ? AND id_contenido = ?");
$stmt->execute([$tipo, $id]);

header("Location: ../admin_denuncias.php");
exit;
