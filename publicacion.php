<?php
require_once 'includes/db.php';
session_start();



$id = $_GET['id'] ?? null;

if (!$id) {
    die("ID de publicación no especificado.");
}

// Si se accede desde una notificación, marcarla como leída
if (isset($_GET['notificacion']) && isset($_SESSION['usuario_id'])) {
    $notif_id = $_GET['notificacion'];
    $stmt = $pdo->prepare("UPDATE notificaciones SET leida = 1 WHERE id = ? AND usuario_id = ?");
    $stmt->execute([$notif_id, $_SESSION['usuario_id']]);
}

// Obtener publicación y autor
$stmt = $pdo->prepare("SELECT p.id AS publicacion_id, p.*, u.id AS usuario_id, u.correo, u.localidad AS user_localidad 
                       FROM publicaciones p 
                       JOIN usuarios u ON p.usuario_id = u.id 
                       WHERE p.id = ?");
$stmt->execute([$id]);
$publicacion = $stmt->fetch();

if (!$publicacion) {
    die("Publicación no encontrada.");
}

// Verificar si está finalizada
$stmt = $pdo->prepare("SELECT 1 FROM publicaciones_finalizadas WHERE publicacion_id = ?");
$stmt->execute([$id]);
$esta_finalizada = $stmt->fetchColumn();
if ($esta_finalizada) {
// Redirigir directamente a la página de publicaciones finalizadas
header("Location: publicaciones_finalizadas.php");
exit;
}


// Control de acceso si fue denunciada
if ($publicacion['denunciado']) {
    if (!isset($_SESSION['usuario_id'])) {
        die("Esta publicación ha sido denunciada y no está disponible.");
    }
    $stmt = $pdo->prepare("SELECT correo FROM usuarios WHERE id = ?");
    $stmt->execute([$_SESSION['usuario_id']]);
    $correo = $stmt->fetchColumn();
    if ($correo !== 'root@gmail.com') {
        die("Esta publicación ha sido denunciada y no está disponible.");
    }
}

// Obtener valoraciones
$stmt = $pdo->prepare("
    SELECT
        SUM(CASE WHEN tipo = 'like' THEN 1 ELSE 0 END) AS likes,
        SUM(CASE WHEN tipo = 'dislike' THEN 1 ELSE 0 END) AS dislikes
    FROM valoraciones
    WHERE publicacion_id = ?");
$stmt->execute([$id]);
$votos = $stmt->fetch();

// Obtener imágenes
$stmt = $pdo->prepare("SELECT id, ruta_imagen, denunciado FROM imagenes WHERE publicacion_id = ?");
$stmt->execute([$id]);
$imagenes = $stmt->fetchAll();

// Obtener comentarios
$stmt = $pdo->prepare("SELECT c.*, u.id AS usuario_id, u.correo 
                       FROM comentarios c 
                       JOIN usuarios u ON c.usuario_id = u.id 
                       WHERE c.publicacion_id = ? 
                       ORDER BY fecha_comentario DESC");
$stmt->execute([$id]);
$comentarios = $stmt->fetchAll();
?>


<?php include 'templates/header.php'; ?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Detalles de la publicación</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

<style>
.contenedor {
    display: flex;
    justify-content: center;
    padding: 1rem;
}

.tarjeta {
    background-color: #fff;
    width: 90%;
    max-width: 1400px;
    padding: 2rem;
    border-radius: 10px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.tarjeta img {
    width: 90%;
    max-width: 850px;
    max-height: 500px;
    display: block;
    margin: 1.5rem auto;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    background-color: #fff;
}

.tarjeta h2,
.tarjeta h3,
.tarjeta h4 {
    color: #0056b3;
    margin-top: 1.5rem;
}

.tarjeta p,
.tarjeta ul,
.tarjeta li {
    color: #333;
    line-height: 1.6;
}

.tarjeta textarea {
    width: 100%;
    padding: 0.5rem;
    font-family: inherit;
    border-radius: 4px;
    border: 1px solid #ccc;
    margin-top: 0.5rem;
}

.tarjeta hr {
    margin: 2rem 0;
    border: none;
    border-top: 1px solid #ccc;
}

.tarjeta a {
    color: #007bff;
    text-decoration: none;
    transition: color 0.2s ease;
}

.tarjeta a:hover {
    color: #0056b3;
   
}

#comentarios-contenedor {
    max-height: 700px;
    overflow-y: auto;
    border: 1px solid #ccc;
    padding: 10px;
    border-radius: 8px;
    margin-bottom: 1.5rem;
}

.contenedor-comentarios {
    max-height: 700px;
    overflow-y: auto;
    border: 1px solid #ccc;
    padding: 10px;
    border-radius: 8px;
    margin-bottom: 1.5rem;
}

.comentario {
    border: 1px solid #ccc;
    padding: 10px;
    margin-bottom: 10px;
    border-radius: 6px;
    background-color: #fafafa;
}

.botones-comentario {
    margin-top: 8px;
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
}

.botones-comentario button {
    font-size: 0.75rem;
    padding: 4px 10px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    transition: filter 0.2s ease;
}

.btn-denunciar {
    background-color: #f8d7da;
    color: #721c24;
    border: 1px solid #f1aeb5;
}
.btn-denunciar:hover {
    background-color: #f5c6cb;
}

.btn-chat {
    background-color: #cce5ff;
    color: #004085;
    border: 1px solid #b8daff;
}
.btn-chat:hover {
    filter: brightness(0.95);
}

.btn-eliminar {
    background-color: #d1d8e0;
    color: #2f3542;
    border: 1px solid #a4b0be;
}
.btn-eliminar:hover {
    filter: brightness(0.95);
}

.btn-finalizar {
    background-color: #0056b3;
    color: #ffffff;
    border: 1px solid #004a9f;
    font-size: 0.85rem;
    padding: 6px 12px;
    border-radius: 4px;
    cursor: pointer;
    transition: background-color 0.2s ease;
}
.btn-finalizar:hover {
    background-color: #00408d;
}

.btn-editar {
    background-color: #0056b3;
    color: white;
    border: 1px solid #004a9f;
    font-size: 0.85rem;
    padding: 6px 12px;
    border-radius: 4px;
    cursor: pointer;
    text-decoration: none;
    transition: background-color 0.2s ease;
    display: inline-block;
}

.btn-editar a{
    color: white;
}
.btn-editar:hover {
    
    background-color: #00408d;
    color: white;
  
}


.btn-comentar {
    background-color: #0056b3;
    color: #ffffff;
    border: 1px solid #004a9f;
    font-size: 0.85rem;
    padding: 6px 12px;
    border-radius: 4px;
    cursor: pointer;
    transition: background-color 0.2s ease;
}
.btn-comentar:hover {
    background-color: #00408d;
}


.mensaje-editada {
    background-color: #f1f1f1;
    color: #000;
    font-weight: bold;
    padding: 6px 12px;
    border-left: 4px solid #000;
    margin: 1rem 0;
    border-radius: 4px;
}

@media (max-width: 450px) {
    .botones-comentario {
        flex-direction: column;
        align-items: center;
    }

    .botones-comentario form,
    .botones-comentario button {
        width: 80% !important;
    }

    .botones-comentario button {
        margin-bottom: 6px;
    }

    .btn-finalizar,
    .btn-denunciar,
    .btn-chat,
    .btn-eliminar,
    .btn-editar,
    .btn-comentar {
        width: 80% !important;
        display: block;
        margin: 0 auto 6px auto;
        text-align: center;
    }

    form[action="includes/valorar.php"] button,
    form[action="denunciar.php"] button {
        width: 80% !important;
        display: block;
        margin: 6px auto;
    }
}
</style>

</head>
<body>
<div class="contenedor">
    <div class="tarjeta">

<a href="index.php" style="display:inline-block; margin-bottom: 15px;">← Volver al inicio</a>


<?php if (!empty($imagenes)): ?>
  
    <?php foreach ($imagenes as $img): ?>
        <?php if ($img['denunciado']) continue; ?>
        <div style="margin-bottom: 15px;">
            <img src="<?= htmlspecialchars($img['ruta_imagen']) ?>">
            <?php if (isset($_SESSION['usuario_id'])): ?>
<form method="GET" action="denunciar.php" style="display:inline;">
    <input type="hidden" name="tipo" value="imagen">
    <input type="hidden" name="id" value="<?= $img['id'] ?>">
    <button type="submit" class="btn-denunciar">🚨 Denunciar Imagen</button>
</form>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php
$noHayImagenValida = true;
if (!empty($imagenes)) {
    foreach ($imagenes as $img) {
        if (!$img['denunciado']) {
            $noHayImagenValida = false;
            break;
        }
    }
}
if ($noHayImagenValida): ?>
   
    <div style="margin-bottom: 15px;">
        <img src="./default.PNG" alt="Imagen por defecto">
    </div>
<?php endif; ?>


<h2>Detalles de la publicación</h2>

<p><strong>Publicado por:</strong>
   <a href="perfil.php?id=<?= $publicacion['usuario_id'] ?>">
      <?= htmlspecialchars($publicacion['correo']) ?>
   </a>
</p>
<p><strong>¿Es el dueño?:</strong>
    <?= $publicacion['origen'] === 'dueno' ? 'Es dueño' : 'Lo ha encontrado' ?>
</p>
<p><strong>Tipo de animal:</strong> <?= htmlspecialchars($publicacion['tipo_animal']) ?></p>
<?php if (!empty($publicacion['nombre_animal'])): ?>
<p><strong>Nombre:</strong> <?= htmlspecialchars($publicacion['nombre_animal']) ?></p>
<?php endif; ?>
<p><strong>Texto:</strong> <?= nl2br(htmlspecialchars($publicacion['texto'])) ?></p>
<p><strong>Localidad:</strong> <?= htmlspecialchars($publicacion['localidad']) ?></p>
<p><strong>Fecha:</strong> <?= date('d/m/Y H:i', strtotime($publicacion['fecha_publicacion'])) ?></p>

<?php if ($publicacion['editada']): ?>
<p class="mensaje-editada">️ Publicación editada</p>
<?php endif; ?>



<?php
$mostrarMapa = (
    isset($publicacion['latitud']) &&
    isset($publicacion['longitud']) &&
    is_numeric($publicacion['latitud']) &&
    is_numeric($publicacion['longitud']) &&
    $publicacion['latitud'] != 0 &&
    $publicacion['longitud'] != 0
);
?>

<?php if ($mostrarMapa): ?>
    <h4>Ubicación en el mapa:</h4>
    <div id="map" style="height: 300px; margin-bottom: 15px;"></div>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        const lat = <?= $publicacion['latitud'] ?>;
        const lng = <?= $publicacion['longitud'] ?>;
        const map = L.map('map').setView([lat, lng], 15);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);
        L.marker([lat, lng]).addTo(map)
            .bindPopup('Ubicación marcada por el usuario.')
            .openPopup();
    </script>
<?php endif; ?>

<?php if (isset($_SESSION['usuario_id']) && $_SESSION['usuario_id'] == $publicacion['usuario_id'] && !$esta_finalizada): ?>
<button onclick="mostrarFormularioFinalizar()" class="btn-finalizar">Finalizar publicación</button>

    <div id="formulario-finalizar" style="display: none; margin-top: 10px;">
        <form method="POST" action="confirmar_terminar.php" onsubmit="return confirmarTerminar();">
            <input type="hidden" name="publicacion_id" value="<?= $publicacion['id'] ?>">
            <label for="mensaje">¿Qué ocurrió con el animal?</label><br>
            <textarea name="mensaje" id="mensaje" rows="3" cols="50" required></textarea><br><br>
            <button type="submit" class="btn-finalizar">Confirmar finalización</button>
        </form>
    </div>

    <script>
        function mostrarFormularioFinalizar() {
            document.getElementById('formulario-finalizar').style.display = 'block';
        }

        function confirmarTerminar() {
            return confirm('¿Estás seguro de que deseas marcar esta publicación como terminada? El cambio será permanente y no se podrá deshacer.');
        }
    </script>
<?php endif; ?>

<p><strong>👍 Up Vote:</strong> <?= $votos['likes'] ?? 0 ?> |
   <strong>👎 Down Vote:</strong> <?= $votos['dislikes'] ?? 0 ?></p>

<?php if (isset($_SESSION['usuario_id'])): ?>
    <form method="POST" action="includes/valorar.php" style="display:inline;">
        <input type="hidden" name="publicacion_id" value="<?= $publicacion['publicacion_id'] ?>">
        <input type="hidden" name="tipo" value="like">
        <button type="submit">👍 Up Vote</button>
    </form>

    <form method="POST" action="includes/valorar.php" style="display:inline;">
        <input type="hidden" name="publicacion_id" value="<?= $publicacion['publicacion_id'] ?>">
        <input type="hidden" name="tipo" value="dislike">
        <button type="submit">👎 Down Vote</button>
    </form>
<?php else: ?>
    <p><a href="login.php">Inicia sesión</a> para valorar esta publicación.</p>
<?php endif; ?>
<?php if (isset($_SESSION['usuario_id']) && $_SESSION['usuario_id'] == $publicacion['usuario_id']): ?>
<p>
    <a href="editar_publicacion.php?id=<?= $publicacion['publicacion_id'] ?>" class="btn-editar" style="color: white">
        Editar publicación
    </a>
</p>
<?php endif; ?>

<?php if (isset($_SESSION['usuario_id'])): ?>
<form method="GET" action="denunciar.php" style="display:inline;">
    <input type="hidden" name="tipo" value="publicacion">
    <input type="hidden" name="id" value="<?= $publicacion['publicacion_id'] ?>">
    <button type="submit" class="btn-denunciar">🚨 Denunciar Publicación</button>
</form>
<?php endif; ?>



<hr>
<h3 id="comentarios">Comentarios</h3>

<div class="contenedor-comentarios">
<?php if (empty($comentarios)): ?>
    <p>No hay comentarios todavía.</p>
<?php else: ?>
    <?php foreach ($comentarios as $com): ?>
        <?php if ($com['denunciado'] || $com['eliminado']) continue; ?>
        <div style="border: 1px solid #ccc; padding: 8px; margin-bottom: 10px;">
            <p><strong><a href="perfil.php?id=<?= $com['usuario_id'] ?>"><?= htmlspecialchars($com['correo']) ?></a>:</strong></p>
            <p><?= nl2br(htmlspecialchars($com['contenido'])) ?></p>
            <p><small><?= date('d/m/Y H:i', strtotime($com['fecha_comentario'])) ?></small></p>


<div class="botones-comentario">
    <?php if (isset($_SESSION['usuario_id'])): ?>
        <form method="GET" action="denunciar.php" style="display:inline;">
            <input type="hidden" name="tipo" value="comentario">
            <input type="hidden" name="id" value="<?= $com['id'] ?>">
            <button type="submit" class="btn-denunciar">🚨 Denunciar Comentario</button>
        </form>
    <?php endif; ?>

    <?php if (
        isset($_SESSION['usuario_id']) &&
        $_SESSION['usuario_id'] == $publicacion['usuario_id'] &&
        $_SESSION['usuario_id'] != $com['usuario_id']
    ): ?>
        <form method="POST" action="includes/crear_chat.php" style="display:inline;">
            <input type="hidden" name="publicacion_id" value="<?= $publicacion['publicacion_id'] ?>">
            <input type="hidden" name="destinatario_id" value="<?= $com['usuario_id'] ?>">
            <button type="submit" class="btn-chat">💬 Iniciar chat</button>
        </form>
    <?php endif; ?>

    <?php if (isset($_SESSION['usuario_id']) && $_SESSION['usuario_id'] == $publicacion['usuario_id']): ?>
        <form method="POST" action="includes/eliminar_comentario.php" style="display:inline;">
            <input type="hidden" name="comentario_id" value="<?= $com['id'] ?>">
            <input type="hidden" name="publicacion_id" value="<?= $publicacion['publicacion_id'] ?>">
            <button type="submit" class="btn-eliminar" onclick="return confirm('¿Seguro que quieres eliminar este comentario?')">🗑️ Eliminar</button>
        </form>
    <?php endif; ?>
</div>
            
            
        </div>
    <?php endforeach; ?>
<?php endif; ?>
</div> 

<?php if (isset($_SESSION['usuario_id'])): ?>
    <hr>
    <h4>Dejar un comentario</h4>
    <form method="POST" action="includes/comentar.php">
        <textarea name="contenido" rows="3" cols="50" required></textarea><br><br>
        <input type="hidden" name="publicacion_id" value="<?= $publicacion['publicacion_id'] ?>">
        <button type="submit">Comentar</button>
    </form>
<?php else: ?>
    <p><a href="login.php">Inicia sesión</a> para comentar.</p>
<?php endif; ?>

    </div>
</div>

<?php include 'templates/footer.php'; ?>

</body>
</html>
