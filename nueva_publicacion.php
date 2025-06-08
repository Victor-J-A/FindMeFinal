<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';

$errores = [];

function limpiar_localidad($valor) {
    $valor = strtoupper(trim($valor));
    if (preg_match('/^[A-ZÑ ]+$/u', $valor)) {
        return $valor;
    }
    return false;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $texto = trim($_POST['texto'] ?? '');
    $tipo_animal = $_POST['tipo_animal'] ?? '';
    $es_dueno = $_POST['origen_animal'] ?? '';
    $nombre_animal = trim($_POST['nombre_animal'] ?? '');
    $latitud = isset($_POST['latitud']) ? floatval($_POST['latitud']) : null;
    $longitud = isset($_POST['longitud']) ? floatval($_POST['longitud']) : null;
    $usar_ubicacion = isset($_POST['usar_ubicacion']) && $_POST['usar_ubicacion'] === '1';
    $localidad_manual = $_POST['localidad_tipo'] ?? 'propia';

    $usuario_id = $_SESSION['usuario_id'];

    if ($localidad_manual === 'otra') {
        $nueva1 = limpiar_localidad($_POST['localidad1'] ?? '');
        $nueva2 = limpiar_localidad($_POST['localidad2'] ?? '');
        if (!$nueva1 || !$nueva2 || $nueva1 !== $nueva2) {
            $errores[] = "La localidad escrita no es válida o no coincide.";
        } else {
            $localidad = $nueva1;
        }
    } else {
        $stmt = $pdo->prepare("SELECT localidad FROM usuarios WHERE id = ?");
        $stmt->execute([$usuario_id]);
        $localidad = $stmt->fetchColumn();
    }

    if (empty($texto)) {
        $errores[] = "El texto descriptivo es obligatorio.";
    }
    if (!in_array($tipo_animal, ['perro', 'gato', 'pajaro', 'reptil', 'otro'])) {
        $errores[] = "Debes seleccionar un tipo de animal válido.";
    }
    if (!in_array($es_dueno, ['dueno', 'encontrado'])) {
        $errores[] = "Debes indicar si eres el dueño o lo has encontrado.";
    }
    if ($es_dueno === 'dueno' && empty($nombre_animal)) {
        $errores[] = "Debes indicar el nombre del animal si eres su dueño.";
    }
    if ($usar_ubicacion && ($latitud === null || $longitud === null)) {
        $errores[] = "No se pudo obtener tu ubicación.";
    }

    if (empty($errores)) {
        $stmt = $pdo->prepare("INSERT INTO publicaciones 
            (usuario_id, texto, localidad, tipo_animal, origen, nombre_animal, latitud, longitud) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $usuario_id,
            $texto,
            $localidad,
            $tipo_animal,
            $es_dueno,
            $nombre_animal ?: null,
            $usar_ubicacion ? $latitud : null,
            $usar_ubicacion ? $longitud : null
        ]);
        $publicacion_id = $pdo->lastInsertId();

        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE localidad = ? AND id != ?");
        $stmt->execute([$localidad, $usuario_id]);
        $otros_usuarios = $stmt->fetchAll(PDO::FETCH_COLUMN);
        foreach ($otros_usuarios as $otro_id) {
            $insertNotif = $pdo->prepare("
                INSERT INTO notificaciones (usuario_id, tipo, mensaje, publicacion_id, leida)
                VALUES (?, 'publicacion', 'Nueva publicación cerca de ti', ?, 0)
            ");
            $insertNotif->execute([$otro_id, $publicacion_id]);
        }

if (!empty($_FILES['imagen_unica']['name'])) {
    $permitidos = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $max_size = 10 * 1024 * 1024;

    $tmp = $_FILES['imagen_unica']['tmp_name'];
    $tipo = mime_content_type($tmp);
    $size = $_FILES['imagen_unica']['size'];
    $nombre = $_FILES['imagen_unica']['name'];

    if ($size > $max_size || !in_array($tipo, $permitidos)) {
        $errores[] = "La imagen no es válida o es demasiado grande.";
    } else {
        $destino = 'uploads/' . uniqid() . '_' . basename($nombre);
        move_uploaded_file($tmp, $destino);
        $stmt = $pdo->prepare("INSERT INTO imagenes (publicacion_id, ruta_imagen) VALUES (?, ?)");
        $stmt->execute([$publicacion_id, $destino]);
    }
}

        if (empty($errores)) {
            header("Location: index.php");
            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Crear nueva publicación</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <style>
        :root {
            --azul-principal: #007bff;
            --azul-hover: #0056b3;
            --gris-fondo: #f5f7fa;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--gris-fondo);
            color: #000;
        }

        main {
            width: 90%;
            max-width: 1000px;
            margin: 40px auto;
            padding: 1.5rem;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 123, 255, 0.08);
        }

        h1 {
            text-align: center;
            margin-bottom: 1.5rem;
            font-size: 1.8rem;
            color: #000;
        }

        h2 {
            font-size: 1.2rem;
            margin: 1.2rem 0 0.5rem;
            color: #000;
        }

        form {
            display: flex;
            flex-direction: column;
            gap: 0.6rem;
        }

        label {
            font-weight: 500;
            margin: 0.2rem 0;
            display: block;
            color: #000;
        }

        input[type="text"],
        input[type="file"],
        select,
        textarea {
            width: 100%;
            padding: 0.5rem;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 1rem;
            margin-bottom: 0.4rem;
        }

        textarea {
            resize: vertical;
        }

        input[type="radio"],
        input[type="checkbox"] {
            margin-right: 6px;
        }

        small {
            color: #444;
            font-size: 0.85rem;
            margin-top: -0.3rem;
        }

        #map {
            width: 100%;
            height: 280px;
            border: 1px solid #ccc;
            border-radius: 6px;
            margin-top: 0.3rem;
        }

        button[type="submit"] {
            background-color: var(--azul-principal);
            color: white;
            border: none;
            padding: 0.6rem 1.1rem;
            font-size: 1rem;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.2s ease;
            margin-top: 1rem;
            align-self: flex-start;
        }

        button[type="submit"]:hover {
            background-color: var(--azul-hover);
        }

        ul {
            list-style-type: disc;
            color: red;
            margin-left: 1.5rem;
            margin-bottom: 1rem;
        }

        @media (max-width: 480px) {
            main {
                margin: 20px 10px;
                padding: 1.2rem;
            }

            h1 {
                font-size: 1.4rem;
            }

            button[type="submit"] {
                width: 100%;
            }
        }
    </style>
</head>
<body>

<?php include 'templates/header.php'; ?>

<main>
    <h1>Crear nueva publicación</h1>

    <?php if (!empty($errores)): ?>
        <ul>
            <?php foreach ($errores as $error): ?>
                <li><?= htmlspecialchars($error) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <h2>Localidad</h2>
        <label><input type="radio" name="localidad_tipo" value="propia" <?= ($_POST['localidad_tipo'] ?? 'propia') === 'propia' ? 'checked' : '' ?>> En mi localidad</label>
        <label><input type="radio" name="localidad_tipo" value="otra" <?= ($_POST['localidad_tipo'] ?? '') === 'otra' ? 'checked' : '' ?>> En otra localidad</label>

        <div id="localidad_nueva_container" style="display: none;">
            <label>Escribe la ciudad o el pueblo donde está situada la publicación:</label>
            <input type="text" name="localidad1" value="<?= htmlspecialchars($_POST['localidad1'] ?? '') ?>">
            <label>Vuelve a escribir la ciudad o pueblo:</label>
            <input type="text" name="localidad2" value="<?= htmlspecialchars($_POST['localidad2'] ?? '') ?>">
        </div>

        <h2>Texto descriptivo</h2>
        <textarea name="texto" rows="4" required><?= htmlspecialchars($_POST['texto'] ?? '') ?></textarea>

        <h2>Tipo de animal</h2>
        <select name="tipo_animal" required>
            <option value="">-- Selecciona uno --</option>
            <?php
            $opciones = ['perro' => 'Perro', 'gato' => 'Gato', 'pajaro' => 'Pájaro', 'reptil' => 'Reptil', 'otro' => 'Otro'];
            foreach ($opciones as $valor => $texto):
            ?>
                <option value="<?= $valor ?>" <?= ($_POST['tipo_animal'] ?? '') === $valor ? 'selected' : '' ?>><?= $texto ?></option>
            <?php endforeach; ?>
        </select>

        <h2>Origen del animal</h2>
        <label><input type="radio" name="origen_animal" value="dueno" <?= ($_POST['origen_animal'] ?? '') === 'dueno' ? 'checked' : '' ?>> Es mi mascota</label>
        <label><input type="radio" name="origen_animal" value="encontrado" <?= ($_POST['origen_animal'] ?? '') === 'encontrado' ? 'checked' : '' ?>> Lo he encontrado</label>

        <div id="nombre_mascota_container" style="display: none;">
            <label>Nombre del animal:</label>
            <input type="text" name="nombre_animal" value="<?= htmlspecialchars($_POST['nombre_animal'] ?? '') ?>">
        </div>

        <h2>Subir imágenes (opcional)</h2>
<input type="file" name="imagen_unica" id="imagen_unica" accept="image/*">

        <small>Tamaño máximo por imagen: 10 MB</small>
<div id="vista_previa_imagen" style="margin-top: 0.5rem;"></div>


        <h2>Ubicación (opcional)</h2>
        <div id="map"></div>
        <input type="hidden" name="latitud" id="latitud">
        <input type="hidden" name="longitud" id="longitud">

        <div id="ubicacion_confirm_container" style="display: none;">
            <label><input type="checkbox" name="usar_ubicacion" value="1" id="usar_ubicacion"> Confirmo que deseo compartir esta ubicación en el mapa</label>
        </div>

        <button type="submit">Publicar</button>
    </form>
</main>

<script>
    const radioDueno = document.querySelector('input[value="dueno"]');
    const radioEncontrado = document.querySelector('input[value="encontrado"]');
    const nombreContainer = document.getElementById('nombre_mascota_container');

    const radioPropia = document.querySelector('input[value="propia"]');
    const radioOtra = document.querySelector('input[value="otra"]');
    const localidadNueva = document.getElementById('localidad_nueva_container');

    function actualizarNombreAnimal() {
        nombreContainer.style.display = radioDueno?.checked ? 'block' : 'none';
    }

    function actualizarLocalidad() {
        localidadNueva.style.display = radioOtra?.checked ? 'block' : 'none';
    }

    [radioDueno, radioEncontrado].forEach(r => r?.addEventListener('change', actualizarNombreAnimal));
    [radioPropia, radioOtra].forEach(r => r?.addEventListener('change', actualizarLocalidad));

    window.onload = () => {
        actualizarNombreAnimal();
        actualizarLocalidad();
    };
</script>

<script>
    let map, marker;
    const latInput = document.getElementById('latitud');
    const lngInput = document.getElementById('longitud');
    const confirmarUbicacion = document.getElementById('ubicacion_confirm_container');

    navigator.geolocation.getCurrentPosition(function(position) {
        const lat = position.coords.latitude;
        const lng = position.coords.longitude;

        map = L.map('map').setView([lat, lng], 14);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap'
        }).addTo(map);

        marker = L.marker([lat, lng], { draggable: true }).addTo(map);
        latInput.value = lat;
        lngInput.value = lng;
        confirmarUbicacion.style.display = 'block';

        marker.on('dragend', function(e) {
            const pos = marker.getLatLng();
            latInput.value = pos.lat.toFixed(8);
            lngInput.value = pos.lng.toFixed(8);
        });
    }, function(error) {
        document.getElementById('map').innerHTML = "No se pudo acceder a tu ubicación.";
    });
</script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const formulario = document.querySelector('form');
const inputImagenes = document.querySelector('input[name="imagen_unica"]');

    formulario.addEventListener('submit', function (e) {
        if (inputImagenes.files.length > 1) {
            e.preventDefault();
            alert("Solo puedes subir una imagen.");
        }
    });
});
</script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const inputImagen = document.getElementById('imagen_unica');
    const vistaPrevia = document.getElementById('vista_previa_imagen');

    inputImagen.addEventListener('change', function () {
        vistaPrevia.innerHTML = ''; // Limpiar vista previa anterior

        const archivo = this.files[0];
        if (archivo && archivo.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = function (e) {
                const img = document.createElement('img');
                img.src = e.target.result;
                img.alt = "Vista previa";
                img.style.maxWidth = "100%";
                img.style.border = "1px solid #ccc";
                img.style.borderRadius = "6px";
                img.style.marginTop = "0.5rem";
                vistaPrevia.appendChild(img);
            };
            reader.readAsDataURL(archivo);
        }
    });
});
</script>


<?php include 'templates/footer.php'; ?>
</body>
</html>
