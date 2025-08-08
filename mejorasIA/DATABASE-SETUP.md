# 🗄️ Base de Datos MySQL - JC3Design

## Descripción

Se ha implementado una base de datos MySQL completa para el catálogo de productos de JC3Design. Esta implementación reemplaza los datos estáticos por una base de datos dinámica con APIs RESTful.

## 🏗️ Estructura de la Base de Datos

### Tablas Principales

#### 1. **categorias**
- `id` - ID único de la categoría
- `nombre` - Nombre de la categoría
- `slug` - URL amigable (ej: "productos3d", "muebles")
- `descripcion` - Descripción de la categoría
- `imagen` - Imagen representativa
- `activo` - Estado activo/inactivo
- `orden` - Orden de visualización
- `created_at` / `updated_at` - Timestamps

#### 2. **productos**
- `id` - ID único del producto
- `categoria_id` - Referencia a la categoría
- `codigo` - Código único del producto
- `nombre` - Nombre del producto
- `descripcion` - Descripción detallada
- `precio` - Precio numérico (opcional)
- `precio_mostrar` - Texto del precio (ej: "Consultar precio")
- `dimensiones` - Dimensiones del producto
- `material` - Material utilizado
- `peso` - Peso del producto
- `uso` - Uso recomendado
- `otras_caracteristicas` - Características adicionales
- `observaciones` - Observaciones especiales
- `garantia` - Información de garantía
- `imagen` - Imagen principal
- `imagen_alt` - Texto alternativo
- `stock` - Cantidad en stock
- `activo` - Estado activo/inactivo
- `destacado` - Producto destacado
- `orden` - Orden de visualización
- `created_at` / `updated_at` - Timestamps

#### 3. **producto_imagenes**
- `id` - ID único de la imagen
- `producto_id` - Referencia al producto
- `imagen` - Ruta de la imagen
- `imagen_alt` - Texto alternativo
- `orden` - Orden de visualización
- `principal` - Imagen principal
- `created_at` - Timestamp

#### 4. **producto_especificaciones**
- `id` - ID único de la especificación
- `producto_id` - Referencia al producto
- `nombre` - Nombre de la especificación
- `valor` - Valor de la especificación
- `orden` - Orden de visualización
- `created_at` - Timestamp

#### 5. **usuarios**
- `id` - ID único del usuario
- `username` - Nombre de usuario
- `email` - Email del usuario
- `password` - Contraseña hasheada
- `nombre` - Nombre real
- `apellido` - Apellido
- `rol` - Rol (admin/editor)
- `activo` - Estado activo/inactivo
- `created_at` / `updated_at` - Timestamps

#### 6. **logs**
- `id` - ID único del log
- `usuario_id` - Usuario que realizó la acción
- `accion` - Tipo de acción (crear/actualizar/eliminar)
- `tabla` - Tabla afectada
- `registro_id` - ID del registro afectado
- `datos_anteriores` - Datos antes del cambio (JSON)
- `datos_nuevos` - Datos después del cambio (JSON)
- `ip` - IP del usuario
- `user_agent` - User agent del navegador
- `created_at` - Timestamp

## 🚀 Instalación y Configuración

### 1. Requisitos Previos

- **MySQL** 5.7+ o **MariaDB** 10.2+
- **PHP** 7.4+ con extensiones:
  - `pdo_mysql`
  - `json`
  - `mbstring`
- **Servidor web** (Apache/Nginx)

### 2. Configuración de la Base de Datos

#### Paso 1: Crear la base de datos
```bash
# Conectar a MySQL
mysql -u root -p

# Crear la base de datos
CREATE DATABASE jc3design_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE jc3design_db;

# Importar el esquema
source database/schema.sql;
```

#### Paso 2: Configurar las credenciales
Editar `database/config.php`:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'jc3design_db');
define('DB_USER', 'tu_usuario_mysql');
define('DB_PASS', 'tu_contraseña_mysql');
```

### 3. Configuración del Servidor

#### Para Apache (.htaccess)
```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^api/(.*)$ api/$1 [L]
```

#### Para Nginx
```nginx
location /api/ {
    try_files $uri $uri/ /api/index.php?$query_string;
}
```

## 📡 APIs Disponibles

### Productos

#### GET /api/productos.php
- **Listar productos**: `GET /api/productos.php`
- **Filtrar por categoría**: `GET /api/productos.php?slug=productos3d`
- **Obtener producto específico**: `GET /api/productos.php?id=1`
- **Paginación**: `GET /api/productos.php?page=1&limit=12`
- **Productos destacados**: `GET /api/productos.php?destacado=1`

#### POST /api/productos.php
```json
{
    "nombre": "Nuevo Producto",
    "categoria_id": 1,
    "codigo": "PROD-001",
    "descripcion": "Descripción del producto",
    "precio_mostrar": "Consultar precio",
    "material": "PETG",
    "dimensiones": "100x50x10mm",
    "uso": "Aplicación específica",
    "garantia": "6 meses",
    "imagen": "../images/producto.jpg",
    "destacado": false
}
```

#### PUT /api/productos.php
```json
{
    "id": 1,
    "nombre": "Producto Actualizado",
    "categoria_id": 1,
    "descripcion": "Nueva descripción"
}
```

#### DELETE /api/productos.php
- **Eliminar producto**: `DELETE /api/productos.php?id=1`

### Categorías

#### GET /api/categorias.php
- **Listar categorías**: `GET /api/categorias.php`
- **Obtener por ID**: `GET /api/categorias.php?id=1`
- **Obtener por slug**: `GET /api/categorias.php?slug=productos3d`

#### POST /api/categorias.php
```json
{
    "nombre": "Nueva Categoría",
    "slug": "nueva-categoria",
    "descripcion": "Descripción de la categoría",
    "imagen": "../images/categoria.jpg"
}
```

## 🔧 Panel de Administración

### Acceso al Panel
- **URL**: `http://tu-dominio.com/admin/`
- **Usuario por defecto**: `admin`
- **Contraseña por defecto**: `admin123`

