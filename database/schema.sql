-- Base de datos para JC3Design
CREATE DATABASE IF NOT EXISTS jc3design_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE jc3design_db;

-- Tabla de categorías
CREATE TABLE categorias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    descripcion TEXT,
    imagen VARCHAR(255),
    activo BOOLEAN DEFAULT TRUE,
    orden INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabla de productos
CREATE TABLE productos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    categoria_id INT NOT NULL,
    codigo VARCHAR(50) UNIQUE,
    nombre VARCHAR(255) NOT NULL,
    descripcion TEXT,
    precio DECIMAL(10,2),
    precio_mostrar VARCHAR(100) DEFAULT 'Consultar precio vía contacto',
    dimensiones VARCHAR(255),
    material VARCHAR(255),
    peso VARCHAR(100),
    uso VARCHAR(255),
    otras_caracteristicas TEXT,
    observaciones TEXT,
    garantia VARCHAR(255),
    imagen VARCHAR(255),
    imagen_alt VARCHAR(255),
    stock INT DEFAULT 0,
    activo BOOLEAN DEFAULT TRUE,
    destacado BOOLEAN DEFAULT FALSE,
    orden INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE CASCADE
);

-- Tabla de imágenes de productos (para múltiples imágenes por producto)
CREATE TABLE producto_imagenes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    producto_id INT NOT NULL,
    imagen VARCHAR(255) NOT NULL,
    imagen_alt VARCHAR(255),
    orden INT DEFAULT 0,
    principal BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE
);

-- Tabla de especificaciones técnicas
CREATE TABLE producto_especificaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    producto_id INT NOT NULL,
    nombre VARCHAR(255) NOT NULL,
    valor TEXT NOT NULL,
    orden INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE
);

-- Tabla de usuarios (para administración)
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    nombre VARCHAR(100),
    apellido VARCHAR(100),
    rol ENUM('admin', 'editor') DEFAULT 'editor',
    activo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabla de logs de actividad
CREATE TABLE logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT,
    accion VARCHAR(100) NOT NULL,
    tabla VARCHAR(100),
    registro_id INT,
    datos_anteriores JSON,
    datos_nuevos JSON,
    ip VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
);

-- Tabla de servicios
CREATE TABLE servicios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(255) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    descripcion TEXT,
    descripcion_corta VARCHAR(500),
    precio_base DECIMAL(10,2),
    precio_mostrar VARCHAR(100),
    tiempo_estimado VARCHAR(100),
    incluye TEXT,
    no_incluye TEXT,
    imagen VARCHAR(255),
    activo BOOLEAN DEFAULT TRUE,
    destacado BOOLEAN DEFAULT FALSE,
    orden INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabla de cotizaciones (actualizada para incluir servicios)
CREATE TABLE cotizaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tipo_cotizacion ENUM('producto', 'servicio') NOT NULL,
    producto_id INT NULL,
    servicio_id INT NULL,
    nombre_cliente VARCHAR(255) NOT NULL,
    email_cliente VARCHAR(255) NOT NULL,
    telefono_cliente VARCHAR(50),
    mensaje TEXT,
    detalles_proyecto TEXT,
    presupuesto_estimado VARCHAR(100),
    fecha_requerida DATE NULL,
    estado ENUM('solicitada', 'enviada', 'vendida') DEFAULT 'solicitada',
    precio_cotizado DECIMAL(10,2),
    notas_admin TEXT,
    fecha_solicitud TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_envio TIMESTAMP NULL,
    fecha_venta TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE SET NULL,
    FOREIGN KEY (servicio_id) REFERENCES servicios(id) ON DELETE SET NULL
);

-- Índices para optimizar consultas
CREATE INDEX idx_productos_categoria ON productos(categoria_id);
CREATE INDEX idx_productos_activo ON productos(activo);
CREATE INDEX idx_productos_destacado ON productos(destacado);
CREATE INDEX idx_producto_imagenes_producto ON producto_imagenes(producto_id);
CREATE INDEX idx_producto_especificaciones_producto ON producto_especificaciones(producto_id);

-- Insertar datos iniciales

-- Categorías
INSERT INTO categorias (nombre, slug, descripcion, imagen, orden) VALUES
('Piezas 3D', 'productos3d', 'Piezas impresas en 3D de alta precisión para diferentes aplicaciones', '../images/p13w_jc3d.jpg', 1),
('Muebles a Medida', 'muebles', 'Muebles personalizados diseñados y fabricados especialmente para cada cliente', '../images/mueble_1.jpg', 2);

