<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/notificaciones.php';
require_once __DIR__ . '/../includes/chat_notificaciones.php';

$nueva_alerta = false;
$likes = 0;
$dislikes = 0;
$avatar_color = 'neutro';
$copa = '';
$correo_usuario = '';

if (isset($_SESSION['usuario_id'])) {
    $usuario_id = $_SESSION['usuario_id'];

    $nuevas_publicaciones = obtenerNuevasPublicacionesLocalidad($usuario_id);
    $nuevos_mensajes = obtenerMensajesNoLeidos($usuario_id);

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM notificaciones WHERE usuario_id = ? AND leida = 0 AND tipo = 'comentario'");
    $stmt->execute([$usuario_id]);
    $nuevos_comentarios = (int) $stmt->fetchColumn();

    $nueva_alerta = ($nuevas_publicaciones > 0 || $nuevos_mensajes > 0 || $nuevos_comentarios > 0) && empty($_SESSION['notificaciones_vistas']);

    $stmt = $pdo->prepare("SELECT correo FROM usuarios WHERE id = ?");
    $stmt->execute([$usuario_id]);
    $correo_usuario = $stmt->fetchColumn();

    $stmt = $pdo->prepare("
        SELECT 
            SUM(CASE WHEN tipo = 'like' THEN 1 ELSE 0 END) AS likes,
            SUM(CASE WHEN tipo = 'dislike' THEN 1 ELSE 0 END) AS dislikes
        FROM valoraciones v
        JOIN publicaciones p ON v.publicacion_id = p.id
        WHERE p.usuario_id = ?
    ");
    $stmt->execute([$usuario_id]);
    $resumen = $stmt->fetch();
    $likes = (int) $resumen['likes'];
    $dislikes = (int) $resumen['dislikes'];

    if ($likes > $dislikes) {
        $avatar_color = 'verde';
    } elseif ($dislikes > $likes) {
        $avatar_color = 'rojo';
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>FindMe - Mascotas perdidas</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
* {
    box-sizing: border-box;
}

body {
    font-family: Arial, sans-serif;
    margin: 0;
    padding: 0;
}

.encabezado {
    width: 100%;
}

.barra-navegacion {
    background-color: rgb(255, 255, 255);
    padding: 10px 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
}

.contenedor-logo {
    width: 25%;
}

#logo {
    width: 100%;
    max-height: 110px;
    object-fit: contain;
    display: block;
}

.enlaces-navegacion {
    display: flex;
    width: 50%;
    justify-content: space-around;
}

.enlaces-navegacion a {
    text-decoration: none;
    color:rgb(18, 18, 82);
    font-weight: bold;
}

.enlaces-navegacion a:hover {
    color: rgb(34, 34, 230);
    text-decoration: underline;
}



.enlaces-navegacion a.notificacion {
    color: red !important;
}


.informacion-usuario {
    display: flex;
    align-items: center;
    gap: 10px;
    position: relative;
    cursor: pointer;
    width: 25%;
    justify-content: flex-end;
    

}

.fila-usuario {
    display: flex;
    align-items: center;
    gap: 10px;
    justify-content: center;
    

}

.correo-usuario {
    font-weight: normal;
    color: #000;
    white-space: nowrap;
    font-weight: bold;
    color:rgb(18, 18, 82);
}

.avatar {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    font-size: 16px;
    color: white;
    font-weight: bold;
    display: flex;
    align-items: center;
    justify-content: center;
}

.verde { background-color: #4CAF50; }
.rojo { background-color: #e74c3c; }
.neutro { background-color: #999; }

/* Menú oculto por defecto */
#menu-usuario {
    display: none;
    position: absolute;
    top: 45px;
    right: 0;
    background: white;
    border: 1px solid #ccc;
    box-shadow: 0px 2px 6px rgba(0, 0, 0, 0.2);
    z-index: 1000;
    min-width: 180px;
}

#menu-usuario.visible {
    display: block;
}

#menu-usuario a {
    display: block;
    padding: 10px 15px;
    text-decoration: none;
    color: #333;
}

#menu-usuario a:hover {
    background-color: #f0f0f0;
}

@media (max-width: 1000px) {
    .barra-navegacion {
        flex-wrap: wrap;
        flex-direction: row;
        align-items: center;
        justify-content: space-between;
        padding: 10px;
    }

    .contenedor-logo {
        width: 100%;
        display: flex;
        justify-content: center;
        margin-bottom: 10px;
    }

    #logo {
        width: 100%;
        height: auto;
        max-height: 120px;
        object-fit: contain;
    }

    .enlaces-navegacion {
        width: 70%;
        justify-content: space-around;
        display: flex;
        flex-wrap: wrap;

     

    }

    .informacion-usuario {
        width: 30%;
        display: flex;
        justify-content: center;
        
    }
}

