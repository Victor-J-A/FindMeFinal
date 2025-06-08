<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';

$usuario_id = $_SESSION['usuario_id'];

// Obtener datos actuales
$stmt = $pdo->prepare("SELECT telefono, localidad FROM usuarios WHERE id = ?");
$stmt->execute([$usuario_id]);
$usuario = $stmt->fetch();

$errores = [];
$telefono = $usuario['telefono'];
$localidad = $usuario['localidad'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $telefono = $_POST['telefono'] ?? '';
    $localidad_input = $_POST['localidad'] ?? '';
    $confirmar_localidad = $_POST['confirmar_localidad'] ?? '';

    if (empty($telefono) || empty($localidad_input) || empty($confirmar_localidad)) {
        $errores[] = "Todos los campos son obligatorios.";
    }

    if (!preg_match('/^[0-9]{9}$/', $telefono)) {
        $errores[] = "El número de teléfono debe tener exactamente 9 dígitos.";
    }

    if (!preg_match('/^[a-zA-ZñÑ\s]+$/', $localidad_input)) {
        $errores[] = "La localidad contiene caracteres inválidos (no se permiten acentos, números ni símbolos).";
    }

    if (strcasecmp($localidad_input, $confirmar_localidad) !== 0) {
        $errores[] = "Las localidades no coinciden.";
    }

    if (empty($errores) && $telefono !== $usuario['telefono']) {
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE telefono = ? AND id != ?");
        $stmt->execute([$telefono, $usuario_id]);
        if ($stmt->fetch()) {
            $errores[] = "Ese número de teléfono ya está en uso por otro usuario.";
        }
    }

    if (empty($errores)) {
        $localidad_mayus = strtoupper($localidad_input);
        $stmt = $pdo->prepare("UPDATE usuarios SET telefono = ?, localidad = ? WHERE id = ?");
        $stmt->execute([$telefono, $localidad_mayus, $usuario_id]);
        header("Location: perfil.php?id=" . $usuario_id);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar perfil</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: system-ui, sans-serif;
            background-color: #f9f9f9;
            margin: 0;
            padding: 1rem;
        }

        main {
            max-width: 600px;
            margin: auto;
            background-color: #fff;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
        }

        h2 {
            text-align: center;
            margin-bottom: 1.5rem;
        }

        label {
            display: block;
            margin-top: 1rem;
        }

        input {
            width: 100%;
            padding: 0.5rem;
            margin-top: 0.25rem;
            border: 1px solid #ccc;
            border-radius: 4px;
        }

        button {
            margin-top: 2rem;
            padding: 0.6rem 1.2rem;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            width: 100%;
        }

        button:hover {
            background-color: #0056b3;
        }

        ul {
            color: red;
        }
    </style>
</head>
<body>

<?php include 'templates/header.php'; ?>

<main>
    <h2>Editar perfil</h2>

    <?php if (!empty($errores)): ?>
        <ul>
            <?php foreach ($errores as $error): ?>
                <li><?= htmlspecialchars($error) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <form method="POST" novalidate>
        <label for="telefono">Teléfono:</label>
        <input type="text" name="telefono" id="telefono" value="<?= htmlspecialchars($telefono) ?>" required>

        <label for="localidad">Nombre de tu Ciudad/Pueblo:</label>
        <input type="text" name="localidad" id="localidad" value="<?= htmlspecialchars($localidad) ?>" required>

        <label for="confirmar_localidad">Confirmar Ciudad/Pueblo:</label>
        <input type="text" name="confirmar_localidad" id="confirmar_localidad" required>

        <button type="submit">Guardar cambios</button>
    </form>
</main>

<script>
document.addEventListener("DOMContentLoaded", function() {
    let alertaMostrada = false;
    const campoLocalidad = document.getElementById('localidad');

    campoLocalidad.addEventListener('focusin', function() {
        if (!alertaMostrada) {
            alertaMostrada = true;
            setTimeout(function() {
                alert("Por favor, asegúrate de escribir correctamente el nombre de tu ciudad o pueblo. No se permiten acentos ni símbolos.");
            }, 100);
        }
    });

    function validarCampo(input) {
        const regex = /^[a-zA-ZñÑ\s]+$/;
        if (!regex.test(input.value)) {
            input.setCustomValidity("Solo letras y espacios. No se permiten números ni símbolos.");
        } else {
            input.setCustomValidity("");
        }
    }

    document.getElementById('localidad').addEventListener('input', function() {
        validarCampo(this);
    });

    document.getElementById('confirmar_localidad').addEventListener('input', function() {
        validarCampo(this);
    });

    document.querySelector('form').addEventListener('submit', function(e) {
        const loc1 = document.getElementById('localidad').value.trim();
        const loc2 = document.getElementById('confirmar_localidad').value.trim();
        if (loc1.toLowerCase() !== loc2.toLowerCase()) {
            e.preventDefault();
            alert("Las localidades no coinciden.");
        }
    });
});
</script>

<?php include 'templates/footer.php'; ?>
</body>
</html>
