# INSTRUCCIONES DE INSTALACIÓN EN WEBHOSTING
## JC3Design - Base de Datos y Cliente PHP

### 📋 REQUISITOS PREVIOS
- Hosting con soporte para PHP 7.4 o superior
- Base de datos MySQL 5.7 o superior
- Acceso FTP/SFTP al servidor
- Acceso al panel de control del hosting (cPanel, Plesk, etc.)
- Editor de texto para modificar archivos

---

## 🗄️ PASO 1: CREAR LA BASE DE DATOS

### 1.1 Acceder al Panel de Control
1. Inicia sesión en tu panel de control del hosting
2. Busca la sección "Bases de datos" o "MySQL Databases"
3. Crea una nueva base de datos con el nombre: `jc3design_db`
4. Anota el nombre de la base de datos, usuario y contraseña

### 1.2 Crear Usuario de Base de Datos
1. En la misma sección, crea un nuevo usuario de MySQL
2. Asigna una contraseña segura
3. Anota el nombre de usuario y contraseña

### 1.3 Asignar Permisos
1. Asigna el usuario creado a la base de datos `jc3design_db`
2. Otorga todos los privilegios (ALL PRIVILEGES)
3. Guarda la configuración

---

## 🚀 PASO 2: INSTALAR LA BASE DE DATOS

### 2.1 Acceder a phpMyAdmin
1. En tu panel de control, busca "phpMyAdmin" o "Administrar BD"
2. Haz clic para abrir phpMyAdmin
3. Selecciona la base de datos `jc3design_db` del panel izquierdo

### 2.2 Ejecutar el Script SQL
1. Haz clic en la pestaña "SQL"
2. Copia y pega el siguiente script completo:

```sql
-- Base de datos para JC3Design
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

-- Tabla de imágenes de productos
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

-- Tabla de usuarios
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

-- Tabla de logs
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

-- Tabla de cotizaciones
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

-- Tabla de contactos
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

-- Crear índices
CREATE INDEX idx_productos_categoria ON productos(categoria_id);
CREATE INDEX idx_productos_activo ON productos(activo);
CREATE INDEX idx_productos_destacado ON productos(destacado);
CREATE INDEX idx_producto_imagenes_producto ON producto_imagenes(producto_id);
CREATE INDEX idx_producto_especificaciones_producto ON producto_especificaciones(producto_id);
CREATE INDEX idx_contactos_tipo ON contactos(tipo_consulta);
CREATE INDEX idx_contactos_estado ON contactos(estado);
CREATE INDEX idx_contactos_prioridad ON contactos(prioridad);
CREATE INDEX idx_contactos_fecha ON contactos(created_at);
CREATE INDEX idx_contactos_email ON contactos(email);
```

3. Haz clic en "Continuar" o "Ejecutar"
4. Verifica que todas las tablas se hayan creado correctamente

---

## 📊 PASO 3: CARGAR DATOS INICIALES

### 3.1 Insertar Datos de Ejemplo
1. En la misma pestaña SQL, ejecuta este script para insertar datos iniciales:

```sql
-- Insertar categorías
INSERT INTO categorias (nombre, slug, descripcion, imagen, orden) VALUES
('Piezas 3D', 'productos3d', 'Piezas impresas en 3D de alta precisión para diferentes aplicaciones', '../images/p13w_jc3d.jpg', 1),
('Muebles a Medida', 'muebles', 'Muebles personalizados diseñados y fabricados especialmente para cada cliente', '../images/mueble_1.jpg', 2);

-- Insertar productos 3D
INSERT INTO productos (categoria_id, codigo, nombre, descripcion, dimensiones, material, uso, otras_caracteristicas, observaciones, garantia, imagen, destacado) VALUES
(1, 'P3D-001', 'Escuadra de refuerzo', 'Pieza de refuerzo estructural para uniones en ángulo recto. Ideal para proyectos de carpintería y construcción.', '50 x 50 x 3 mm', 'PETG', 'Carpintería y construcción', 'Alta resistencia mecánica', 'Se puede personalizar según necesidades', '6 meses (garantía legal)', '../images/p13w_jc3d.jpg', TRUE),
(1, 'P3D-002', 'Soporte de barra intermedia', 'Soporte diseñado para sostener barras intermedias en estructuras. Perfecto para proyectos de organización y almacenamiento.', 'A medida según aplicación', 'PETG', 'Organización y almacenamiento', 'Diseño modular', 'Se adapta a diferentes diámetros de barra', '6 meses (garantía legal)', '../images/p13w_jc3d.jpg', FALSE);

-- Insertar productos muebles
INSERT INTO productos (categoria_id, codigo, nombre, descripcion, dimensiones, material, uso, otras_caracteristicas, observaciones, garantia, imagen, destacado) VALUES
(2, 'MUE-001', 'Mueble de Cocina Personalizado', 'Muebles de cocina diseñados específicamente para tu espacio y necesidades.', 'A medida', 'Madera de pino y melamina', 'Cocina', 'Incluye cajones con correderas suaves', 'Diseño personalizado según medidas del cliente', '1 año de garantía', '../images/mueble_1.jpg', TRUE),
(2, 'MUE-002', 'Estantería Modular', 'Sistema de estanterías modulares que se adaptan a cualquier espacio.', 'A medida', 'Madera de pino tratada', 'Hogar y oficina', 'Módulos intercambiables', 'Se puede expandir según necesidades', '1 año de garantía', '../images/mueble_1.jpg', FALSE);

-- Insertar usuario administrador (password: admin123)
INSERT INTO usuarios (username, email, password, nombre, apellido, rol) VALUES
('admin', 'admin@jc3design.cl', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrador', 'JC3Design', 'admin');

-- Insertar servicios
INSERT INTO servicios (nombre, slug, descripcion, descripcion_corta, precio_base, precio_mostrar, tiempo_estimado, incluye, no_incluye, imagen, orden) VALUES
('Diseño de Muebles 3D', 'diseno-muebles-3d', 'Servicio completo de diseño de muebles personalizados utilizando tecnología 3D.', 'Diseño personalizado de muebles con modelado 3D y renders fotorrealistas.', 15000, 'Desde $15.000', '3-5 días hábiles', 'Modelado 3D completo, Renders fotorrealistas, Planos técnicos, 3 revisiones incluidas', 'Fabricación del mueble, Materiales, Instalación', 'images/dise_1.jpg', 1),
('Impresión 3D', 'impresion-3d', 'Servicio de impresión 3D para piezas personalizadas, prototipos y elementos decorativos.', 'Impresión 3D de alta calidad para prototipos y piezas personalizadas.', 5000, 'Desde $5.000', '1-3 días hábiles', 'Impresión 3D, Post-procesado básico, Consultoría técnica', 'Diseño del modelo, Materiales premium, Acabados especiales', 'images/p13w_jc3d.jpg', 2);
```

### 3.2 Verificar la Carga
1. Haz clic en cada tabla para verificar que los datos se hayan insertado
2. Deberías ver:
   - 2 categorías
   - 4 productos (2 de cada categoría)
   - 1 usuario administrador
   - 2 servicios

---

## ⚙️ PASO 4: CONFIGURAR EL ARCHIVO DE CONEXIÓN

### 4.1 Descargar y Editar config.php
1. Descarga el archivo `database/config.php` de tu proyecto local
2. Abre el archivo en un editor de texto
3. Modifica las siguientes líneas con tus datos del hosting:

```php
// Configuración de la base de datos
define('DB_HOST', 'localhost'); // Generalmente es 'localhost'
define('DB_NAME', 'jc3design_db'); // El nombre que creaste
define('DB_USER', 'TU_USUARIO_MYSQL'); // El usuario que creaste
define('DB_PASS', 'TU_CONTRASEÑA_MYSQL'); // La contraseña que asignaste
define('DB_CHARSET', 'utf8mb4');

// Configuración de la aplicación
define('APP_NAME', 'JC3Design');
define('APP_URL', 'https://tudominio.com'); // Cambia por tu dominio real
define('APP_VERSION', '1.0.0');

// Configuración de seguridad (IMPORTANTE: Cambiar en producción)
define('JWT_SECRET', 'cambia_esta_clave_secreta_por_una_muy_segura_2025');
```

### 4.2 Guardar el Archivo
1. Guarda el archivo con los cambios
2. Asegúrate de que la codificación sea UTF-8

---

## 📁 PASO 5: SUBIR ARCHIVOS VÍA FTP

### 5.1 Conectar por FTP
1. Abre tu cliente FTP (FileZilla, WinSCP, etc.)
2. Conecta a tu servidor usando:
   - Host: tu-dominio.com o IP del servidor
   - Usuario: tu usuario FTP
   - Contraseña: tu contraseña FTP
   - Puerto: 21 (FTP) o 22 (SFTP)

### 5.2 Estructura de Directorios
Crea la siguiente estructura en tu servidor:

