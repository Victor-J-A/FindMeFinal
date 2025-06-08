<?php
require_once 'includes/db.php';
session_start();

if (!isset($_SESSION['usuario_id'])) {
    die("Acceso denegado.");
}

$stmt = $pdo->prepare("SELECT correo FROM usuarios WHERE id = ?");
$stmt->execute([$_SESSION['usuario_id']]);
$correo = $stmt->fetchColumn();

if ($correo !== 'root@gmail.com') {
    die("Solo el administrador puede ver contenido denunciado.");
}

$tipo = $_GET['tipo'] ?? null;
$id = $_GET['id'] ?? null;

$tipos_validos = ['publicacion', 'comentario', 'imagen'];

if (!in_array($tipo, $tipos_validos) || !is_numeric($id)) {
    die("Parámetros inválidos.");
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Contenido denunciado</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: "Segoe UI", sans-serif;
            background-color: #f8f9fa;
            margin: 0;
            padding: 0;
        }

        main {
            max-width: 800px;
            margin: 50px auto;
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }

        h2 {
            color: #0d6efd;
            text-align: center;
            margin-bottom: 1.5rem;
        }

        p, small, h4 {
            margin: 0.5rem 0;
        }

        img {
            max-width: 100%;
            border-radius: 6px;
            margin-top: 10px;
        }

        #map {
            height: 300px;
            border-radius: 8px;
            margin-bottom: 1rem;
        }

        .comentario {
            border: 1px solid #ccc;
            padding: 1rem;
            margin-top: 1rem;
            border-radius: 6px;
            background-color: #f1f1f1;
        }

        a {
            display: inline-block;
            margin-top: 1.5rem;
            text-decoration: none;
            color: #0d6efd;
        }
    </style>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
</head>
<body>

<?php include 'templates/header.php'; ?>

<main>
    <h2>Contenido denunciado</h2>

    <?php
    if ($tipo === 'publicacion') {
        $stmt = $pdo->prepare("SELECT p.*, u.correo, u.localidad AS user_localidad FROM publicaciones p JOIN usuarios u ON p.usuario_id = u.id WHERE p.id = ?");
        $stmt->execute([$id]);
        $publicacion = $stmt->fetch();

        if (!$publicacion) {
            echo "<p>Publicación no encontrada.</p>";
        } else {
            echo "<p><strong>Tipo de animal:</strong> " . htmlspecialchars($publicacion['tipo_animal']) . "</p>";
            if (!empty($publicacion['nombre_animal'])) {
                echo "<p><strong>Nombre:</strong> " . htmlspecialchars($publicacion['nombre_animal']) . "</p>";
            }
            echo "<p><strong>Texto:</strong> " . nl2br(htmlspecialchars($publicacion['texto'])) . "</p>";
            echo "<p><strong>Relación con el animal:</strong> " . ($publicacion['origen'] === 'dueno' ? 'Es su mascota' : 'Lo ha encontrado') . "</p>";
            echo "<p><strong>Localidad:</strong> " . htmlspecialchars($publicacion['localidad']) . "</p>";
            echo "<p><strong>Fecha:</strong> " . date('d/m/Y H:i', strtotime($publicacion['fecha_publicacion'])) . "</p>";
            echo "<p><strong>Publicado por:</strong> " . htmlspecialchars($publicacion['correo']) . " (" . htmlspecialchars($publicacion['user_localidad']) . ")</p>";

            $stmt = $pdo->prepare("SELECT 
                SUM(CASE WHEN tipo = 'like' THEN 1 ELSE 0 END) AS likes,
                SUM(CASE WHEN tipo = 'dislike' THEN 1 ELSE 0 END) AS dislikes
                FROM valoraciones WHERE publicacion_id = ?");
            $stmt->execute([$id]);
            $votos = $stmt->fetch();
            echo "<p><strong>👍 Up Vote:</strong> " . ($votos['likes'] ?? 0) . " | <strong>👎 Down Vote:</strong> " . ($votos['dislikes'] ?? 0) . "</p>";

            if ($publicacion['latitud'] && $publicacion['longitud']) {
                echo "<h4>Ubicación en el mapa:</h4>";
                echo "<div id='map'></div>";
                echo "<script>
                    const map = L.map('map').setView([" . $publicacion['latitud'] . ", " . $publicacion['longitud'] . "], 15);
                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        attribution: '&copy; OpenStreetMap contributors'
                    }).addTo(map);
                    L.marker([" . $publicacion['latitud'] . ", " . $publicacion['longitud'] . "]).addTo(map)
                        .bindPopup('Ubicación marcada por el usuario.').openPopup();
                </script>";
            }

            $stmt = $pdo->prepare("SELECT ruta_imagen FROM imagenes WHERE publicacion_id = ? AND denunciado = 0");
            $stmt->execute([$id]);
            $imagenes = $stmt->fetchAll();

            if (!empty($imagenes)) {
                echo "<h4>Imágenes:</h4>";
                foreach ($imagenes as $img) {
                    echo "<img src='" . htmlspecialchars($img['ruta_imagen']) . "' alt='Imagen'>";
                }
            }

            $stmt = $pdo->prepare("SELECT c.*, u.correo FROM comentarios c JOIN usuarios u ON c.usuario_id = u.id WHERE c.publicacion_id = ? AND c.denunciado = 0 AND c.eliminado = 0 ORDER BY fecha_comentario ASC");
            $stmt->execute([$id]);
            $comentarios = $stmt->fetchAll();

            echo "<h4>Comentarios:</h4>";
            if (empty($comentarios)) {
                echo "<p>No hay comentarios disponibles.</p>";
            } else {
                foreach ($comentarios as $com) {
                    echo "<div class='comentario'>";
                    echo "<strong>" . htmlspecialchars($com['correo']) . "</strong><br>";
                    echo "<p>" . nl2br(htmlspecialchars($com['contenido'])) . "</p>";
                    echo "<small>" . date('d/m/Y H:i', strtotime($com['fecha_comentario'])) . "</small>";
                    echo "</div>";
                }
            }
        }

    } elseif ($tipo === 'comentario') {
        $stmt = $pdo->prepare("SELECT * FROM comentarios WHERE id = ?");
        $stmt->execute([$id]);
        $comentario = $stmt->fetch();

        if (!$comentario) {
            echo "<p>Comentario no encontrado.</p>";
        } else {
            echo "<p><strong>Mensaje denunciado:</strong></p><p>" . nl2br(htmlspecialchars($comentario['contenido'])) . "</p>";
            echo "<p><strong>Fecha:</strong> " . htmlspecialchars($comentario['fecha_comentario']) . "</p>";
        }

    } elseif ($tipo === 'imagen') {
        $stmt = $pdo->prepare("SELECT ruta_imagen FROM imagenes WHERE id = ?");
        $stmt->execute([$id]);
        $img = $stmt->fetch();

        if (!$img) {
            echo "<p>Imagen no encontrada.</p>";
        } else {
            
            echo "<img src='" . htmlspecialchars($img['ruta_imagen']) . "' alt='Imagen'>";
        }
    }

    echo "<p><a href='admin_denuncias.php'>← Volver a denuncias</a></p>";
    ?>
</main>

<?php include 'templates/footer.php'; ?>

</body>
</html>
