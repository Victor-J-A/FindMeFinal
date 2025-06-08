<?php
require_once __DIR__ . '/db.php';
session_start();

// Verificar que el usuario es el administrador root
$stmt = $pdo->prepare("SELECT correo FROM usuarios WHERE id = ?");
$stmt->execute([$_SESSION['usuario_id'] ?? 0]);
if ($stmt->fetchColumn() !== 'root@gmail.com') {
    die("Acceso denegado.");
}

// Validar el parámetro recibido
$usuario_id = $_GET['id'] ?? null;
if (!is_numeric($usuario_id)) {
    die("ID de usuario no válido.");
}

// Obtener datos actuales del usuario
$stmt = $pdo->prepare("SELECT correo, baneos FROM usuarios WHERE id = ?");
$stmt->execute([$usuario_id]);
$usuario = $stmt->fetch();

if (!$usuario) {
    die("Usuario no encontrado.");
}

// Determinar la duración del baneo según el número actual de baneos
$baneos = (int)$usuario['baneos'];
$nuevo_baneos = $baneos + 1;
$fecha_actual = new DateTime();
$baneo_fin = null;

switch ($nuevo_baneos) {
    case 1:
        $fecha_actual->modify('+1 day');
        break;
    case 2:
        $fecha_actual->modify('+3 days');
        break;
    case 3:
        $fecha_actual->modify('+7 days');
        break;
    default:
        // Baneo indefinido: se establece una fecha muy lejana (año 9999)
        $fecha_actual = new DateTime('9999-12-31 23:59:59');
        break;
}

$baneo_fin = $fecha_actual->format('Y-m-d H:i:s');

// Aplicar el baneo en la base de datos
$stmt = $pdo->prepare("UPDATE usuarios SET baneos = ?, baneo_fin = ? WHERE id = ?");
$stmt->execute([$nuevo_baneos, $baneo_fin, $usuario_id]);

// Redirigir con mensaje opcional
header("Location: ../listar_usuarios.php?baneo=ok");
exit;
