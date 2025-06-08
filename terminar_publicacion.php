<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';

$usuario_id = $_SESSION['usuario_id'];
$publicacion_id = $_GET['id'] ?? null;

if (!$publicacion_id) {
    die("ID de publicación no especificado.");
}

// Verificar que el usuario sea el dueño
$stmt = $pdo->prepare("SELECT * FROM publicaciones WHERE id = ? AND usuario_id = ?");
$stmt->execute([$publicacion_id, $usuario_id]);
$publicacion = $stmt->fetch();

if (!$publicacion) {
    die("No tienes permiso para modificar esta publicación.");
}
?>

<?php include 'templates/header.php'; ?>

<h2>Finalizar publicación</h2>

<p><strong>Indica brevemente qué ha ocurrido. Este mensaje será visible públicamente.</strong></p>

<form method="POST" action="finalizar_publicacion.php" onsubmit="return confirmarMensaje();">
    <input type="hidden" name="id" value="<?= $publicacion_id ?>">
    <textarea name="mensaje" rows="5" cols="60" required placeholder="Ejemplo: Mi perro ha sido encontrado gracias a la plataforma. ¡Gracias a todos!"></textarea><br><br>
    <button type="submit">Confirmar finalización</button>
    <a href="publicacion.php?id=<?= $publicacion_id ?>">Cancelar</a>
</form>

<script>
function confirmarMensaje() {
    return confirm('Este mensaje no se podrá editar ni restaurar. ¿Deseas continuar?');
}
</script>

<?php include 'templates/footer.php'; ?>
