<?php
require_once 'includes/db.php';
session_start();

// Verificar que sea el administrador root
$stmt = $pdo->prepare("SELECT correo FROM usuarios WHERE id = ?");
$stmt->execute([$_SESSION['usuario_id'] ?? 0]);
if ($stmt->fetchColumn() !== 'root@gmail.com') {
    die("Acceso denegado.");
}

// Filtro de búsqueda por correo
$filtro = trim($_GET['correo'] ?? '');

// Separar usuarios por tipo de baneo
$usuarios_activos = [];
$usuarios_baneados_temporal = [];
$usuarios_baneados_permanente = [];

$sql_base = "SELECT * FROM usuarios";
$params = [];
if (!empty($filtro)) {
    $sql_base .= " WHERE correo LIKE ?";
    $params[] = "%$filtro%";
}
$sql_base .= " ORDER BY id ASC";

$stmt = $pdo->prepare($sql_base);
$stmt->execute($params);
$usuarios = $stmt->fetchAll();

$ahora = new DateTime();

foreach ($usuarios as $u) {
    if ($u['baneo_fin'] === null) {
        $usuarios_activos[] = $u;
    } else {
        $hasta = new DateTime($u['baneo_fin']);
        if ($hasta->format('Y-m-d H:i:s') === '9999-12-31 23:59:59') {
            $usuarios_baneados_permanente[] = $u;
        } elseif ($hasta > $ahora) {
            $usuarios_baneados_temporal[] = $u;
        } else {
            $usuarios_activos[] = $u;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Lista de usuarios</title>
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
    padding: 0 0 60px 0;
}

h2, h3 {
    margin-left: 5%;
}

h2 {
    font-size: 24px;
    margin-top: 30px;
    color: var(--color-secundario);
}

h3 {
    font-size: 20px;
    margin-top: 40px;
    color: var(--color-principal);
}

p {
    margin-left: 5%;
}

form {
    width: 90%;
    margin: 20px auto;
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 10px;
}

input[type="text"] {
    padding: 6px 10px;
    font-size: 14px;
    border: 1px solid #ccc;
    border-radius: 4px;
    flex-grow: 1;
}

button {
    background-color: var(--color-principal);
    color: white;
    border: none;
    padding: 7px 14px;
    border-radius: 4px;
    font-weight: bold;
    cursor: pointer;
}

button:hover {
    background-color: var(--color-secundario);
}

a {
    color: var(--color-principal);
    text-decoration: none;
    font-weight: 500;
}

a:hover {
    color: var(--color-secundario);
    text-decoration: underline;
}

.table-wrapper {
    width: 90%;
    margin: 20px auto;
    overflow-x: auto;
}

table {
    width: 100%;
    border-collapse: collapse;
    background: #fff;
    box-shadow: 0 2px 5px rgba(0,0,0,0.08);
    min-width: 600px;
}

th, td {
    padding: 12px;
    border: 1px solid #ddd;
    text-align: left;
    vertical-align: top;
}

th {
    background-color: #f2f2f2;
    font-weight: bold;
}

.acciones {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
}

.acciones a {
    display: inline-block;
}

@media (max-width: 768px) {
    .acciones {
        flex-direction: column;
        gap: 6px;
    }

    .table-wrapper {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }

    form {
        flex-direction: column;
        align-items: flex-start;
    }

    form input,
    form button,
    form a {
        width: 100%;
    }
}
    </style>
</head>
<body>

<?php include 'templates/header.php'; ?>
<h2> Lista de usuarios</h2>

<form method="GET" action="listar_usuarios.php">
    <label for="correo">Filtrar por correo:</label>
    <input type="text" name="correo" id="correo" value="<?= htmlspecialchars($filtro) ?>">
    <button type="submit">Buscar</button>
    <a href="listar_usuarios.php">Reiniciar</a>
</form>

<h3>Usuarios activos</h3>
<?php if (empty($usuarios_activos)): ?>
    <p>No hay usuarios activos.</p>
<?php else: ?>
    <div class="table-wrapper">
        <table>
            <tr>
                <th>ID</th>
                <th>Correo</th>
                <th>Localidad</th>
                <th>Acciones</th>
            </tr>
            <?php foreach ($usuarios_activos as $u): ?>
                <tr>
                    <td><?= $u['id'] ?></td>
                    <td><?= htmlspecialchars($u['correo']) ?></td>
                    <td><?= htmlspecialchars($u['localidad']) ?></td>
                    <td>
                        <div class="acciones">
                            <a href="perfil.php?id=<?= $u['id'] ?>">Ver perfil</a>
                            <a href="enviar_mensaje_moderador.php?id=<?= $u['id'] ?>">Mensaje moderador</a>
                            <a href="includes/banear_usuario.php?id=<?= $u['id'] ?>"
                               onclick="return confirm('¿Seguro que deseas banear al usuario <?= htmlspecialchars($u['correo']) ?>?');">
                               Banear usuario</a>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>
<?php endif; ?>

<h3> Usuarios temporalmente baneados</h3>
<?php if (empty($usuarios_baneados_temporal)): ?>
    <p>No hay usuarios temporalmente baneados.</p>
<?php else: ?>
    <div class="table-wrapper">
        <table>
            <tr>
                <th>ID</th>
                <th>Correo</th>
                <th>Localidad</th>
                <th>Tiempo restante</th>
                <th>Acciones</th>
            </tr>
            <?php foreach ($usuarios_baneados_temporal as $u): ?>
                <?php
                    $hasta = new DateTime($u['baneo_fin']);
                    $intervalo = $ahora->diff($hasta);
                    $restante = $intervalo->format('%ad %hh %im');
                ?>
                <tr>
                    <td><?= $u['id'] ?></td>
                    <td><?= htmlspecialchars($u['correo']) ?></td>
                    <td><?= htmlspecialchars($u['localidad']) ?></td>
                    <td><?= $restante ?></td>
                    <td>
                        <div class="acciones">
                            <a href="perfil.php?id=<?= $u['id'] ?>">Ver perfil</a>
                            <a href="includes/desbanear_usuario.php?id=<?= $u['id'] ?>">Desbanear</a>
                            <a href="editar_baneo.php?id=<?= $u['id'] ?>">Editar baneo</a>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>
<?php endif; ?>

<h3>Usuarios permanentemente baneados</h3>
<?php if (empty($usuarios_baneados_permanente)): ?>
    <p>No hay usuarios permanentemente baneados.</p>
<?php else: ?>
    <div class="table-wrapper">
        <table>
            <tr>
                <th>ID</th>
                <th>Correo</th>
                <th>Localidad</th>
                <th>Acciones</th>
            </tr>
            <?php foreach ($usuarios_baneados_permanente as $u): ?>
                <tr>
                    <td><?= $u['id'] ?></td>
                    <td><?= htmlspecialchars($u['correo']) ?></td>
                    <td><?= htmlspecialchars($u['localidad']) ?></td>
                    <td>
                        <div class="acciones">
                            <a href="perfil.php?id=<?= $u['id'] ?>">Ver perfil</a>
                            <a href="includes/desbanear_usuario.php?id=<?= $u['id'] ?>">Desbanear</a>
                            <a href="editar_baneo.php?id=<?= $u['id'] ?>">Editar baneo</a>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>
<?php endif; ?>

<p><a href="admin_denuncias.php">← Volver a administración</a></p>
<?php include 'templates/footer.php'; ?>
</body>
</html>
