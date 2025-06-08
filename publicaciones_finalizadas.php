<?php
session_start();
require_once 'includes/db.php';

$filtro_animal = $_GET['filtro'] ?? null;
$filtro_localidad = $_GET['localidad'] ?? null;

$usuario_localidad = null;
if (isset($_SESSION['usuario_id'])) {
    $stmt = $pdo->prepare("SELECT localidad FROM usuarios WHERE id = ?");
    $stmt->execute([$_SESSION['usuario_id']]);
    $usuario = $stmt->fetch();
    $usuario_localidad = $usuario ? $usuario['localidad'] : null;
}

$localidades = $pdo->query("
    SELECT DISTINCT UPPER(p.localidad) 
    FROM publicaciones_finalizadas pf
    JOIN publicaciones p ON pf.publicacion_id = p.id
")->fetchAll(PDO::FETCH_COLUMN);

sort($localidades, SORT_STRING | SORT_FLAG_CASE);

$sql = "
    SELECT pf.*, p.texto, p.tipo_animal, p.localidad, p.fecha_publicacion, u.correo,
           (SELECT ruta_imagen 
            FROM imagenes i 
            WHERE i.publicacion_id = p.id AND i.denunciado = 0 
            ORDER BY i.id ASC LIMIT 1) AS imagen_destacada
    FROM publicaciones_finalizadas pf
    JOIN publicaciones p ON pf.publicacion_id = p.id
    JOIN usuarios u ON p.usuario_id = u.id
    WHERE 1 = 1
";

$params = [];

if (in_array($filtro_animal, ['perro', 'gato', 'pajaro', 'reptil', 'otro'])) {
    $sql .= " AND p.tipo_animal = ?";
    $params[] = $filtro_animal;
}

if ($filtro_localidad === 'mi_localidad' && $usuario_localidad) {
    $sql .= " AND p.localidad = ?";
    $params[] = $usuario_localidad;
} elseif (!empty($filtro_localidad)) {
    $sql .= " AND p.localidad = ?";
    $params[] = $filtro_localidad;
}

$sql .= " ORDER BY pf.fecha_finalizacion DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$publicaciones = $stmt->fetchAll();
?>

<?php include 'templates/header.php'; ?>

<style>
h2 {
    margin-left: 5%;
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
    grid-template-columns: repeat(3, 1fr); /* Escritorio por defecto */
    gap: 20px;
    margin-top: 20px;
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

/* === MEDIA QUERIES ACTUALIZADAS === */

/* Escritorio: 3 columnas */
/* Escritorio: 3 columnas desde 1024px en adelante */
@media (min-width: 1024px) {
    .grid-publicacion {
        grid-template-columns: repeat(3, 1fr);
    }
}

/* Tabletas: de 768px a 1023px → 2 columnas */
@media (min-width: 768px) and (max-width: 1023px) {
    .grid-publicacion {
        grid-template-columns: repeat(2, 1fr);
    }
}

/* Móviles y pantallas pequeñas: hasta 810px → 1 columna + ajustes de formulario */
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

<h2> Publicaciones finalizadas</h2>

<form method="GET" action="publicaciones_finalizadas.php">
    <label for="filtro">Tipo de animal:</label>
    <select name="filtro" id="filtro">
        <option value="">-- Todos --</option>
        <option value="perro" <?= $filtro_animal === 'perro' ? 'selected' : '' ?>>Perros</option>
        <option value="gato" <?= $filtro_animal === 'gato' ? 'selected' : '' ?>>Gatos</option>
        <option value="pajaro" <?= $filtro_animal === 'pajaro' ? 'selected' : '' ?>>Pájaros</option>
        <option value="reptil" <?= $filtro_animal === 'reptil' ? 'selected' : '' ?>>Reptiles</option>
        <option value="otro" <?= $filtro_animal === 'otro' ? 'selected' : '' ?>>Otros</option>
    </select>

    <label for="localidad">Localidad:</label>
    <select name="localidad" id="localidad">
        <option value="">-- Todas --</option>
        <?php if ($usuario_localidad): ?>
            <option value="mi_localidad" <?= $filtro_localidad === 'mi_localidad' ? 'selected' : '' ?>>
                Mi localidad (<?= htmlspecialchars($usuario_localidad) ?>)
            </option>
        <?php endif; ?>
        <?php foreach ($localidades as $loc): ?>
            <option value="<?= htmlspecialchars($loc) ?>" <?= $filtro_localidad === $loc ? 'selected' : '' ?>>
                <?= htmlspecialchars($loc) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <button type="submit">Aplicar filtro</button>
    <button type="button" onclick="window.location.href='publicaciones_finalizadas.php'">Quitar filtros</button>
</form>

<hr>

<?php if (empty($publicaciones)): ?>
    <p style="margin-left:5%;">No hay publicaciones finalizadas con esos filtros.</p>
<?php else: ?>
    <div class="grid-publicacion">
    <?php foreach ($publicaciones as $pub): ?>
        <div class="card-publicacion">
            <?php if ($pub['imagen_destacada']): ?>
                <img src="<?= htmlspecialchars($pub['imagen_destacada']) ?>" alt="Imagen">
            <?php endif; ?>
            <p><strong>Tipo de animal:</strong> <?= htmlspecialchars($pub['tipo_animal']) ?></p>
            <p><strong>Texto:</strong> <?= nl2br(htmlspecialchars($pub['texto'])) ?></p>
            <p><strong>Localidad:</strong> <?= htmlspecialchars($pub['localidad']) ?></p>
            <p><strong>Publicado por:</strong> <?= htmlspecialchars($pub['correo']) ?></p>
            <p><strong>Fecha de publicación:</strong> <?= date('d/m/Y H:i', strtotime($pub['fecha_publicacion'])) ?></p>
            <p><strong>Mensaje de finalización:</strong> <?= nl2br(htmlspecialchars($pub['mensaje'])) ?></p>
            <p><strong>Finalizado el:</strong> <?= date('d/m/Y H:i', strtotime($pub['fecha_finalizacion'])) ?></p>
        </div>
    <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php include 'templates/footer.php'; ?>
