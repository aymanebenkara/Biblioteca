-- ============================================
-- SISTEMA DE PRÉSTAMO DE LIBROS
-- Script de creación de base de datos
-- ============================================

-- Crear la base de datos
CREATE DATABASE IF NOT EXISTS biblioteca CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE biblioteca;

-- ============================================
-- TABLA: usuarios
-- Almacena la información de los usuarios registrados
-- ============================================
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    apellidos VARCHAR(150) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    es_admin BOOLEAN DEFAULT FALSE,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLA: libros
-- Almacena los libros dados de alta por los usuarios
-- ============================================
-- NOTA EDUCATIVA: Esta tabla guarda la información de cada libro
-- que los usuarios registran en el sistema para prestar a otros
CREATE TABLE IF NOT EXISTS libros (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(255) NOT NULL,
    autor VARCHAR(200) NOT NULL,
    genero VARCHAR(100) NOT NULL,
    -- IMPORTANTE: Usamos 'anio' sin ñ para evitar problemas de compatibilidad
    -- Algunos sistemas no manejan bien caracteres especiales en nombres de columnas
    anio INT NOT NULL,
    -- Campo para guardar la URL de la portada del libro (opcional)
    -- NULL permite que sea opcional, VARCHAR(500) da espacio suficiente para URLs
    imagen_url VARCHAR(500) NULL,
    propietario_id INT NOT NULL,
    fecha_alta TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    -- FOREIGN KEY: Conecta cada libro con su dueño en la tabla usuarios
    -- ON DELETE CASCADE: Si se elimina el usuario, se eliminan sus libros automáticamente
    FOREIGN KEY (propietario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    -- ÍNDICES: Aceleran las búsquedas por estos campos
    INDEX idx_titulo (titulo),
    INDEX idx_autor (autor),
    INDEX idx_genero (genero),
    INDEX idx_propietario (propietario_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLA: prestamos
-- Almacena los préstamos de libros entre usuarios
-- ============================================
CREATE TABLE IF NOT EXISTS prestamos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    libro_id INT NOT NULL,
    prestatario_id INT NOT NULL,
    fecha_prestamo TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_devolucion TIMESTAMP NULL DEFAULT NULL,
    FOREIGN KEY (libro_id) REFERENCES libros(id) ON DELETE CASCADE,
    FOREIGN KEY (prestatario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_libro (libro_id),
    INDEX idx_prestatario (prestatario_id),
    INDEX idx_fecha_prestamo (fecha_prestamo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- DATOS INICIALES
-- ============================================

-- Crear usuario administrador
-- Email: admin@biblioteca.com
-- Contraseña: admin123
-- NOTA: Este hash fue generado con password_hash('admin123', PASSWORD_DEFAULT) en PHP 8.2
INSERT INTO usuarios (nombre, apellidos, email, password, es_admin) 
VALUES (
    'Administrador',
    'del Sistema',
    'admin@biblioteca.com',
    '$2y$10$N2U79YDEmivkw5FSi.G8u.s6P8miyO//1kjj29av8KNPrsGez/YBi', -- password: admin123
    TRUE
);

-- Crear algunos usuarios de ejemplo
-- NOTA: Todos usan la contraseña 'admin123' para facilitar las pruebas
INSERT INTO usuarios (nombre, apellidos, email, password, es_admin) 
VALUES 
    ('Juan', 'García López', 'juan@email.com', '$2y$10$N2U79YDEmivkw5FSi.G8u.s6P8miyO//1kjj29av8KNPrsGez/YBi', FALSE),
    ('María', 'Rodríguez Pérez', 'maria@email.com', '$2y$10$N2U79YDEmivkw5FSi.G8u.s6P8miyO//1kjj29av8KNPrsGez/YBi', FALSE),
    ('Carlos', 'Martínez Sánchez', 'carlos@email.com', '$2y$10$N2U79YDEmivkw5FSi.G8u.s6P8miyO//1kjj29av8KNPrsGez/YBi', FALSE);

-- Crear algunos libros de ejemplo
-- NOTA: Ahora usamos 'anio' (sin ñ) y agregamos imagen_url (puede ser NULL)
INSERT INTO libros (titulo, autor, genero, anio, imagen_url, propietario_id) 
VALUES 
    ('Cien años de soledad', 'Gabriel García Márquez', 'Realismo mágico', 1967, NULL, 2),
    ('Don Quijote de la Mancha', 'Miguel de Cervantes', 'Novela', 1605, NULL, 2),
    ('1984', 'George Orwell', 'Ciencia ficción', 1949, NULL, 3),
    ('El principito', 'Antoine de Saint-Exupéry', 'Fábula', 1943, NULL, 3),
    ('Rayuela', 'Julio Cortázar', 'Novela experimental', 1963, NULL, 4),
    ('La sombra del viento', 'Carlos Ruiz Zafón', 'Misterio', 2001, NULL, 4);

-- Crear algunos préstamos de ejemplo
INSERT INTO prestamos (libro_id, prestatario_id, fecha_prestamo) 
VALUES 
    (1, 3, DATE_SUB(NOW(), INTERVAL 5 DAY)),
    (3, 2, DATE_SUB(NOW(), INTERVAL 10 DAY));

-- ============================================
-- VISTAS ÚTILES
-- ============================================

-- Vista de libros con información del propietario
-- NOTA EDUCATIVA: Una VISTA es como una "tabla virtual" que combina datos de varias tablas
-- Es útil porque podemos consultar datos complejos con una query simple
CREATE OR REPLACE VIEW vista_libros_completa AS
SELECT 
    l.id,
    l.titulo,
    l.autor,
    l.genero,
    l.anio,  -- CORREGIDO: ahora usa 'anio' sin ñ
    l.imagen_url,  -- AGREGADO: incluimos la URL de la imagen
    l.fecha_alta,
    u.id AS propietario_id,
    u.nombre AS propietario_nombre,
    u.apellidos AS propietario_apellidos,
    u.email AS propietario_email,
    -- CASE: Verifica si el libro está prestado actualmente
    -- Si existe un préstamo sin fecha_devolucion, está prestado (FALSE)
    CASE 
        WHEN EXISTS (
            SELECT 1 FROM prestamos p 
            WHERE p.libro_id = l.id 
            AND p.fecha_devolucion IS NULL
        ) THEN FALSE
        ELSE TRUE
    END AS disponible
FROM libros l
INNER JOIN usuarios u ON l.propietario_id = u.id;

-- Vista de préstamos con información completa
CREATE OR REPLACE VIEW vista_prestamos_completa AS
SELECT 
    p.id,
    p.fecha_prestamo,
    p.fecha_devolucion,
    DATEDIFF(COALESCE(p.fecha_devolucion, NOW()), p.fecha_prestamo) AS dias_prestamo,
    l.id AS libro_id,
    l.titulo AS libro_titulo,
    l.autor AS libro_autor,
    l.genero AS libro_genero,
    propietario.id AS propietario_id,
    propietario.nombre AS propietario_nombre,
    propietario.apellidos AS propietario_apellidos,
    prestatario.id AS prestatario_id,
    prestatario.nombre AS prestatario_nombre,
    prestatario.apellidos AS prestatario_apellidos,
    prestatario.email AS prestatario_email
FROM prestamos p
INNER JOIN libros l ON p.libro_id = l.id
INNER JOIN usuarios propietario ON l.propietario_id = propietario.id
INNER JOIN usuarios prestatario ON p.prestatario_id = prestatario.id;

-- ============================================
-- PROCEDIMIENTOS ALMACENADOS
-- ============================================

-- Procedimiento para verificar si un usuario puede ser eliminado
DELIMITER //
CREATE PROCEDURE puede_eliminar_usuario(IN usuario_id INT, OUT puede_eliminar BOOLEAN)
BEGIN
    DECLARE prestamos_activos INT;
    
    -- Contar préstamos activos del usuario
    SELECT COUNT(*) INTO prestamos_activos
    FROM prestamos
    WHERE prestatario_id = usuario_id
    AND fecha_devolucion IS NULL;
    
    -- También verificar si tiene libros prestados
    SELECT COUNT(*) + prestamos_activos INTO prestamos_activos
    FROM prestamos p
    INNER JOIN libros l ON p.libro_id = l.id
    WHERE l.propietario_id = usuario_id
    AND p.fecha_devolucion IS NULL;
    
    SET puede_eliminar = (prestamos_activos = 0);
END //
DELIMITER ;

-- ============================================
-- INFORMACIÓN DE FINALIZACIÓN
-- ============================================
SELECT 'Base de datos creada exitosamente' AS mensaje;
SELECT COUNT(*) AS total_usuarios FROM usuarios;
SELECT COUNT(*) AS total_libros FROM libros;
SELECT COUNT(*) AS total_prestamos FROM prestamos;
