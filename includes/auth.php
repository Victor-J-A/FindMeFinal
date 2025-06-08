<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION["usuario_id"])) {
    header("Location: login.php");
    exit;
}

require_once 'db.php';

$usuario_id = $_SESSION['usuario_id'];

$stmt = $pdo->prepare("SELECT baneo_fin FROM usuarios WHERE id = ?");
$stmt->execute([$usuario_id]);
$baneo_fin = $stmt->fetchColumn();

if ($baneo_fin && $baneo_fin > date('Y-m-d H:i:s')) {
    session_destroy();
    die("Tu cuenta ha sido temporalmente restringida. Podrás acceder de nuevo el " . date('d/m/Y H:i', strtotime($baneo_fin)) . ".");
}
