# 🏗️ JC3Design - Sitio Web Corporativo

## 📋 Descripción del Proyecto

**JC3Design** es un sitio web corporativo completo para una empresa especializada en:
- **Impresión 3D** y piezas personalizadas
- **Diseño de muebles** a medida 
- **Diseño de espacios** personalizados
- **Catálogo de productos** dinámico
- **Sistema de cotizaciones** integrado
- **Panel administrativo** completo

### ✨ Características Principales

- **🎨 Diseño Responsive**: Adaptable a todos los dispositivos
- **🌙 Modo Oscuro**: Interfaz con alternancia de tema claro/oscuro
- **🎠 Carrusel Optimizado**: Galería de imágenes con carga optimizada
- **📱 Progressive Web App**: Experiencia similar a una app nativa
- **🗄️ Base de Datos MySQL**: Sistema completo de gestión de contenido
- **🔐 Panel Admin**: Sistema de administración con autenticación
- **📊 Mini CRM**: Gestión de contactos y cotizaciones
- **🚀 Optimizaciones**: Lazy loading, cache, y mejoras de rendimiento

---

## 🛠️ Requisitos del Sistema

### Servidor Web
- **Apache** 2.4+ o **Nginx** 1.18+
- **PHP** 7.4+ (recomendado PHP 8.0+)
- **MySQL** 5.7+ o **MariaDB** 10.3+
- **SSL** (certificado HTTPS recomendado)

### Extensiones PHP Requeridas
```
- php-mysql
- php-pdo
- php-mbstring
- php-json
- php-session
- php-filter
- php-fileinfo
```

### Espacio en Disco
- **Mínimo**: 100 MB
- **Recomendado**: 500 MB (para imágenes y logs)

---

## 📥 Instalación Paso a Paso

### 1. 📁 Descarga y Preparación de Archivos

#### Opción A: Clonar repositorio
```bash
git clone [URL_REPOSITORIO] jc3design
cd jc3design
```

#### Opción B: Subida manual
1. Descarga todos los archivos del proyecto
2. Sube los archivos al directorio web de tu hosting:
   ```
   /public_html/
   ├── admin/
   ├── api/
   ├── css/
   ├── database/
   ├── images/
   ├── js/
   ├── pag/
   ├── index.html
   └── [otros archivos]
   ```

### 2. 🗄️ Configuración de Base de Datos

#### 2.1 Crear Base de Datos
```sql
CREATE DATABASE jc3design_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

#### 2.2 Crear Usuario de Base de Datos (Recomendado)
```sql
CREATE USER 'jc3design_user'@'localhost' IDENTIFIED BY 'tu_password_seguro';
GRANT ALL PRIVILEGES ON jc3design_db.* TO 'jc3design_user'@'localhost';
FLUSH PRIVILEGES;
```

#### 2.3 Importar Estructura y Datos
```bash
mysql -u root -p jc3design_db < database/schema.sql
```

### 3. ⚙️ Configuración de PHP

#### 3.1 Configurar Base de Datos
Edita el archivo `database/config.php`:

```php
// Configuración de la base de datos
define('DB_HOST', 'localhost');           // Host de tu base de datos
define('DB_NAME', 'jc3design_db');       // Nombre de tu base de datos
define('DB_USER', 'jc3design_user');     // Usuario de base de datos
define('DB_PASS', 'tu_password_seguro');  // Contraseña de base de datos

// Configuración de la aplicación
define('APP_URL', 'https://tudominio.com'); // URL de tu sitio
define('JWT_SECRET', 'cambia_esta_clave_secreta_en_produccion');
```

#### 3.2 Configurar Permisos de Archivos
```bash
# Permisos para directorios
chmod 755 admin/ api/ css/ js/ pag/ images/
chmod 644 *.html *.php

# Crear directorios necesarios
mkdir -p uploads/ logs/
chmod 775 uploads/ logs/
```

### 4. 🌐 Configuración del Servidor Web

#### Apache (.htaccess)
Crea un archivo `.htaccess` en la raíz:
```apache
RewriteEngine On

