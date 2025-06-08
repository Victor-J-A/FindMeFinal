<?php
require_once 'auth.php';
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario_id = $_SESSION['usuario_id'];
    $publicacion_id = $_POST['publicacion_id'] ?? null;
    $contenido = trim($_POST['contenido']);

    if (!$publicacion_id || empty($contenido)) {
        die("Datos incompletos.");
    }

    // Insertar el comentario
    $stmt = $pdo->prepare("INSERT INTO comentarios (publicacion_id, usuario_id, contenido) VALUES (?, ?, ?)");
    $stmt->execute([$publicacion_id, $usuario_id, $contenido]);

    // Obtener autor de la publicación
    $stmt = $pdo->prepare("SELECT usuario_id FROM publicaciones WHERE id = ?");
    $stmt->execute([$publicacion_id]);
    $autor_id = $stmt->fetchColumn();

    // Crear notificación si el autor no es quien comenta
    if ($autor_id && $autor_id != $usuario_id) {
        $stmt = $pdo->prepare("
            INSERT INTO notificaciones (usuario_id, tipo, mensaje, publicacion_id, leida)
            VALUES (?, 'comentario', 'Han comentado tu publicación', ?, 0)
        ");
        $stmt->execute([$autor_id, $publicacion_id]);
    }

    header("Location: ../publicacion.php?id=" . $publicacion_id);
    exit;
} else {
    header("Location: ../index.php");
    exit;
}
