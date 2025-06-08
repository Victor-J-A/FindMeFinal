<?php
session_start();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Centro de ayuda</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: system-ui, sans-serif;
            background-color: #f9f9f9;
            color: #333;
            margin: 0;
            padding: 1rem;
        }

        main {
            max-width: 800px;
            margin: auto;
            background-color: #fff;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
        }

        h2, h3 {
            color: #0056b3;
        }

        ul {
            padding-left: 1.5rem;
            margin-bottom: 1.5rem;
        }

        li {
            margin-bottom: 0.5rem;
        }

        p {
            margin-bottom: 1rem;
        }
        
.contenido-ayuda {
    overflow: hidden;
    transition: max-height 0.3s ease;
    max-height: 0;
}

.contenido-ayuda.oculto {
    max-height: 0;
}
h3 span {
    font-size: 0.8em;
    color: #555;
}
        
    </style>
</head>
<body>

<?php include 'templates/header.php'; ?>

<main>
    <h2>Centro de ayuda</h2>
    <p>¿Tienes alguna duda? Aquí puedes encontrar información útil para usar esta plataforma correctamente.</p>

    <h3>¿Cómo publicar una mascota perdida o encontrada?</h3>
    <ul>
        <li>Haz clic en “Nueva publicación”.</li>
        <li>Rellena todos los campos requeridos con la mayor precisión posible (tipo de animal, descripción, foto, localidad, etc.).</li>
        <li>Publica. Tu publicación será visible para todo el mundo, sean o no de tu zona.</li>
    </ul>

    <h3>¿Qué hacer si encuentras un animal?</h3>
    <ul>
        <li>Haz una publicación en esta plataforma seleccionando “Lo he encontrado”. Incluye una foto clara y una descripción precisa.</li>
        <li>Llama a un veterinario cercano si el animal está herido. No intentes transportarlo sin consultar primero; podrías dañarlo o ponerte en riesgo.</li>
        <li>Ten precaución con cualquier animal que encuentres. Pueden tener reacciones imprevistas.</li>
        <li>No te acerques a animales grandes ni te pongas en riesgo. Usa la app con sentido común y seguridad.</li>
        <li>No te bajes del coche en carretera ni uses la app conduciendo. Puedes crear una publicación sin estar en el lugar exacto donde viste al animal.</li>
        <li>Además del veterinario, también puedes llamar al <strong>Ayuntamiento</strong> o <strong>Protección Animal</strong> para reportar la situación.</li>
    </ul>

    <h3>¿Cómo denunciar una publicación o usuario?</h3>
    <ul>
        <li>En cada publicación o chat verás un botón de “Denunciar”.</li>
        <li>Solo denúncialo si crees que el contenido es inapropiado, falso, ofensivo o sospechoso.</li>
        <li>Un moderador revisará la denuncia y tomará medidas si es necesario.</li>
    </ul>

    <h3>¿Qué hacer si encuentro repetidamente al mismo animal perdido?</h3>
    <p>Si ves varias veces al mismo animal sin dueño en la calle, sigue estos pasos:</p>
    <ul>
        <li>Haz <strong>una sola publicación</strong> y actualízala si vuelve a aparecer.</li>
        <li>Busca si ya existe una publicación sobre el mismo animal (puedes buscar por localidad o tipo de animal).</li>
        <li>Si el animal parece abandonado o en peligro, contacta con el <strong>Ayuntamiento</strong> o <strong>Protección Animal</strong>.</li>
        <li>Evita llevarlo a tu casa sin consultar primero con autoridades o protectoras.</li>
        <li>Si decides acogerlo temporalmente, indícalo en tu publicación y permanece atento por si aparece el dueño.</li>
    </ul>
    <p><strong>Recuerda:</strong> en España está prohibido apropiarse de un animal encontrado si no se ha realizado el proceso legal de búsqueda del propietario.</p>

    <h3>Uso legítimo de la aplicación</h3>
    <p>Esta aplicación ha sido creada para ayudar desinteresadamente a animales perdidos y a sus dueños. Se ruega un uso legítimo y con buena intención.</p>
    <p><strong>No toleramos malas conductas.</strong> Los usuarios que incumplan pueden ser sancionados temporal o permanentemente.</p>
    <p>Aun cuando se denuncian publicaciones o chats, nuestro equipo conserva copias de seguridad para gestionar adecuadamente cada caso.</p>
    <p>Esta aplicación tiene un sistema de puntuación de post y usuarios. Las publicaciones pueden ser votadas a criterop de los usuarios. <br> El total de estas votaciones se guardarán en el perfil del usuario y servirán para saber si es un usuario confiable.</p>
</main>

<script>
document.addEventListener("DOMContentLoaded", () => {
    const titulos = document.querySelectorAll("main h3");

    titulos.forEach(titulo => {
        // Crear ícono ▼
        const indicador = document.createElement("span");
        indicador.textContent = "▼";
        indicador.style.marginLeft = "0.5em";
        titulo.appendChild(indicador);
        titulo.style.cursor = "pointer";

        // Crear contenedor
        const contenedor = document.createElement("div");
        contenedor.classList.add("contenido-ayuda");
        contenedor.style.overflow = "hidden";
        contenedor.style.transition = "max-height 0.3s ease";

        let siguiente = titulo.nextElementSibling;
        while (siguiente && siguiente.tagName !== "H3") {
            const actual = siguiente;
            siguiente = siguiente.nextElementSibling;
            contenedor.appendChild(actual);
        }
        titulo.after(contenedor);

        // Estado inicial: abierto
        contenedor.style.maxHeight = contenedor.scrollHeight + "px";

        titulo.addEventListener("click", () => {
            const estaOculto = contenedor.style.maxHeight === "0px";

            if (estaOculto) {
                contenedor.style.maxHeight = contenedor.scrollHeight + "px";
                indicador.textContent = "▼";
            } else {
                contenedor.style.maxHeight = "0px";
                indicador.textContent = "▲";
            }
        });
    });
});
</script>



<?php include 'templates/footer.php'; ?>

</body>
</html>
