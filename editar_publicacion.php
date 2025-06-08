<?php
require_once 'includes/db.php';
session_start();

$id = $_GET['id'] ?? null;

if (!$id || !isset($_SESSION['usuario_id'])) {
    die("Acceso denegado.");
}

$stmt = $pdo->prepare("SELECT * FROM publicaciones WHERE id = ? AND usuario_id = ?");
$stmt->execute([$id, $_SESSION['usuario_id']]);
$publicacion = $stmt->fetch();

if (!$publicacion) {
    die("No tienes permiso para editar esta publicación.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tipo_animal = $_POST['tipo_animal'] ?? '';
    $origen = $_POST['origen'] ?? '';
    $nombre = $_POST['nombre_animal'] ?? null;
    $texto = trim($_POST['texto']);
    $localidad = strtoupper(trim($_POST['localidad'] ?? ''));
    $lat = $_POST['latitud'] ?? null;
    $lng = $_POST['longitud'] ?? null;

    if (empty($tipo_animal) || empty($origen) || empty($texto) || empty($localidad)) {
        die("Faltan campos obligatorios.");
    }

    if (empty($lat) || empty($lng)) {
        $lat = $publicacion['latitud'];
        $lng = $publicacion['longitud'];
    }

    $stmt = $pdo->prepare("UPDATE publicaciones 
        SET tipo_animal = ?, origen = ?, nombre_animal = ?, texto = ?, localidad = ?, latitud = ?, longitud = ?, fecha_publicacion = NOW(), editada = 1 
        WHERE id = ?");
    $stmt->execute([$tipo_animal, $origen, $nombre, $texto, $localidad, $lat, $lng, $id]);

    $pdo->prepare("DELETE FROM valoraciones WHERE publicacion_id = ?")->execute([$id]);

    if (!empty($_FILES['nueva_imagen']['name'])) {
        $stmt = $pdo->prepare("SELECT id, ruta_imagen FROM imagenes WHERE publicacion_id = ? LIMIT 1");
        $stmt->execute([$id]);
        $imagen_anterior = $stmt->fetch();

        if ($imagen_anterior && file_exists($imagen_anterior['ruta_imagen'])) {
            unlink($imagen_anterior['ruta_imagen']);
            $stmt = $pdo->prepare("DELETE FROM imagenes WHERE id = ?");
            $stmt->execute([$imagen_anterior['id']]);
        }

        $ruta_temporal = $_FILES['nueva_imagen']['tmp_name'];
        $nombre_archivo = basename($_FILES['nueva_imagen']['name']);
        $destino = "uploads/" . uniqid() . "_" . $nombre_archivo;

        if (move_uploaded_file($ruta_temporal, $destino)) {
            $stmt = $pdo->prepare("INSERT INTO imagenes (publicacion_id, ruta_imagen) VALUES (?, ?)");
            $stmt->execute([$id, $destino]);
        }
    }

    header("Location: publicacion.php?id=$id");
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar publicación</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
</head>
<style>
  
main {
    max-width: 700px;
    margin: 40px auto;
    background-color: white;
    padding: 30px;
    border-radius: 12px;
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
}


main h2 {
    text-align: center;
    color: #2a3f5f;
    margin-bottom: 20px;
}


form label {
    font-weight: bold;
    display: block;
    margin-bottom: 5px;
    color: #333;
}

form input[type="text"],
form input[type="file"],
form textarea,
form select {
    width: 100%;
    padding: 8px 10px;
    margin-bottom: 15px;
    border: 1px solid #ccc;
    border-radius: 6px;
    font-size: 14px;
}


form input[type="radio"] {
    margin-right: 5px;
}

form label[for="es_mascota"],
form label[for="lo_encontre"] {
    margin-right: 15px;
}


form button {
    background-color: #007bff;
    color: white;
    border: none;
    padding: 10px 18px;
    border-radius: 6px;
    cursor: pointer;
    font-size: 15px;
    margin-top: 10px;
    transition: background-color 0.3s ease;
}

form button:hover {
    background-color: #0056b3;
}

.radio-opciones-vertical {
    display: flex;
    flex-direction: column;
    gap: 10px;
    margin-top: 8px;
}

.radio-linea {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 16px;
    color: #333;
}



#mapa {
    margin-top: 10px;
    border: 2px solid #ccc;
    border-radius: 6px;
}


@media (max-width: 768px) {
    main {
        margin: 20px;
        padding: 20px;
    }
}

</style>
<body>

<?php include 'templates/header.php'; ?>

<main>
    <h2>Editar publicación</h2>

    <form method="POST" enctype="multipart/form-data" id="form_editar">
        <label for="tipo_animal">Tipo de animal:</label>
        <select name="tipo_animal" required>
            <option value="">-- Selecciona --</option>
            <?php
            $tipos = ['perro', 'gato', 'pajaro', 'reptil', 'otro'];
            foreach ($tipos as $tipo) {
                $sel = $publicacion['tipo_animal'] === $tipo ? 'selected' : '';
                echo "<option value='$tipo' $sel>" . ucfirst($tipo) . "</option>";
            }
            ?>
        </select><br><br>

<label>¿Es tu mascota o lo has encontrado?</label><br>
<div class="radio-opciones-vertical">
    <label class="radio-linea">
        Es mi mascota
        <input type="radio" name="origen" value="dueno" id="es_mascota" <?= $publicacion['origen'] === 'dueno' ? 'checked' : '' ?>>
    </label>
    <label class="radio-linea">
         Lo he encontrado
        <input type="radio" name="origen" value="encontrado" id="lo_encontre" <?= $publicacion['origen'] === 'encontrado' ? 'checked' : '' ?>>
       
    </label>
</div><br>


        <div id="nombre_animal_container" style="display: none;">
            <label>Nombre del animal:</label>
            <input type="text" name="nombre_animal" value="<?= htmlspecialchars($publicacion['nombre_animal']) ?>"><br><br>
        </div>

        <label>Texto descriptivo:</label><br>
        <textarea name="texto" rows="5" cols="50" required><?= htmlspecialchars($publicacion['texto']) ?></textarea><br><br>

        <label>Localidad:</label><br>
        <input type="text" name="localidad" id="localidad" value="<?= htmlspecialchars($publicacion['localidad']) ?>" required><br><br>

        <label>Confirmar localidad:</label><br>
        <input type="text" id="confirmar_localidad" required><br><br>

        <input type="hidden" name="latitud" id="latitud" value="<?= htmlspecialchars($publicacion['latitud']) ?>">
        <input type="hidden" name="longitud" id="longitud" value="<?= htmlspecialchars($publicacion['longitud']) ?>">

        <label>Cambiar imagen (opcional):</label><br>
        <input type="file" name="nueva_imagen" id="nueva_imagen" accept="image/*"><br><br>
        <div id="preview_imagen" style="margin-bottom: 1rem;"></div>

        <button type="button" onclick="iniciarMapa()">Elegir ubicación en el mapa (opcional)</button>
        <div id="mapa" style="height: 300px; margin-top: 10px; display: none;"></div><br>

        <button type="button" onclick="validarYEnviar()">Guardar cambios</button>
    </form>
</main>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const origenInputs = document.querySelectorAll('input[name="origen"]');
    const contenedorNombre = document.getElementById('nombre_animal_container');

    function toggleNombre() {
        contenedorNombre.style.display = document.getElementById('es_mascota').checked ? 'block' : 'none';
    }

    origenInputs.forEach(input => input.addEventListener('change', toggleNombre));
    toggleNombre();
});