-- Productos 3D
INSERT INTO productos (categoria_id, codigo, nombre, descripcion, dimensiones, material, uso, otras_caracteristicas, observaciones, garantia, imagen, destacado) VALUES
(1, 'P3D-001', 'Escuadra de refuerzo', 'Pieza de refuerzo estructural para uniones en ángulo recto. Ideal para proyectos de carpintería y construcción.', '50 x 50 x 3 mm', 'PETG', 'Carpintería y construcción', 'Alta resistencia mecánica', 'Se puede personalizar según necesidades', '6 meses (garantía legal)', '../images/p13w_jc3d.jpg', TRUE),
(1, 'P3D-002', 'Soporte de barra intermedia', 'Soporte diseñado para sostener barras intermedias en estructuras. Perfecto para proyectos de organización y almacenamiento.', 'A medida según aplicación', 'PETG', 'Organización y almacenamiento', 'Diseño modular', 'Se adapta a diferentes diámetros de barra', '6 meses (garantía legal)', '../images/p13w_jc3d.jpg', FALSE),
(1, 'P3D-003', 'Soporte barra lateral', 'Soporte lateral para barras y estructuras', 'A medida', 'PETG', 'Estructuras y soportes', 'Diseño versátil', 'Personalizable', '6 meses (garantía legal)', '../images/p13w_jc3d.jpg', FALSE),
(1, 'P3D-004', 'Soporte escuadra de unión', 'Soporte en forma de escuadra para uniones estructurales', '40 x 20 x 3,0 mm', 'PETG', 'Uniones estructurales', 'Alta resistencia', 'Ideal para proyectos de carpintería', '6 meses (garantía legal)', '../images/p13w_jc3d.jpg', FALSE),
(1, 'P3D-005', 'Soporte escuadra pequeño', 'Soporte escuadra de tamaño reducido', '20 x 20 x 3,0 mm', 'PETG', 'Uniones pequeñas', 'Compacto', 'Para espacios reducidos', '6 meses (garantía legal)', '../images/p13w_jc3d.jpg', FALSE),
(1, 'P3D-006', 'Soporte escuadra mediano', 'Soporte escuadra de tamaño mediano', '25 x 25 x 3,2 mm', 'PETG', 'Uniones medianas', 'Resistencia intermedia', 'Balance entre tamaño y resistencia', '6 meses (garantía legal)', '../images/p13w_jc3d.jpg', FALSE),
(1, 'P3D-007', 'Soporte triangular', 'Soporte en forma triangular para refuerzos', '<br> 20 x 20 mm <br> 25 x 25 mm', 'PETG', 'Refuerzos triangulares', 'Múltiples tamaños', 'Dos variantes disponibles', '6 meses (garantía legal)', '../images/p13w_jc3d.jpg', FALSE),
(1, 'P3D-008', 'Soporte triangular de unión', 'Soporte triangular especializado para uniones', '40 x 20 mm', 'PETG', 'Uniones triangulares', 'Diseño especializado', 'Para aplicaciones específicas', '6 meses (garantía legal)', '../images/p13w_jc3d.jpg', FALSE),
(1, 'P3D-009', 'Placa de unión', 'Placa plana para uniones múltiples', '80 x 40 x 3 mm', 'PETG', 'Uniones múltiples', 'Superficie amplia', 'Ideal para conexiones complejas', '6 meses (garantía legal)', '../images/p13w_jc3d.jpg', FALSE),
(1, 'P3D-010', 'Soporte universal', 'Soporte versátil para múltiples aplicaciones', '30 x 30 x 3 mm', 'PETG', 'Aplicaciones generales', 'Versatilidad', 'Múltiples usos', '6 meses (garantía legal)', '../images/p13w_jc3d.jpg', FALSE),
(1, 'P3D-011', 'Soporte Elegance', 'Soporte con diseño elegante', 'A medida', 'PETG', 'Aplicaciones decorativas', 'Diseño elegante', 'Para proyectos especiales', '6 meses (garantía legal)', '../images/p13w_jc3d.jpg', FALSE),
(1, 'P3D-012', 'Pasacables con rosca mediano', 'Pasacables con sistema de rosca mediano', 'Ø50 mm', 'PETG', 'Cableado', 'Sistema de rosca', 'Para instalaciones eléctricas', '6 meses (garantía legal)', '../images/p13w_jc3d.jpg', FALSE),
(1, 'P3D-013', 'Pasacables con rosca grande', 'Pasacables con sistema de rosca grande', 'Ø60 mm', 'PETG', 'Cableado grueso', 'Sistema de rosca', 'Para cables de mayor diámetro', '6 meses (garantía legal)', '../images/p13w_jc3d.jpg', FALSE);

