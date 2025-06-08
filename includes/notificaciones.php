<?php
require_once 'db.php';


function obtenerNuevasPublicacionesLocalidad($usuario_id) {
    global $pdo;

    // Obtener la localidad del usuario
    $stmt = $pdo->prepare("SELECT localidad FROM usuarios WHERE id = ?");
    $stmt->execute([$usuario_id]);
    $localidad = $stmt->fetchColumn();

    // Obtener la última revisión registrada
    $stmt = $pdo->prepare("SELECT ultima_revision FROM notificaciones_locales WHERE usuario_id = ?");
    $stmt->execute([$usuario_id]);
    $ultima = $stmt->fetchColumn();

    if (!$ultima) {
        $now = date('Y-m-d H:i:s');
        $stmt = $pdo->prepare("INSERT INTO notificaciones_locales (usuario_id, ultima_revision) VALUES (?, ?)");
        $stmt->execute([$usuario_id, $now]);
        return 0;
    }

    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM publicaciones 
        WHERE localidad = ? AND fecha_publicacion > ? AND usuario_id != ?
    ");
    $stmt->execute([$localidad, $ultima, $usuario_id]);
    return (int)$stmt->fetchColumn();
}

function actualizarUltimaRevision($usuario_id) {
    global $pdo;
    $stmt = $pdo->prepare("UPDATE notificaciones_locales SET ultima_revision = NOW() WHERE usuario_id = ?");
    $stmt->execute([$usuario_id]);
}
