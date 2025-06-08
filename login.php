<?php
session_start();
require_once 'includes/db.php';

$errores = [];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $correo = trim($_POST["correo"]);
    $contraseña = $_POST["contraseña"];

    if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $errores[] = "Correo electrónico inválido.";
    }

    if (empty($errores)) {
        $stmt = $pdo->prepare("SELECT id, contraseña, baneo_fin FROM usuarios WHERE correo = ?");
        $stmt->execute([$correo]);
        $usuario = $stmt->fetch();

        if ($usuario) {
            // Verificar si el usuario está baneado actualmente
            if (!is_null($usuario["baneo_fin"]) && strtotime($usuario["baneo_fin"]) > time()) {
                $fecha = date("d/m/Y H:i", strtotime($usuario["baneo_fin"]));
                echo "<script>alert('Tu cuenta está temporalmente suspendida hasta el $fecha. No puedes iniciar sesión por el momento.');</script>";
            } elseif (password_verify($contraseña, $usuario["contraseña"])) {
                $_SESSION["usuario_id"] = $usuario["id"];
                header("Location: index.php");
                exit;
            } else {
                $errores[] = "Correo o contraseña incorrectos.";
            }
        } else {
            $errores[] = "Correo o contraseña incorrectos.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Iniciar sesión</title>
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
            max-width: 600px;
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
            margin-bottom: 0.5rem;
            font-weight: 600;
        }

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

        p {
            text-align: center;
            margin-top: 1.2rem;
        }

        a {
            color: #0056b3;
            text-decoration: none;
        }

        a:hover {
            text-decoration: underline;
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
    <h2>Iniciar sesión</h2>

    <?php if (!empty($errores)): ?>
        <ul>
            <?php foreach ($errores as $error): ?>
                <li><?= htmlspecialchars($error) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <form method="POST" action="">
        <label for="correo">Correo electrónico:</label>
        <input type="email" id="correo" name="correo" required>

        <label for="contraseña">Contraseña:</label>
        <input type="password" id="contraseña" name="contraseña" required>

        <button type="submit">Iniciar sesión</button>
    </form>

    <p>¿No tienes cuenta? <a href="register.php">Regístrate aquí</a></p>
    <p>¿Has olvidado tu contraseña? <a href="recuperar_cuenta.php">Recupera tu cuenta</a></p>
</main>

<?php include 'templates/footer.php'; ?>

</body>
</html>
