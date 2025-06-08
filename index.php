<?php
session_start();
require_once 'includes/db.php';

$filtro_animal = $_GET['filtro'] ?? null;
$filtro_localidad = $_GET['localidad'] ?? null;

$usuario_localidad = null;
$es_root = false;

if (isset($_SESSION['usuario_id'])) {
    $stmt = $pdo->prepare("SELECT correo, localidad FROM usuarios WHERE id = ?");
    $stmt->execute([$_SESSION['usuario_id']]);
    $usuario_data = $stmt->fetch();
    if ($usuario_data) {
        $correo = $usuario_data['correo'];
        $usuario_localidad = $usuario_data['localidad'];
        if ($correo === 'root@gmail.com') {
            $es_root = true;
        }
    }
}

// Obtener todas las localidades en mayúsculas desde usuarios y publicaciones
$localidades_usuarios = $pdo->query("SELECT DISTINCT localidad FROM usuarios")->fetchAll(PDO::FETCH_COLUMN);
$localidades_publicaciones = $pdo->query("SELECT DISTINCT localidad FROM publicaciones")->fetchAll(PDO::FETCH_COLUMN);
$localidades_combinadas = array_unique(array_merge($localidades_usuarios, $localidades_publicaciones));
$localidades = array_filter($localidades_combinadas, function ($loc) {
    return strtoupper($loc) === $loc;
});
sort($localidades, SORT_STRING | SORT_FLAG_CASE);

$sql = "
    SELECT p.*, 
           u.id AS usuario_id, 
           u.correo, 
           u.localidad AS user_localidad,
           (SELECT ruta_imagen 
            FROM imagenes i 
            WHERE i.publicacion_id = p.id AND i.denunciado = 0 
            ORDER BY i.id ASC LIMIT 1) AS imagen_destacada,
           (SELECT COUNT(*) FROM valoraciones v WHERE v.publicacion_id = p.id AND v.tipo = 'like') AS total_likes,
           (SELECT COUNT(*) FROM valoraciones v WHERE v.publicacion_id = p.id AND v.tipo = 'dislike') AS total_dislikes
    FROM publicaciones p
    JOIN usuarios u ON p.usuario_id = u.id
    WHERE (
        SELECT COUNT(*) 
        FROM denuncias d 
        WHERE d.tipo_contenido = 'publicacion' AND d.id_contenido = p.id
    ) < 1
    AND p.id NOT IN (SELECT publicacion_id FROM publicaciones_finalizadas)
";

$params = [];
if (in_array($filtro_animal, ['perro', 'gato', 'pajaro', 'reptil', 'otro'])) {
    $sql .= " AND p.tipo_animal = ?";
    $params[] = $filtro_animal;
}