### Funcionalidades del Panel

#### Dashboard
- Estadísticas de productos y categorías
- Productos recientes
- Acciones rápidas

#### Gestión de Productos
- Listar todos los productos
- Crear nuevo producto
- Editar producto existente
- Eliminar producto (soft delete)
- Marcar como destacado

#### Gestión de Categorías
- Listar categorías
- Crear nueva categoría
- Editar categoría
- Eliminar categoría

#### Logs de Actividad
- Ver historial de cambios
- Filtrar por usuario/acción
- Exportar logs

## 🔄 Migración desde Datos Estáticos

### 1. Los datos ya están migrados
El archivo `database/schema.sql` incluye todos los productos y categorías que estaban en `js/productosData.js`.

### 2. Actualización del Frontend
El archivo `js/productos.js` ha sido actualizado para usar las APIs en lugar de los datos estáticos.

### 3. Compatibilidad
- Las URLs existentes siguen funcionando
- La estructura visual se mantiene igual
- El modo oscuro sigue funcionando

## 📊 Características Avanzadas

### Paginación
- Soporte para paginación automática
- Configurable por página
- Navegación intuitiva

### Filtros
- Por categoría
- Por estado (activo/inactivo)
- Por destacado
- Por fecha de creación

### Búsqueda
- Búsqueda por nombre
- Búsqueda por código
- Búsqueda por descripción

### Logs de Actividad
- Registro automático de cambios
- Información detallada de cada acción
- Trazabilidad completa

### Seguridad
- Validación de datos
- Sanitización de inputs
- Autenticación requerida para modificaciones
- Soft delete (no eliminación física)

## 🛠️ Mantenimiento

### Backup de la Base de Datos
```bash
mysqldump -u root -p jc3design_db > backup_$(date +%Y%m%d_%H%M%S).sql
```

### Restaurar Backup
```bash
mysql -u root -p jc3design_db < backup_20250101_120000.sql
```

### Optimización
```sql
-- Analizar tablas
ANALYZE TABLE productos, categorias;

-- Optimizar tablas
OPTIMIZE TABLE productos, categorias;
```

## 🔍 Troubleshooting

### Problemas Comunes

#### 1. Error de conexión a la base de datos
- Verificar credenciales en `database/config.php`
- Verificar que MySQL esté ejecutándose
- Verificar permisos del usuario

#### 2. Error 404 en APIs
- Verificar configuración del servidor web
- Verificar que los archivos PHP estén en la ubicación correcta
- Verificar permisos de archivos

#### 3. Productos no se cargan
- Verificar que la base de datos tenga datos
- Verificar logs de error de PHP
- Verificar la consola del navegador

#### 4. Error de autenticación
- Verificar que la sesión esté iniciada
- Verificar permisos de administrador
- Verificar configuración de sesiones

### Debug

#### Habilitar logs de error
```php
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

#### Verificar conexión a la base de datos
```php
try {
    $db = getDB();
    echo "Conexión exitosa";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
```

## 📈 Próximas Mejoras

### Funcionalidades Planificadas
- [ ] Búsqueda avanzada con filtros
- [ ] Sistema de imágenes múltiples
- [ ] Variantes de productos
- [ ] Sistema de inventario
- [ ] Exportación a CSV/Excel
- [ ] API para aplicaciones móviles
- [ ] Cache de consultas
- [ ] Sistema de notificaciones

### Optimizaciones
- [ ] Índices adicionales para búsqueda
- [ ] Cache de consultas frecuentes
- [ ] Compresión de imágenes automática
- [ ] CDN para imágenes
- [ ] API rate limiting

---

**Desarrollado por:** Asistente IA  
**Fecha:** 2025  
**Versión:** 1.0.0