function validarYEnviar() {
    const loc1 = document.getElementById('localidad');
    const loc2 = document.getElementById('confirmar_localidad');
    const val1 = loc1.value.trim().toUpperCase();
    const val2 = loc2.value.trim().toUpperCase();

    if (!val1 || !val2) {
        alert("Debes rellenar ambos campos de localidad.");
        return;
    }

    if (val1 !== val2) {
        alert("Las localidades no coinciden.");
        return;
    }

    loc1.value = val1;

    if (document.getElementById('mapa').style.display === 'none') {
        document.getElementById('latitud').value = '';
        document.getElementById('longitud').value = '';
    }

    document.getElementById('form_editar').submit();
}

function iniciarMapa() {
    if (!confirm("¿Deseas compartir la ubicación para marcarla en el mapa?")) return;

    const mapaDiv = document.getElementById('mapa');
    mapaDiv.style.display = 'block';

    const latInput = document.getElementById('latitud');
    const lngInput = document.getElementById('longitud');

    let map;
    let marker;

    function crearMapa(lat, lng) {
        map = L.map('mapa').setView([lat, lng], 13);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);

        marker = L.marker([lat, lng]).addTo(map);
        latInput.value = lat;
        lngInput.value = lng;

        map.on('click', function (e) {
            if (marker) map.removeLayer(marker);
            marker = L.marker(e.latlng).addTo(map);
            latInput.value = e.latlng.lat;
            lngInput.value = e.latlng.lng;
        });
    }

    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            function (position) {
                crearMapa(position.coords.latitude, position.coords.longitude);
            },
            function () {
                alert("No se pudo obtener tu ubicación. Se usará una ubicación por defecto.");
                crearMapa(40.4168, -3.7038); // Madrid
            }
        );
    } else {
        alert("Tu navegador no soporta geolocalización. Se usará una ubicación por defecto.");
        crearMapa(40.4168, -3.7038);
    }
}
</script>
<script>
document.getElementById('nueva_imagen').addEventListener('change', function (event) {
    const contenedor = document.getElementById('preview_imagen');
    contenedor.innerHTML = ''; 

    const archivos = event.target.files;

    if (archivos.length > 1) {
        alert("Solo puedes seleccionar una imagen.");
        event.target.value = ""; 
        return;
    }

    const archivo = archivos[0];
    if (archivo && archivo.type.startsWith('image/')) {
        const reader = new FileReader();
        reader.onload = function (e) {
            const img = document.createElement('img');
            img.src = e.target.result;
            img.alt = "Vista previa de imagen";
            img.style.maxWidth = '300px';
            img.style.marginTop = '10px';
            img.style.borderRadius = '8px';
            img.style.boxShadow = '0 2px 6px rgba(0,0,0,0.2)';
            contenedor.appendChild(img);
        };
        reader.readAsDataURL(archivo);
    }
});
</script>


<?php include 'templates/footer.php'; ?>

</body>
</html>
