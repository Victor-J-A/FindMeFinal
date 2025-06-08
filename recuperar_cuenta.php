<?php
require_once 'includes/db.php';
session_start();

$correo = $_POST['correo'] ?? '';
$telefono = $_POST['telefono'] ?? '';
$nueva_pass = $_POST['nueva_pass'] ?? '';
$repetir_pass = $_POST['repetir_pass'] ?? '';
$paso2 = false;
$mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['comprobar'])) {
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE correo = ? AND telefono = ?");
        $stmt->execute([$correo, $telefono]);
        $usuario = $stmt->fetch();

        if ($usuario) {
            $_SESSION['recuperar_id'] = $usuario['id'];
            $paso2 = true;
        } else {
            $mensaje = "Correo o número de teléfono erróneo.";
        }
    }

    if (isset($_POST['cambiar']) && isset($_SESSION['recuperar_id'])) {
        if ($nueva_pass !== $repetir_pass) {
            $mensaje = "Las contraseñas no coinciden.";
        } elseif (strlen($nueva_pass) < 8) {
            $mensaje = "La contraseña debe tener al menos 8 caracteres.";
        } else {
            $hash = password_hash($nueva_pass, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE usuarios SET contraseña = ? WHERE id = ?");
            $stmt->execute([$hash, $_SESSION['recuperar_id']]);

            unset($_SESSION['recuperar_id']);
            $mensaje = "Contraseña actualizada correctamente. Puedes iniciar sesión.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Recuperar cuenta</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
    body {
        font-family: "Segoe UI", sans-serif;
        background-color: #f8f9fa;
        margin: 0;
        padding: 0;
    }

    main {
        max-width: 500px;
        margin: 60px auto;
        background: white;
        padding: 2rem;
        border-radius: 8px;
        box-shadow: 0 4px 10px rgba(0,0,0,0.1);
    }

    h2 {
        text-align: center;
        margin-bottom: 1.5rem;
        color: #0d6efd;
    }

    form {
        display: flex;
        flex-direction: column;
    }

    label {
        margin-bottom: 0.3rem;
        font-weight: 600;
    }

    input[type="email"],
    input[type="text"],
    input[type="password"] {
        padding: 0.6rem;
        font-size: 1rem;
        margin-bottom: 1.2rem;
        border: 1px solid #ccc;
        border-radius: 4px;
    }

    button[type="submit"] {
        background-color: #0d6efd;
        color: white;
        padding: 0.7rem;
        font-size: 1rem;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        transition: background-color 0.2s ease;
        margin-bottom: 1rem;
    }

    button[type="submit"]:hover {
        background-color: #0a58ca;
    }

    .mensaje {
        margin-bottom: 1rem;
        font-weight: bold;
    }

    .mensaje.red {
        color: red;
    }

    .mensaje.green {
        color: green;
    }

    a {
        display: inline-block;
        text-align: center;
        color: #0d6efd;
        text-decoration: none;
        font-weight: 500;
        margin-top: 0.5rem;
    }

    a:hover {
        text-decoration: underline;
        color: #0a58ca;
    }
</style>
</head>
<body>

<?php include 'templates/header.php'; ?>

<main>
    <h2>Recuperar cuenta</h2>

    <?php if (!empty($mensaje)): ?>
        <p class="mensaje <?= str_contains($mensaje, 'correctamente') ? 'green' : 'red' ?>">
            <?= htmlspecialchars($mensaje) ?>
        </p>
    <?php endif; ?>

    <?php if (!$paso2 && !isset($_SESSION['recuperar_id'])): ?>
        <form method="POST">
            <label for="correo">Correo electrónico:</label>
            <input type="email" name="correo" id="correo" required>

            <label for="telefono">Teléfono:</label>
            <input type="text" name="telefono" id="telefono" required>

            <button type="submit" name="comprobar">Recuperar</button>
            
             <a href="login.php">Volver a Iniciar Sesión</a>
        </form>
    <?php else: ?>
        <form method="POST">
            <label for="nueva_pass">Nueva contraseña:</label>
            <input type="password" name="nueva_pass" id="nueva_pass" required>

            <label for="repetir_pass">Repetir nueva contraseña:</label>
            <input type="password" name="repetir_pass" id="repetir_pass" required>

            <button type="submit" name="cambiar">Aceptar</button>
        </form>
    <?php endif; ?>
</main>

<?php include 'templates/footer.php'; ?>

</body>
</html>
