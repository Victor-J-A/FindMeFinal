<?php
require_once 'includes/db.php';
session_start();

// Verificar si es root
$stmt = $pdo->prepare("SELECT correo FROM usuarios WHERE id = ?");
$stmt->execute([$_SESSION['usuario_id'] ?? 0]);
if ($stmt->fetchColumn() !== 'root@gmail.com') {
    die("Acceso denegado.");
}

// Obtener ID del destinatario
$destinatario_id = $_GET['id'] ?? null;

if (!is_numeric($destinatario_id)) {
    die("ID de usuario inválido.");
}

// Obtener datos del usuario destinatario
$stmt = $pdo->prepare("SELECT correo FROM usuarios WHERE id = ?");
$stmt->execute([$destinatario_id]);
$correo_destinatario = $stmt->fetchColumn();

if (!$correo_destinatario) {
    die("Usuario no encontrado.");
}

// Procesar el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mensaje = trim($_POST['mensaje'] ?? '');

    if (empty($mensaje)) {
        $error = "El mensaje no puede estar vacío.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO mensajes_moderador (destinatario_id, mensaje) VALUES (?, ?)");
        $stmt->execute([$destinatario_id, $mensaje]);
        $enviado = true;
    }
}
?>

<?php include 'templates/header.php'; ?>
<h2>✉️ Enviar mensaje moderador</h2>

<p>Destinatario: <strong><?= htmlspecialchars($correo_destinatario) ?></strong></p>

<?php if (!empty($enviado)): ?>
    <p style="color: green;"> Mensaje enviado correctamente.</p>
    <p><a href="listar_usuarios.php">← Volver a lista de usuarios</a></p>
<?php else: ?>
    <?php if (!empty($error)): ?>
        <p style="color: red;"> <?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form method="POST">
        <label for="mensaje">Mensaje:</label><br>
        <textarea name="mensaje" id="mensaje" rows="5" cols="50" required></textarea><br><br>
        <button type="submit">Enviar mensaje</button>
        <a href="listar_usuarios.php" style="margin-left: 10px;">Cancelar</a>
    </form>
<?php endif; ?>

<?php include 'templates/footer.php'; ?>