if ($filtro_localidad === 'mi_localidad' && $usuario_localidad) {
    $sql .= " ORDER BY (p.localidad = ?) DESC, p.fecha_publicacion DESC";
    $params[] = $usuario_localidad;
} elseif (!empty($filtro_localidad)) {
    $sql .= " AND p.localidad = ? ORDER BY p.fecha_publicacion DESC";
    $params[] = $filtro_localidad;
} else {
    $sql .= " ORDER BY p.fecha_publicacion DESC";
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$publicaciones = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>

<style>

.btn-admin {
    display: inline-block;
    padding: 6px 12px;
    background-color: #ff4d4d;
    color: white;
    text-decoration: none;
    border-radius: 4px;
    font-weight: bold;
    transition: background-color 0.2s ease;
    margin-left: 5%;
}

.btn-admin:hover {
    background-color: #cc0000;
}

.btn-finalizadas {
    display: inline-block;
    padding: 6px 12px;
    background-color: rgb(144, 144, 250);
    color: white;
    text-decoration: none;
    border-radius: 4px;
    font-weight: bold;
    transition: background-color 0.2s ease;
    margin-left: 5%;
}

.btn-finalizadas:hover {
    background-color: rgb(100, 100, 230);
}

h2{
    margin-left:5%;
}

a {
    color: #3366cc;
    text-decoration: none;
    font-weight: 500;
    transition: color 0.2s ease;
}

a:hover {
    color: #224499;
    text-decoration: underline;
}

form {
    margin: 0 0 20px 5%;
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 10px;
}

form label {
    font-weight: bold;
    margin-right: 5px;
}

form select,
form button {
    padding: 6px 12px;
    font-size: 14px;
    border-radius: 4px;
    border: 1px solid #ccc;
    background-color: #f9f9f9;
    transition: all 0.2s ease;
}

form select:focus,
form button:hover {
    background-color: #e6e6e6;
    border-color: #999;
}

form button {
    background-color: #3366cc;
    color: white;
    border: none;
}

form button:hover {
    background-color: #224499;
}

.grid-publicacion {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 20px;
    margin-top: 20px;
    align-items: stretch;
    width: 95%;
    margin-left: 2.5%;
    margin-bottom: 40px;
}

.card-publicacion {
    border: 2px solid #ccc;
    padding: 15px;
    background-color: white;
    border-radius: 8px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    height: 100%;
    min-height: 620px; 
}

.card-publicacion img {
    width: 100%;
    aspect-ratio: 4 / 3;
    object-fit: cover;
    border-radius: 5px;
    margin-bottom: 10px;
    background-color: #f0f0f0;
    display: block;
}

.card-publicacion .mapa {
    height: 200px;
    margin-top: 10px;
}

.btn-detalles {
    display: inline-block;
    padding: 6px 12px;
    background-color: #2a8cff;
    color: white;
    border-radius: 4px;
    text-decoration: none;
    font-weight: bold;
    transition: background-color 0.2s ease;
    margin-top: 10px;
    text-align: center;
}

.btn-detalles:hover {
    background-color: #1a6fd1;
    text-decoration: underline;
}

.texto-limitado {
    display: block;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 100%;
}

@media (min-width: 1024px) {
    .grid-publicacion {
        grid-template-columns: repeat(3, 1fr);
    }
}

@media (min-width: 768px) and (max-width: 1023px) {
    .grid-publicacion {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 810px) {
    .grid-publicacion {
        grid-template-columns: 1fr;
    }

    form {
        flex-direction: column;
        align-items: stretch;
        gap: 12px;
    }

    form label {
        margin-right: 0;
    }

    form select,
    form button {
        width: 90%;
        box-sizing: border-box;
    }

    form button {
        text-align: center;
        font-weight: bold;
    }
}

</style>
<body>
    


<?php include 'templates/header.php'; ?>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<?php if ($es_root): ?>
    <p>
        <a href="admin_denuncias.php" class="btn-admin">
             Administrar denuncias
        </a>
    </p>
<?php endif; ?>

<p>
    <a href="publicaciones_finalizadas.php" class="btn-finalizadas">
         Ver publicaciones finalizadas
    </a>
</p>

<h2 id="filtrotext">Últimas publicaciones</h2> 

<form method="GET" action="index.php">
    <label id="filtrotext" for="filtro">Tipo de animal:</label>
    <select name="filtro" id="filtro">
        <option value="">-- Todos --</option>
        <option value="perro" <?= $filtro_animal === 'perro' ? 'selected' : '' ?>>Perros</option>
        <option value="gato" <?= $filtro_animal === 'gato' ? 'selected' : '' ?>>Gatos</option>
        <option value="pajaro" <?= $filtro_animal === 'pajaro' ? 'selected' : '' ?>>Pájaros</option>
        <option value="reptil" <?= $filtro_animal === 'reptil' ? 'selected' : '' ?>>Reptiles</option>
        <option value="otro" <?= $filtro_animal === 'otro' ? 'selected' : '' ?>>Otros</option>
    </select>

    <label id="filtrotext" for="localidad">Localidad:</label>
    <select name="localidad" id="localidad">
        <option value="">-- Todas --</option>
        <?php if ($usuario_localidad): ?>
            <option value="mi_localidad" <?= $filtro_localidad === 'mi_localidad' ? 'selected' : '' ?>>Mi localidad (<?= htmlspecialchars($usuario_localidad) ?>)</option>
        <?php endif; ?>
        <?php foreach ($localidades as $loc): ?>
            <option value="<?= htmlspecialchars($loc) ?>" <?= $filtro_localidad === $loc ? 'selected' : '' ?>>
                <?= htmlspecialchars($loc) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <button type="submit">Aplicar filtro</button>
<button type="button" onclick="window.location.href='index.php'">Quitar filtros</button>
</form>

<hr>

<?php if (empty($publicaciones)): ?>
    <p>No hay publicaciones aún.</p>
<?php else: ?>
<div class="grid-publicacion">
<?php foreach ($publicaciones as $pub): ?>
<div class="card-publicacion">
<?php if (!empty($pub['imagen_destacada'])): ?>
    <img src="<?= htmlspecialchars($pub['imagen_destacada']) ?>" alt="Imagen">
<?php else: ?>
    <img src="./default.PNG" alt="Imagen por defecto">
<?php endif; ?>

    <p><strong>Tipo de animal:</strong> <?= htmlspecialchars($pub['tipo_animal']) ?></p>
    <?php if (!empty($pub['nombre_animal'])): ?>
        <p><strong>Nombre del animal:</strong> <?= htmlspecialchars($pub['nombre_animal']) ?></p>
    <?php endif; ?>

<?php
$texto_limpio = strip_tags($pub['texto']); // Eliminamos etiquetas HTML
$texto_truncado = mb_strimwidth($texto_limpio, 0, 30, '...');
?>
<p class="texto-limitado"><strong>Texto:</strong> <?= htmlspecialchars($texto_truncado) ?></p>


    <p><strong>Relación con el animal:</strong>
        <?= $pub['origen'] === 'dueno' ? 'Es su mascota' : ($pub['origen'] === 'encontrado' ? 'Lo ha encontrado' : 'No especificado') ?>
    </p>
    <p><strong>Localidad:</strong> <?= htmlspecialchars($pub['localidad']) ?></p>
    <p><strong>Publicado por:</strong>
        <a href="perfil.php?id=<?= $pub['usuario_id'] ?>">
            <?= htmlspecialchars($pub['correo']) ?>
        </a>
    </p>
    <p><strong>👍 Up Vote:</strong> <?= $pub['total_likes'] ?? 0 ?> |
    <strong>👎 Down Vote:</strong> <?= $pub['total_dislikes'] ?? 0 ?></p>

    <p><strong>Fecha:</strong> <?= date('d/m/Y H:i', strtotime($pub['fecha_publicacion'])) ?></p>

    <?php if (is_numeric($pub['latitud']) && is_numeric($pub['longitud']) && $pub['latitud'] != 0 && $pub['longitud'] != 0): ?>
        <div id="map_<?= $pub['id'] ?>" class="mapa"></div>
        <script>
            const map<?= $pub['id'] ?> = L.map('map_<?= $pub['id'] ?>').setView([<?= $pub['latitud'] ?>, <?= $pub['longitud'] ?>], 14);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap contributors'
            }).addTo(map<?= $pub['id'] ?>);
            L.marker([<?= $pub['latitud'] ?>, <?= $pub['longitud'] ?>]).addTo(map<?= $pub['id'] ?>)
                .bindPopup('Ubicación aproximada');
        </script>
    <?php endif; ?>

<a href="publicacion.php?id=<?= $pub['id'] ?>" class="btn-detalles">Ver detalles</a>
</div>
<?php endforeach; ?>
</div> 
<?php endif; ?>

<?php
if (isset($_SESSION['usuario_id'])) {
    require_once 'includes/notificaciones.php';
    actualizarUltimaRevision($_SESSION['usuario_id']);
}
?>

<?php include 'templates/footer.php'; ?>
</body>
</html>
