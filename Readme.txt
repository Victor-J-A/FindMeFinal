El usuario con derechos de administrador tiene como correo root@gmail.com.

Cualquier otro usuario no podrá administrar las denuncias de la pagina ni entrar en listar usuarios.

Si quieres añadir al usuario root con una consulta puedes usar el siguiente SQL.


INSERT INTO usuarios (
    correo, telefono, localidad, contraseña,
    likes_recibidos, dislikes_recibidos, comentarios_enviados, baneos, baneo_fin
) VALUES (
    'root@gmail.com', '', '', SHA2('rootroot', 256),
    0, 0, 0, 0, NULL
);


La carpeta uploads es necesaria en la carpeta raiz del proyecto para que funcione el almacenado de imagenes.

La imagen default.png es necesaria el la carpeta raiz del proyecto para que se muestre correctamente en las publicaciones sin imagen.