/* Ajustes específicos para pantallas hasta 650px */
@media (max-width: 700px) {
    .barra-navegacion {
        flex-direction: column;
        align-items: center;
    }

    .contenedor-logo {
        width: 100%;
        display: flex;
        justify-content: center;
        margin-bottom: 10px;
    }

    #logo {
        width: 100%;
        height: auto;
        max-height: 120px;
        object-fit: contain;
    }

    .enlaces-navegacion {
        width: 100%;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 10px;
        margin-bottom: 10px;
        font-size:18px;
    }

    .informacion-usuario {
        flex-direction: column;
        align-items: center;
        justify-content: center;
        margin-bottom: 10px;
        position: relative;
        width: 100%;
        font-size:16px;
    }

    .fila-usuario {
        justify-content: center;
    }

    #menu-usuario {
        position: static;
        width: 100%;
        margin-top: 10px;
        box-shadow: none;
        border: 1px solid #ccc;
        text-align: center;
    }

    #menu-usuario.visible {
        display: block;
    }

    #menu-usuario a {
        text-align: center;
        padding: 12px;
        border-top: 1px solid #eee;
    }
}
</style>
</head>
<body>

<div class="encabezado">
  <nav class="barra-navegacion">
    <div class="contenedor-logo">
      <img src="./templates/logo.PNG" alt="Logo FindMe" id="logo">
    </div>

    <div class="enlaces-navegacion">
      <a href="index.php">Inicio</a>
      <?php if (isset($_SESSION['usuario_id'])): ?>
          <a href="nueva_publicacion.php">Nueva publicación</a>
          <a href="notificaciones.php" class="<?= $nueva_alerta ? 'notificacion' : '' ?>">Notificaciones</a>
          <?php if ($correo_usuario === 'root@gmail.com'): ?>
              <a href="listar_usuarios.php">Listar usuarios</a>
          <?php endif; ?>
      <?php else: ?>
          <a href="login.php">Iniciar sesión</a>
          <a href="register.php">Registrarse</a>
      <?php endif; ?>
    </div>

    <?php if (!empty($correo_usuario)): ?>
    <div class="informacion-usuario" onclick="toggleMenu()">
      <div class="fila-usuario">
        <div class="avatar <?= $avatar_color ?>">
          <?= $copa ?: strtoupper(substr($correo_usuario, 0, 1)) ?>
        </div>
        <span class="correo-usuario"><?= htmlspecialchars($correo_usuario) ?> ▼</span>
      </div>
      <div id="menu-usuario">
        <a href="perfil.php?id=<?= $usuario_id ?>">Mi perfil</a>
        <a href="mis_publicaciones.php">Publicaciones activas</a>
        <a href="mis_chats.php">Mis chats</a>
        <a href="editar_perfil.php">Editar perfil</a>
        <a href="ayuda.php">Ayuda</a>
        <a href="logout.php">Cerrar sesión</a>
      </div>
    </div>
    <?php endif; ?>
  </nav>
</div>


<hr>

<script>
function toggleMenu() {
    const menu = document.getElementById('menu-usuario');
    menu.classList.toggle('visible');
}

document.addEventListener('click', function(event) {
    const menu = document.getElementById('menu-usuario');
    const usuarioInfo = document.querySelector('.informacion-usuario');
    if (menu && !usuarioInfo.contains(event.target)) {
        menu.classList.remove('visible');
    }
});
</script>