# Redirigir HTTP a HTTPS
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

# Proteger archivos sensibles
<Files "database/config.php">
    Order allow,deny
    Deny from all
</Files>

<Files "*.sql">
    Order allow,deny
    Deny from all
</Files>

# Cache de recursos estáticos
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType image/jpg "access plus 1 month"
    ExpiresByType image/jpeg "access plus 1 month"
    ExpiresByType image/gif "access plus 1 month"
    ExpiresByType image/png "access plus 1 month"
    ExpiresByType text/css "access plus 1 month"
    ExpiresByType application/pdf "access plus 1 month"
    ExpiresByType text/javascript "access plus 1 month"
    ExpiresByType application/javascript "access plus 1 month"
</IfModule>

# Compresión Gzip
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/plain
    AddOutputFilterByType DEFLATE text/html
    AddOutputFilterByType DEFLATE text/xml
    AddOutputFilterByType DEFLATE text/css
    AddOutputFilterByType DEFLATE application/xml
    AddOutputFilterByType DEFLATE application/xhtml+xml
    AddOutputFilterByType DEFLATE application/rss+xml
    AddOutputFilterByType DEFLATE application/javascript
    AddOutputFilterByType DEFLATE application/x-javascript
</IfModule>
```

#### Nginx (configuración adicional)
```nginx
location ~ \.php$ {
    fastcgi_pass unix:/var/run/php/php8.0-fpm.sock;
    fastcgi_index index.php;
    fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    include fastcgi_params;
}

location /database/ {
    deny all;
}

location ~ \.(sql|md)$ {
    deny all;
}
```

---

## 🔐 Credenciales de Administrador

### Acceso al Panel Administrativo

**URL de Acceso**: `https://tudominio.com/admin/`

**Credenciales por Defecto**:
- **Usuario**: `admin`
- **Email**: `admin@jc3design.cl`
- **Contraseña**: `admin123`

> ⚠️ **IMPORTANTE**: Cambia estas credenciales inmediatamente después de la instalación.

### Cambiar Contraseña de Administrador

1. Accede al panel de administración
2. Ve a "Usuarios" → "Editar administrador"
3. Cambia la contraseña por una segura
4. O ejecuta este SQL:

```sql
UPDATE usuarios 
SET password = '$2y$10$[HASH_DE_TU_NUEVA_CONTRASEÑA]' 
WHERE username = 'admin';
```

Para generar el hash de contraseña en PHP:
```php
echo password_hash('tu_nueva_contraseña', PASSWORD_DEFAULT);
```

---

## 📂 Estructura del Proyecto

```
jc3design/
├── 📁 admin/              # Panel administrativo
│   ├── index.php          # Dashboard principal
│   ├── login.php          # Sistema de login
│   ├── productos.php      # Gestión de productos
│   ├── categorias.php     # Gestión de categorías
│   ├── contactos.php      # Mini CRM de contactos
│   ├── cotizaciones.php   # Gestión de cotizaciones
│   ├── usuarios.php       # Gestión de usuarios
│   └── logs.php           # Logs del sistema
├── 📁 api/                # APIs RESTful
│   ├── productos.php      # API de productos
│   ├── categorias.php     # API de categorías
│   ├── contactos.php      # API de contactos
│   ├── cotizaciones.php   # API de cotizaciones
│   └── servicios.php      # API de servicios
├── 📁 css/                # Hojas de estilo
│   ├── style.css          # Estilos principales
│   ├── dark-mode.css      # Modo oscuro
│   ├── optimizacion.css   # Optimizaciones
│   └── [otros CSS]
├── 📁 database/           # Base de datos
│   ├── config.php         # Configuración de DB
│   └── schema.sql         # Estructura de DB
├── 📁 images/             # Imágenes del sitio
├── 📁 js/                 # Scripts JavaScript
│   ├── script.js          # Scripts principales
│   ├── carrusel-fix.js    # Fix del carrusel
│   ├── dark-mode.js       # Modo oscuro
│   └── [otros JS]
├── 📁 pag/                # Páginas adicionales
│   ├── productos.html     # Catálogo de productos
│   ├── contacto.html      # Formulario de contacto
│   ├── nosotros.html      # Página sobre nosotros
│   └── [otras páginas]
├── index.html             # Página principal
└── README.md              # Este archivo
```