```
public_html/ (o www/)
├── admin/
├── api/
├── css/
├── database/
├── images/
├── js/
├── logs/
├── pag/
├── test/
├── uploads/
├── videos/
├── index.html
└── README.md
```

### 5.3 Subir Archivos
1. **Archivos PHP**: Sube todos los archivos `.php` a sus respectivas carpetas
2. **Archivos HTML/CSS/JS**: Sube todos los archivos frontend
3. **Imágenes**: Sube la carpeta `images/` completa
4. **Base de datos**: Sube el archivo `database/config.php` modificado
5. **Logs**: Crea la carpeta `logs/` y asegúrate de que tenga permisos de escritura (755)

### 5.4 Permisos de Archivos
Establece los siguientes permisos:
- Archivos: 644
- Carpetas: 755
- `logs/`: 755
- `uploads/`: 755

---

## 🔐 PASO 6: CONFIGURAR SEGURIDAD

### 6.1 Cambiar Contraseña de Administrador
1. Accede a tu base de datos
2. Ejecuta este comando para cambiar la contraseña del admin:

```sql
UPDATE usuarios 
SET password = '$2y$10$' || SUBSTRING(SHA2(CONCAT('nueva_contraseña', RAND()), 256), 1, 22) 
WHERE username = 'admin';
```

**O mejor aún, usa un generador de hash online:**
1. Ve a https://bcrypt-generator.com/
2. Genera un hash para tu nueva contraseña
3. Ejecuta:

```sql
UPDATE usuarios 
SET password = 'HASH_GENERADO_AQUI' 
WHERE username = 'admin';
```

### 6.2 Verificar Archivos Sensibles
1. Asegúrate de que `database/config.php` no sea accesible públicamente
2. Verifica que la carpeta `logs/` tenga permisos correctos
3. Revisa que no haya archivos de configuración en directorios públicos

---

## 🧪 PASO 7: PROBAR LA INSTALACIÓN

### 7.1 Verificar Frontend
1. Visita tu sitio web: `https://tudominio.com`
2. Verifica que las páginas se carguen correctamente
3. Revisa que las imágenes se muestren

### 7.2 Verificar Backend
1. Accede al panel de administración: `https://tudominio.com/admin/`
2. Inicia sesión con:
   - Usuario: `admin`
   - Contraseña: `admin123` (o la que hayas cambiado)
3. Verifica que puedas ver:
   - Productos
   - Categorías
   - Contactos
   - Cotizaciones

### 7.3 Verificar APIs
1. Visita: `https://tudominio.com/api/productos.php`
2. Deberías ver una respuesta JSON con los productos
3. Prueba otras APIs: categorías, servicios, etc.

---

## 🚨 SOLUCIÓN DE PROBLEMAS COMUNES

### Error de Conexión a Base de Datos
- Verifica que el usuario y contraseña sean correctos
- Asegúrate de que el usuario tenga permisos en la base de datos
- Verifica que el host sea correcto (generalmente 'localhost')

### Error 500 (Internal Server Error)
- Revisa los logs de error del servidor
- Verifica que PHP esté habilitado
- Comprueba la sintaxis de los archivos PHP

### Páginas en Blanco
- Verifica que los archivos se hayan subido correctamente
- Revisa los permisos de archivos
- Comprueba que no haya errores de PHP

### Imágenes No Se Muestran
- Verifica que las rutas en la base de datos sean correctas
- Asegúrate de que las imágenes se hayan subido
- Revisa los permisos de la carpeta `images/`

---

## 📞 SOPORTE

Si tienes problemas durante la instalación:

1. **Revisa los logs**: `https://tudominio.com/logs/app.log`
2. **Verifica la consola del navegador** para errores JavaScript
3. **Revisa los logs del servidor** en tu panel de hosting
4. **Contacta al soporte** de tu hosting si es un problema del servidor

---

## ✅ CHECKLIST FINAL

- [ ] Base de datos creada y configurada
- [ ] Tablas creadas correctamente
- [ ] Datos iniciales insertados
- [ ] Archivo config.php modificado con datos del hosting
- [ ] Todos los archivos subidos vía FTP
- [ ] Permisos de archivos configurados correctamente
- [ ] Contraseña de administrador cambiada
- [ ] Frontend funcionando correctamente
- [ ] Panel de administración accesible
- [ ] APIs respondiendo correctamente
- [ ] Logs funcionando
- [ ] Imágenes mostrándose correctamente

¡Tu sitio JC3Design debería estar funcionando completamente en tu webhosting!












