<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    die("Debes iniciar sesión para denunciar contenido.");
}

$type = $_GET['tipo'] ?? null;
$id = $_GET['id'] ?? null;

$tipos_validos = ['publicacion', 'comentario', 'imagen'];

if (!in_array($type, $tipos_validos) || !is_numeric($id)) {
    die("Parámetros inválidos.");
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Confirmar denuncia</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
    #confirmar-denuncia {
        font-family: "Segoe UI", sans-serif;
        background-color: #f8f9fa;
        padding: 0;
        margin: 0;
    }

    #confirmar-denuncia main {
        max-width: 600px;
        margin: 60px auto;
        background-color: white;
        padding: 2rem;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        text-align: center;
    }

    #confirmar-denuncia h2 {
        color: #dc3545;
        margin-bottom: 1rem;
    }

    #confirmar-denuncia p {
        font-size: 1.1rem;
        margin-bottom: 2rem;
    }

    #confirmar-denuncia form {
        display: flex;
        justify-content: center;
        gap: 1rem;
        flex-wrap: wrap;
    }

    #confirmar-denuncia button,
    #confirmar-denuncia a.boton-cancelar {
        padding: 0.7rem 1.4rem;
        border: none;
        border-radius: 5px;
        font-size: 1rem;
        cursor: pointer;
        text-decoration: none;
        color: white;
        transition: background-color 0.2s ease-in-out;
    }

    #confirmar-denuncia button {
        background-color: #dc3545;
    }

    #confirmar-denuncia button:hover {
        background-color: #bb2d3b;
    }

    #confirmar-denuncia a.boton-cancelar {
        background-color: #6c757d;
    }

    #confirmar-denuncia a.boton-cancelar:hover {
        background-color: #5a6268;
    }
    </style>
</head>
<body id="confirmar-denuncia">

<?php include 'templates/header.php'; ?>

<main>
    <h2>Confirmar denuncia</h2>
    <p>¿Estás seguro de que deseas denunciar este <strong><?= htmlspecialchars($type) ?></strong>?</p>

    <form method="POST" action="includes/procesar_denuncia.php">
        <input type="hidden" name="tipo" value="<?= htmlspecialchars($type) ?>">
        <input type="hidden" name="id" value="<?= intval($id) ?>">
        <button type="submit">Sí, denunciar</button>
        <a href="index.php" class="boton-cancelar">Cancelar</a>
    </form>
</main>

<?php include 'templates/footer.php'; ?>

</body>
</html>
