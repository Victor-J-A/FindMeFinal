<?php
session_start();
require_once 'includes/db.php';

$errores = [];

$correo = $_POST['correo'] ?? '';
$telefono = $_POST['telefono'] ?? '';
$localidad_original = $_POST['localidad'] ?? '';
$confirmar_localidad = $_POST['confirmar_localidad'] ?? '';
$localidad = strtoupper($localidad_original);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $contraseña = $_POST["contraseña"] ?? '';
    $confirmar = $_POST["confirmar"] ?? '';

    if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $errores[] = "Correo electrónico inválido.";
    }
    if (empty($telefono) || empty($localidad_original) || empty($confirmar_localidad)) {
        $errores[] = "Todos los campos son obligatorios.";
    }
    if (!preg_match('/^[0-9]{9}$/', $telefono)) {
        $errores[] = "El número de teléfono debe contener exactamente 9 dígitos numéricos.";
    }
    if (!preg_match('/^[a-zA-ZñÑ\s]+$/', $localidad_original)) {
        $errores[] = "La localidad contiene caracteres inválidos (no se permiten acentos, números ni símbolos).";
    }
    if (strcasecmp($localidad_original, $confirmar_localidad) !== 0) {
        $errores[] = "Las localidades no coinciden.";
    }
    if ($contraseña !== $confirmar) {
        $errores[] = "Las contraseñas no coinciden.";
    }
    if (strlen($contraseña) < 6) {
        $errores[] = "La contraseña debe tener al menos 6 caracteres.";
    }

    if (empty($errores)) {
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE correo = ?");
        $stmt->execute([$correo]);
        if ($stmt->fetch()) {
            $errores[] = "El correo ya está registrado.";
        }

        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE telefono = ?");
        $stmt->execute([$telefono]);
        if ($stmt->fetch()) {
            $errores[] = "El número de teléfono ya está en uso por otro usuario.";
        }
    }

    if (empty($errores)) {
        $hash = password_hash($contraseña, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO usuarios (correo, telefono, localidad, contraseña) VALUES (?, ?, ?, ?)");
        $stmt->execute([$correo, $telefono, $localidad, $hash]);

        $_SESSION["usuario_id"] = $pdo->lastInsertId();
        header("Location: index.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registro de usuario</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: "Segoe UI", sans-serif;
            background-color: #f0f4f8;
            color: #000;
            margin: 0;
            padding: 0;
        }

        main {
            max-width: 700px;
            margin: 60px auto;
            padding: 2rem;
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        h2 {
            text-align: center;
            margin-bottom: 1.5rem;
            color: #0056b3;
        }

        form {
            display: flex;
            flex-direction: column;
        }

        label {
            margin-bottom: 0.3rem;
            font-weight: 600;
        }

        input[type="text"],
        input[type="email"],
        input[type="password"] {
            padding: 0.6rem;
            font-size: 1rem;
            margin-bottom: 1.2rem;
            border: 1px solid #ccc;
            border-radius: 4px;
        }

        button[type="submit"] {
            background-color: #0056b3;
            color: white;
            padding: 0.7rem;
            border: none;
            border-radius: 4px;
            font-size: 1rem;
            cursor: pointer;
        }

        button[type="submit"]:hover {
            background-color: #003f8a;
        }

        ul {
            color: red;
            list-style: disc;
            margin-left: 1.5rem;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>

<?php include 'templates/header.php'; ?>

<main>
    <h2>Registro de usuario</h2>

    <?php if (!empty($errores)): ?>
        <ul>
            <?php foreach ($errores as $error): ?>
                <li><?= htmlspecialchars($error) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <form method="POST" action="" onsubmit="return validarFormulario();">
        <label>Correo electrónico:</label>
        <input type="email" name="correo" value="<?= htmlspecialchars($correo) ?>" required>

        <label>Teléfono:</label>
        <input type="text" name="telefono" value="<?= htmlspecialchars($telefono) ?>" required>

        <label>Nombre de tu Ciudad/Pueblo:</label>
        <input type="text" name="localidad" id="localidad" value="<?= htmlspecialchars($localidad_original) ?>" required oninput="validarLocalidad(this)">

        <label>Confirmar Ciudad/Pueblo:</label>
        <input type="text" name="confirmar_localidad" id="confirmar_localidad" required oninput="validarLocalidad(this)">

        <label>Contraseña:</label>
        <input type="password" name="contraseña" required>

        <label>Confirmar contraseña:</label>
        <input type="password" name="confirmar" required>

        <button type="submit">Registrarse</button>
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

    campoLocalidad.addEventListener('input', function() {
        const regex = /^[a-zA-ZñÑ\s]+$/;
        if (!regex.test(campoLocalidad.value)) {
            campoLocalidad.setCustomValidity("Solo letras y espacios. No se permiten números ni símbolos.");
        } else {
            campoLocalidad.setCustomValidity("");
        }
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
