CREATE database FindMedb;
use FindMedb;

CREATE TABLE usuarios (
    id INT(11) NOT NULL AUTO_INCREMENT,
    correo VARCHAR(100),
    telefono VARCHAR(20),
    localidad VARCHAR(100),
    contraseña VARCHAR(255),
    fecha_registro TIMESTAMP DEFAULT current_timestamp,
    likes_recibidos INT(11) DEFAULT 0,
    dislikes_recibidos INT(11) DEFAULT 0,
    comentarios_eliminados INT(11) DEFAULT 0,
    baneos INT(11) DEFAULT 0,
    baneo_fin DATETIME DEFAULT NULL,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE publicaciones (
    id INT(11) NOT NULL AUTO_INCREMENT,
    usuario_id INT(11) NOT NULL,
    texto TEXT NOT NULL,
    localidad VARCHAR(100) NOT NULL,
    tipo_animal ENUM('perro', 'gato', 'pajaro', 'reptil', 'otro') NOT NULL,
    fecha_publicacion TIMESTAMP NOT NULL DEFAULT current_timestamp(),
    denunciado TINYINT(1) NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    KEY usuario_id (usuario_id),
    CONSTRAINT fk_publicaciones_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE publicaciones ADD COLUMN origen VARCHAR(20);
ALTER TABLE publicaciones ADD COLUMN nombre_animal VARCHAR(100);
ALTER TABLE publicaciones ADD COLUMN latitud DECIMAL(10, 8) DEFAULT NULL;
ALTER TABLE publicaciones ADD COLUMN longitud DECIMAL(11, 8) DEFAULT NULL;
ALTER TABLE publicaciones ADD COLUMN editada BOOLEAN NOT NULL DEFAULT 0;


CREATE TABLE imagenes (
    id INT(11) NOT NULL AUTO_INCREMENT,
    publicacion_id INT(11) NOT NULL,
    ruta_imagen VARCHAR(255) NOT NULL,
    denunciado TINYINT(1) NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    KEY publicacion_id (publicacion_id),
    CONSTRAINT fk_imagenes_publicacion FOREIGN KEY (publicacion_id) REFERENCES publicaciones(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE comentarios (
    id INT(11) NOT NULL AUTO_INCREMENT,
    publicacion_id INT(11) NOT NULL,
    usuario_id INT(11) NOT NULL,
    contenido TEXT NOT NULL,
    fecha_comentario TIMESTAMP NOT NULL DEFAULT current_timestamp(),
    eliminado TINYINT(1) NOT NULL DEFAULT 0,
    denunciado TINYINT(1) NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    KEY publicacion_id (publicacion_id),
    KEY usuario_id (usuario_id),
    CONSTRAINT fk_comentarios_publicacion FOREIGN KEY (publicacion_id) REFERENCES publicaciones(id) ON DELETE CASCADE,
    CONSTRAINT fk_comentarios_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE chats (
    id INT(11) NOT NULL AUTO_INCREMENT,
    publicacion_id INT(11) NOT NULL,
    creador_id INT(11) NOT NULL,
    participante_id INT(11) NOT NULL,
    fecha_creacion TIMESTAMP NOT NULL DEFAULT current_timestamp(),
    PRIMARY KEY (id),
    KEY fk_chat_publicacion (publicacion_id),
    KEY fk_chat_creador (creador_id),
    KEY fk_chat_participante (participante_id),
    CONSTRAINT fk_chat_publicacion FOREIGN KEY (publicacion_id) REFERENCES publicaciones(id) ON DELETE CASCADE,
    CONSTRAINT fk_chat_creador FOREIGN KEY (creador_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    CONSTRAINT fk_chat_participante FOREIGN KEY (participante_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE mensajes_chat (
    id INT(11) NOT NULL AUTO_INCREMENT,
    chat_id INT(11) NOT NULL,
    emisor_id INT(11) NOT NULL,
    receptor ENUM('creador', 'participante') NOT NULL,
    mensaje TEXT NOT NULL,
    leido TINYINT(1) NOT NULL DEFAULT 0,
    fecha_envio TIMESTAMP NOT NULL DEFAULT current_timestamp(),
    PRIMARY KEY (id),
    KEY fk_mensaje_chat (chat_id),
    KEY fk_mensaje_emisor (emisor_id),
    CONSTRAINT fk_mensaje_chat FOREIGN KEY (chat_id) REFERENCES chats(id) ON DELETE CASCADE,
    CONSTRAINT fk_mensaje_emisor FOREIGN KEY (emisor_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE denuncias (
    id INT(11) NOT NULL AUTO_INCREMENT,
    usuario_id INT(11) DEFAULT NULL, 
    tipo_contenido ENUM('publicacion', 'comentario', 'imagen') NOT NULL,
    id_contenido INT(11) NOT NULL,
    fecha TIMESTAMP NOT NULL DEFAULT current_timestamp(),
    usuario_denunciado_id INT(11) DEFAULT NULL, 
    PRIMARY KEY (id),
    KEY fk_denuncia_usuario (usuario_id),
    KEY fk_denuncia_contenido (id_contenido),
    KEY fk_denuncia_denunciado (usuario_denunciado_id),
    CONSTRAINT fk_denuncia_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL,
    CONSTRAINT fk_denuncia_denunciado FOREIGN KEY (usuario_denunciado_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE denuncias_usuarios (
    id INT(11) NOT NULL AUTO_INCREMENT,
    denunciante_id INT(11) NOT NULL,
    denunciado_id INT(11) NOT NULL,
    fecha TIMESTAMP NOT NULL DEFAULT current_timestamp(),
    PRIMARY KEY (id),
    UNIQUE KEY unica_denuncia (denunciante_id, denunciado_id),
    KEY fk_denuncia_usuario_denunciante (denunciante_id),
    KEY fk_denuncia_usuario_denunciado (denunciado_id),
    CONSTRAINT fk_denuncia_usuario_denunciante FOREIGN KEY (denunciante_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    CONSTRAINT fk_denuncia_usuario_denunciado FOREIGN KEY (denunciado_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE mensajes_moderador (
    id INT AUTO_INCREMENT PRIMARY KEY,
    destinatario_id INT NOT NULL,
    mensaje TEXT NOT NULL,
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_envio TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    leido TINYINT(1) DEFAULT 0,
    FOREIGN KEY (destinatario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE publicaciones_finalizadas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    publicacion_id INT NOT NULL,
    mensaje TEXT NOT NULL,
    fecha_finalizacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (publicacion_id) REFERENCES publicaciones(id) ON DELETE CASCADE
);

CREATE TABLE notificaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    mensaje TEXT NOT NULL,
    fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    publicacion_id INT DEFAULT NULL,
    leida BOOLEAN DEFAULT 0,

    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (publicacion_id) REFERENCES publicaciones(id) ON DELETE CASCADE
);
ALTER TABLE notificaciones ADD COLUMN tipo VARCHAR(20) DEFAULT 'publicacion' AFTER usuario_id;
ALTER TABLE notificaciones ADD COLUMN chat_id INT DEFAULT NULL;


CREATE TABLE notificaciones_locales (
    usuario_id INT NOT NULL PRIMARY KEY,
    ultima_revision DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
);

CREATE TABLE valoraciones (
    usuario_id INT NOT NULL,
    publicacion_id INT NOT NULL,
    tipo ENUM('like', 'dislike') NOT NULL,
    PRIMARY KEY (usuario_id, publicacion_id),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (publicacion_id) REFERENCES publicaciones(id) ON DELETE CASCADE
);
