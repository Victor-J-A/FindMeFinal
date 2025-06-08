<?php
session_start();
require_once 'includes/db.php';

$stmt = $pdo->prepare("SELECT correo FROM usuarios WHERE id = ?");
$stmt->execute([$_SESSION['usuario_id'] ?? 0]);
if ($stmt->fetchColumn() !== 'root@gmail.com') {
    die("Acceso denegado.");
}

$id = $_GET['id'] ?? null;
if (!$id) {
    die("ID de usuario no especificado.");
}

$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt->execute([$id]);
$usuario = $stmt->fetch();
if (!$usuario) {
    die("Usuario no encontrado.");
}

$errores = [];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $tipo = $_POST['tipo_baneo'] ?? '';
    $duracion = $_POST['duracion'] ?? '';

    if ($tipo === 'permanente') {
        $nueva_fecha = '9999-12-31 23:59:59';
    } elseif ($tipo === 'temporal' && is_numeric($duracion) && $duracion > 0) {
        $ahora = new DateTime();
        $ahora->modify("+$duracion hours");
        $nueva_fecha = $ahora->format('Y-m-d H:i:s');
    } else {
        $errores[] = "Duración inválida.";
    }

    if (empty($errores)) {
        $stmt = $pdo->prepare("UPDATE usuarios SET baneo_fin = ? WHERE id = ?");
        $stmt->execute([$nueva_fecha, $id]);
        header("Location: listar_usuarios.php");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar baneo</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        :root {
            --color-principal: #3366cc;
            --color-secundario: #224499;
        }

        body {
            font-family: Arial, sans-serif;
            background: #ffffff;
            color: #333;
            margin: 0;
            padding: 20px;
        }

        .tarjeta {
            width: 90%;
            max-width: 600px;
            margin: 30px auto;
            border: 2px solid #ccc;
            border-radius: 8px;
            padding: 20px;
            background-color: #fdfdfd;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }

        h2 {
            color: var(--color-secundario);
            text-align: center;
            margin-bottom: 20px;
        }

        form label {
            display: block;
            margin-bottom: 10px;
        }

        input[type="radio"],
        input[type="number"] {
            margin-right: 10px;
        }

        input[type="number"],
        button {
            padding: 6px 10px;
            font-size: 16px;
            margin-bottom: 12px;
            width: 100%;
            box-sizing: border-box;
        }

        button {
            background-color: var(--color-principal);
            color: white;
            border: none;
            border-radius: 4px;
            font-weight: bold;
            cursor: pointer;
        }

        button:hover {
            background-color: var(--color-secundario);
        }

        a {
            display: inline-block;
            margin-top: 10px;
            color: var(--color-principal);
            text-decoration: none;
        }

        a:hover {
            text-decoration: underline;
        }

        ul {
            padding-left: 20px;
            color: red;
        }

        @media (max-width: 768px) {
            .tarjeta {
                width: 95%;
                padding: 15px;
            }
        }
        @media (max-width: 300px) {
    h2 {
        font-size: 20px;
        text-align: center;
        word-break: break-word;
    }
}

    </style>
</head>
<body>

<?php include 'templates/header.php'; ?>

<div class="tarjeta">
    <h2>Editar baneo para <?= htmlspecialchars($usuario['correo']) ?></h2>

    <?php if (!empty($errores)): ?>
        <ul>
            <?php foreach ($errores as $error): ?>
                <li><?= htmlspecialchars($error) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <form method="POST">
        <label><input type="radio" name="tipo_baneo" value="temporal" checked> Temporal</label>
        <label><input type="radio" name="tipo_baneo" value="permanente"> Permanente</label>

        <div id="duracion_container">
            <label for="duracion">Duración en horas:</label>
            <input type="number" name="duracion" id="duracion" min="1" value="24">
        </div>

        <button type="submit">Guardar cambios</button>
        <a href="listar_usuarios.php">← Cancelar</a>
    </form>
</div>

<script>
    const radios = document.querySelectorAll('input[name="tipo_baneo"]');
    const duracionContainer = document.getElementById('duracion_container');

    function actualizarVisibilidad() {
        const seleccionado = document.querySelector('input[name="tipo_baneo"]:checked').value;
        duracionContainer.style.display = seleccionado === 'temporal' ? 'block' : 'none';
    }

    radios.forEach(r => r.addEventListener('change', actualizarVisibilidad));
    window.onload = actualizarVisibilidad;
</script>

<?php include 'templates/footer.php'; ?>

</body>
</html>
