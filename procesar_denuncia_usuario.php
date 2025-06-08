<?php
session_start();
require_once 'db.php';

$denunciante = $_SESSION['usuario_id'] ?? null;
$usuario_denunciado_id = $_POST['usuario_denunciado_id'] ?? null;
$motivo = trim($_POST['motivo'] ?? '');

if (!$denunciante || !is_numeric($usuario_denunciado_id)) {
    die("Datos inválidos.");
}

$stmt = $pdo->prepare("INSERT INTO denuncias (usuario_id, tipo_contenido, id_contenido, usuario_denunciado_id) VALUES (?, 'usuario', 0, ?)");
$stmt->execute([$denunciante, $usuario_denunciado_id]);

header("Location: ../index.php");
exit;