---

## 🔧 Configuraciones Avanzadas

### SSL/HTTPS (Recomendado)

1. **Obtener Certificado SSL**:
   - **Let's Encrypt** (gratuito): `certbot --apache -d tudominio.com`
   - **Certificado comercial**: Instalar según proveedor

2. **Configurar HTTPS en config.php**:
```php
define('APP_URL', 'https://tudominio.com');
```

### Optimizaciones de Rendimiento

#### 1. Cache de Base de Datos
```php
// En config.php, agregar:
define('CACHE_ENABLED', true);
define('CACHE_TIME', 3600); // 1 hora
```

#### 2. Compresión de Imágenes
```bash
# Instalar herramientas de optimización
apt-get install jpegoptim optipng

# Optimizar imágenes existentes
find images/ -name "*.jpg" -exec jpegoptim --strip-all {} \;
find images/ -name "*.png" -exec optipng -o7 {} \;
```

#### 3. CDN (Opcional)
Modificar en los HTML para usar CDN:
```html
<!-- Bootstrap desde CDN -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.4.1/dist/css/bootstrap.min.css">
```

### Backup Automático

#### Script de Backup (backup.sh)
```bash
#!/bin/bash
DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/backups"

# Backup de base de datos
mysqldump -u root -p jc3design_db > $BACKUP_DIR/db_backup_$DATE.sql

# Backup de archivos
tar -czf $BACKUP_DIR/files_backup_$DATE.tar.gz /path/to/jc3design/

# Limpiar backups antiguos (mantener 7 días)
find $BACKUP_DIR -name "*.sql" -mtime +7 -delete
find $BACKUP_DIR -name "*.tar.gz" -mtime +7 -delete
```

#### Programar con Cron
```bash
# Editar crontab
crontab -e

# Agregar backup diario a las 2:00 AM
0 2 * * * /path/to/backup.sh
```

---

## 🎛️ Panel de Administración

### Funcionalidades Disponibles

#### 📊 Dashboard
- **Estadísticas** de productos, contactos y cotizaciones
- **Gráficos** de actividad
- **Accesos rápidos** a funciones principales

#### 🛍️ Gestión de Productos
- **CRUD completo** de productos
- **Categorización** y etiquetado
- **Múltiples imágenes** por producto
- **Control de stock** y precios
- **Productos destacados**

#### 📝 Gestión de Categorías  
- **Crear y editar** categorías
- **URLs amigables** (slugs)
- **Orden de visualización**
- **Activar/desactivar** categorías

#### 👥 Mini CRM de Contactos
- **Gestión de consultas** y cotizaciones
- **Filtros por tipo** de consulta
- **Estados de seguimiento**
- **Historial de contactos**

#### 📋 Sistema de Cotizaciones
- **Cotizaciones de diseño**, muebles y 3D
- **Estados de seguimiento**
- **Información detallada** del cliente
- **Gestión de presupuestos**

#### 👤 Gestión de Usuarios
- **Múltiples usuarios** administradores
- **Roles y permisos**
- **Control de acceso**

#### 📈 Logs y Auditoría
- **Registro de actividades**
- **Seguimiento de cambios**
- **IPs y user agents**
- **Filtros por fecha y usuario**

---

## 🐛 Solución de Problemas

### Problemas Comunes

#### 1. **Error de Conexión a Base de Datos**
```
Error: PDO Connection failed
```
**Solución**:
- Verificar credenciales en `database/config.php`
- Comprobar que MySQL esté ejecutándose
- Verificar permisos del usuario de base de datos

#### 2. **Imágenes del Carrusel No Cargan**
**Síntomas**: Solo aparece la primera imagen o placeholders
**Solución**:
- Ejecutar en consola del navegador: `forceReloadCarouselImages()`
- Verificar permisos de la carpeta `images/`
- Limpiar caché del navegador

