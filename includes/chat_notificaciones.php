<?php
require_once 'db.php';

function obtenerMensajesNoLeidos($usuario_id) {
    global $pdo;

    // Contar notificaciones tipo 'chat' no leídas
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM notificaciones 
        WHERE usuario_id = ? 
          AND tipo = 'chat' 
          AND leida = 0
    ");
    $stmt->execute([$usuario_id]);

    return (int) $stmt->fetchColumn();
}
