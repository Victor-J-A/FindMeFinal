<?php
session_start();
require_once 'db.php';

$usuario_id = $_SESSION['usuario_id'] ?? null;

if (!$usuario_id) {
    die("Debes iniciar sesión para denunciar contenido.");
}

$tipo = $_POST['tipo'] ?? null;
$id_contenido = $_POST['id'] ?? null;

$tipos_validos = ['publicacion', 'comentario', 'imagen'];

if (!in_array($tipo, $tipos_validos) || !is_numeric($id_contenido)) {
    die("Datos inválidos.");
}

$stmt = $pdo->prepare("INSERT INTO denuncias (usuario_id, tipo_contenido, id_contenido) VALUES (?, ?, ?)");
$stmt->execute([$usuario_id, $tipo, $id_contenido]);

// Determinar la tabla real para actualizar
switch ($tipo) {
    case 'publicacion':
        $tabla = 'publicaciones';
        break;
    case 'comentario':
        $tabla = 'comentarios';
        break;
    case 'imagen':
        $tabla = 'imagenes';
        break;
    default:
        die("Tipo no válido.");
}

// Contar denuncias para ese contenido
$stmt = $pdo->prepare("SELECT COUNT(*) FROM denuncias WHERE tipo_contenido = ? AND id_contenido = ?");
$stmt->execute([$tipo, $id_contenido]);
$total_denuncias = (int)$stmt->fetchColumn();

// Cantidad de denuncias necesatias para ocultar 
if ($total_denuncias >= 1) {
    $stmt = $pdo->prepare("UPDATE {$tabla} SET denunciado = 1 WHERE id = ?");
    $stmt->execute([$id_contenido]);
}

header("Location: ../index.php");
exit;