#### 3. **Panel de Admin No Accesible**
```
Error 404 - Admin page not found
```
**Solución**:
- Verificar que la carpeta `admin/` esté subida
- Comprobar permisos de archivos PHP
- Verificar configuración del servidor web

#### 4. **Modo Oscuro No Funciona**
**Solución**:
- Verificar que `js/dark-mode.js` esté cargado
- Limpiar localStorage del navegador
- Comprobar errores en consola

### Logs de Error

#### Activar Logs de PHP
```php
// En config.php
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', 'logs/php_errors.log');
```

#### Verificar Logs del Sistema
```bash
# Logs de Apache
tail -f /var/log/apache2/error.log

# Logs de PHP
tail -f logs/php_errors.log

# Logs de MySQL
tail -f /var/log/mysql/error.log
```

### Comandos de Diagnóstico

#### Verificar Estado del Carrusel
En la consola del navegador:
```javascript
debugCarousel(); // Diagnóstico completo
initCarousel(); // Reinicializar carrusel
forceReloadCarouselImages(); // Recargar imágenes
```

#### Verificar APIs
```bash
# Probar API de productos
curl -X GET "https://tudominio.com/api/productos.php"

# Probar API de categorías
curl -X GET "https://tudominio.com/api/categorias.php"
```

---

## 🔄 Actualizaciones y Mantenimiento

### Actualización del Sistema

1. **Backup completo** antes de actualizar
2. **Descargar nueva versión**
3. **Comparar archivos** de configuración
4. **Ejecutar scripts** de migración de base de datos
5. **Probar funcionalidades** críticas

### Mantenimiento Regular

#### Semanal
- **Revisar logs** de errores
- **Verificar backups** automáticos
- **Comprobar espacio** en disco

#### Mensual
- **Actualizar dependencias** de seguridad
- **Optimizar base de datos**: `OPTIMIZE TABLE productos, categorias, contactos;`
- **Limpiar logs antiguos**
- **Revisar rendimiento** del sitio

#### Trimestral
- **Auditoría de seguridad**
- **Actualización de contraseñas**
- **Revisión de contenido**
- **Optimización de imágenes**

---

## 📞 Soporte y Contacto

### Información Técnica

- **Versión**: 1.0.0
- **Desarrollado por**: Tomás Gutiérrez
- **Framework**: PHP Vanilla + MySQL + Bootstrap 4
- **Licencia**: Uso comercial permitido

### Recursos Adicionales

- **Documentación de APIs**: `/api/` (endpoints REST)
- **Guías de usuario**: Panel admin → Ayuda
- **Logs del sistema**: Panel admin → Logs

### Para Desarrollo

```bash
# Servidor de desarrollo local
php -S localhost:8000

# Modo debug (mostrar errores)
echo "define('DEBUG_MODE', true);" >> database/config.php
```

---

## ✅ Checklist de Post-Instalación

- [ ] ✅ Base de datos creada e importada
- [ ] ✅ Configuración de `database/config.php` actualizada
- [ ] ✅ Permisos de archivos configurados
- [ ] ✅ SSL/HTTPS configurado
- [ ] ✅ Contraseña de admin cambiada
- [ ] ✅ Backup automático programado
- [ ] ✅ Sitio web accesible
- [ ] ✅ Panel de admin funcional
- [ ] ✅ Carrusel funcionando correctamente
- [ ] ✅ APIs respondiendo
- [ ] ✅ Formularios de contacto funcionando
- [ ] ✅ Modo oscuro operativo
- [ ] ✅ Imágenes cargando correctamente
- [ ] ✅ Logs del sistema activos

---

**🎉 ¡Instalación Completada!**

Tu sitio web JC3Design está ahora listo para usar. Accede a:
- **Sitio principal**: `https://tudominio.com`
- **Panel de administración**: `https://tudominio.com/admin/`
- **Panel de administración**: admin/admin123

Para cualquier consulta técnica o personalización adicional, no dudes en contactar al desarrollador.