-- Productos Muebles
INSERT INTO productos (categoria_id, codigo, nombre, descripcion, dimensiones, material, uso, otras_caracteristicas, observaciones, garantia, imagen, destacado) VALUES
(2, 'MUE-001', 'Mueble de Cocina Personalizado', 'Muebles de cocina diseñados específicamente para tu espacio y necesidades.', 'A medida', 'Madera de pino y melamina', 'Cocina', 'Incluye cajones con correderas suaves', 'Diseño personalizado según medidas del cliente', '1 año de garantía', '../images/mueble_1.jpg', TRUE),
(2, 'MUE-002', 'Estantería Modular', 'Sistema de estanterías modulares que se adaptan a cualquier espacio.', 'A medida', 'Madera de pino tratada', 'Hogar y oficina', 'Módulos intercambiables', 'Se puede expandir según necesidades', '1 año de garantía', '../images/mueble_1.jpg', FALSE),
(2, 'MUE-003', 'Mesa de Trabajo', 'Mesa de trabajo ergonómica con almacenamiento integrado.', 'A medida', 'Madera de pino y metal', 'Oficina y taller', 'Incluye cajones y repisas', 'Diseño ergonómico para largas jornadas', '1 año de garantía', '../images/mueble_1.jpg', FALSE),
(2, 'MUE-004', 'Rack para TV', 'Rack personalizado para TV con cableado organizado.', 'A medida', 'Madera de pino y MDF', 'Sala de estar', 'Cableado oculto y ventilación', 'Incluye espacios para equipos de audio', '1 año de garantía', '../images/mueble_1.jpg', FALSE),
(2, 'MUE-005', 'Mueble de Baño', 'Muebles de baño resistentes a la humedad y personalizados.', 'A medida', 'Madera tratada y melamina', 'Baño', 'Resistente a la humedad', 'Diseño que aprovecha el espacio disponible', '1 año de garantía', '../images/mueble_1.jpg', FALSE);

-- Usuario administrador por defecto (password: admin123)
INSERT INTO usuarios (username, email, password, nombre, apellido, rol) VALUES
('admin', 'admin@jc3design.cl', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrador', 'JC3Design', 'admin');

-- Tabla de contactos (Mini CRM)
CREATE TABLE contactos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tipo_consulta ENUM('cotizacion', 'consulta', 'felicitacion', 'reclamo', 'sugerencia', 'trabajo', 'otro') NOT NULL,
    nombre VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    telefono VARCHAR(50),
    ciudad VARCHAR(100),
    asunto VARCHAR(255) NOT NULL,
    mensaje TEXT NOT NULL,
    presupuesto VARCHAR(50),
    plazo VARCHAR(50),
    como_nos_conocio VARCHAR(100),
    preferencias_contacto JSON,
    newsletter BOOLEAN DEFAULT FALSE,
    estado ENUM('nuevo', 'contactado', 'en_proceso', 'cerrado') DEFAULT 'nuevo',
    prioridad ENUM('baja', 'media', 'alta', 'urgente') DEFAULT 'media',
    notas_admin TEXT,
    asignado_a INT NULL,
    fecha_contacto TIMESTAMP NULL,
    fecha_cierre TIMESTAMP NULL,
    ip_origen VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (asignado_a) REFERENCES usuarios(id) ON DELETE SET NULL
);

-- Índices para optimizar consultas de contactos
CREATE INDEX idx_contactos_tipo ON contactos(tipo_consulta);
CREATE INDEX idx_contactos_estado ON contactos(estado);
CREATE INDEX idx_contactos_prioridad ON contactos(prioridad);
CREATE INDEX idx_contactos_fecha ON contactos(created_at);
CREATE INDEX idx_contactos_email ON contactos(email);

-- Insertar servicios de ejemplo
INSERT INTO servicios (nombre, slug, descripcion, descripcion_corta, precio_base, precio_mostrar, tiempo_estimado, incluye, no_incluye, imagen, orden) VALUES
(
    'Diseño de Muebles 3D', 
    'diseno-muebles-3d', 
    'Servicio completo de diseño de muebles personalizados utilizando tecnología 3D. Creamos modelos detallados que te permiten visualizar tu mueble antes de la fabricación.',
    'Diseño personalizado de muebles con modelado 3D y renders fotorrealistas.',
    15000,
    'Desde $15.000',
    '3-5 días hábiles',
    'Modelado 3D completo, Renders fotorrealistas, Planos técnicos, 3 revisiones incluidas',
    'Fabricación del mueble, Materiales, Instalación',
    'images/dise_1.jpg',
    1
),
(
    'Impresión 3D', 
    'impresion-3d', 
    'Servicio de impresión 3D para piezas personalizadas, prototipos y elementos decorativos. Trabajamos con diferentes materiales y acabados.',
    'Impresión 3D de alta calidad para prototipos y piezas personalizadas.',
    5000,
    'Desde $5.000',
    '1-3 días hábiles',
    'Impresión 3D, Post-procesado básico, Consultoría técnica',
    'Diseño del modelo, Materiales premium, Acabados especiales',
    'images/p13w_jc3d.jpg',
    2
),
(
    'Fabricación de Muebles a Medida', 
    'fabricacion-muebles', 
    'Fabricación completa de muebles personalizados según tus especificaciones. Desde el diseño hasta la instalación en tu hogar.',
    'Fabricación e instalación de muebles personalizados con materiales premium.',
    50000,
    'Desde $50.000',
    '7-14 días hábiles',
    'Diseño personalizado, Fabricación completa, Instalación, Garantía 1 año',
    'Mantenimiento, Modificaciones posteriores',
    'images/mueble_1.jpg',
    3
);
