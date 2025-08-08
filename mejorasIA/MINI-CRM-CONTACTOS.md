# Mini CRM de Contactos - JC3Design

## 📋 Descripción

Se ha implementado un sistema completo de gestión de contactos (Mini CRM) que permite capturar, organizar y hacer seguimiento de todas las consultas que llegan a través del formulario de contacto del sitio web.

## 🚀 Características Principales

### 1. Captura Automática de Contactos
- **Formulario Integrado**: El formulario de contacto existente ahora guarda automáticamente todos los datos en la base de datos
- **Información Completa**: Captura nombre, email, teléfono, ciudad, tipo de consulta, mensaje, presupuesto, plazos, etc.
- **Seguimiento de Origen**: Registra IP y user agent para análisis de tráfico
- **Preferencias de Contacto**: Almacena las preferencias de comunicación del cliente

### 2. Panel de Administración Completo
- **Dashboard Integrado**: Acceso directo desde el panel de administración principal
- **Estadísticas en Tiempo Real**: Visualización de contactos nuevos, contactados, en proceso y cerrados
- **Vista de Tarjetas y Lista**: Dos formas de visualizar los contactos según preferencia

### 3. Sistema de Gestión Avanzado
- **Estados de Contacto**:
  - ✨ **Nuevo**: Contactos recién llegados
  - 📞 **Contactado**: Ya se estableció contacto
  - ⚙️ **En Proceso**: Consulta en desarrollo
  - ✅ **Cerrado**: Consulta finalizada

- **Niveles de Prioridad**:
  - 🔴 **Urgente**: Requiere atención inmediata
  - 🟠 **Alta**: Prioridad alta
  - 🟡 **Media**: Prioridad media (por defecto para cotizaciones)
  - 🟢 **Baja**: Prioridad baja (por defecto para consultas generales)

### 4. Herramientas de Filtrado y Búsqueda
- **Filtros Múltiples**: Por estado, tipo de consulta, prioridad
- **Búsqueda de Texto**: Buscar en nombre, email, asunto o mensaje
- **Aplicación en Tiempo Real**: Los filtros se aplican automáticamente
- **Exportación a CSV**: Descargar contactos filtrados para análisis externo

### 5. Gestión Individual de Contactos
- **Vista Detallada**: Información completa del contacto en panel lateral
- **Edición Rápida**: Cambiar estado, prioridad, asignar a usuario, agregar notas
- **Timeline**: Seguimiento de fechas importantes (creación, contacto, cierre)
- **Acciones Directas**: Enlaces para llamar, enviar email o WhatsApp

## 📁 Estructura de Archivos Agregados/Modificados

### Nuevos Archivos
- `api/contactos.php` - API REST para gestión de contactos
- `admin/contactos.php` - Panel de administración del CRM

### Archivos Modificados
- `database/schema.sql` - Nueva tabla de contactos e índices
- `js/contacto.js` - Integración con API para envío de formularios
- `admin/index.php` - Estadísticas y enlaces al CRM

## 🛠️ Instalación y Configuración

### 1. Base de Datos
Ejecutar el script SQL actualizado para crear la tabla de contactos:

```sql
-- Ejecutar desde database/schema.sql
-- La tabla 'contactos' se creará automáticamente
```

### 2. Verificar Permisos
- Los usuarios administradores pueden ver, editar y eliminar contactos
- Los usuarios editores pueden ver y editar, pero no eliminar

### 3. Configuración de Email (Opcional)
Para respuestas automáticas, configurar SMTP en `database/config.php`

## 📊 Uso del Sistema

### Acceso al CRM
1. Ir al panel de administración (`admin/`)
2. Hacer login con credenciales de administrador
3. Hacer clic en "Contactos CRM" en el menú lateral

### Gestión de Contactos
1. **Ver Lista**: Los contactos aparecen ordenados por fecha (más recientes primero)
2. **Seleccionar Contacto**: Clic en cualquier contacto para ver detalles
3. **Editar**: Usar el botón de edición para cambiar estado, prioridad, asignar o agregar notas
4. **Filtrar**: Usar los filtros superiores para encontrar contactos específicos
5. **Exportar**: Descargar datos filtrados en formato CSV

### Estados y Flujo de Trabajo Recomendado
1. **Nuevo** → **Contactado**: Cuando se establece primer contacto con el cliente
2. **Contactado** → **En Proceso**: Cuando se inicia trabajo en la consulta/cotización
3. **En Proceso** → **Cerrado**: Cuando se finaliza la gestión (venta realizada o consulta resuelta)

## 🔧 Funcionalidades Técnicas

### API Endpoints
- `GET /api/contactos.php` - Listar contactos con filtros y paginación
- `POST /api/contactos.php` - Crear nuevo contacto
- `PUT /api/contactos.php?id=X` - Actualizar contacto existente
- `DELETE /api/contactos.php?id=X` - Eliminar contacto
- `GET /api/contactos.php?export=csv` - Exportar a CSV

### Seguridad
- Autenticación requerida para todas las operaciones de gestión
- Sanitización de datos de entrada
- Protección contra SQL injection
- Logs de actividad para auditoría

### Campos de la Base de Datos
```sql
contactos:
- id (clave primaria)
- tipo_consulta (cotización, consulta, felicitación, reclamo, etc.)
- nombre, email, telefono, ciudad
- asunto, mensaje
- presupuesto, plazo, como_nos_conocio
- preferencias_contacto (JSON)
- newsletter (boolean)
- estado, prioridad
- notas_admin, asignado_a
- fechas de seguimiento
- datos técnicos (IP, user agent)
```

## 📈 Beneficios del Sistema

1. **Organización**: Todos los contactos centralizados y organizados
2. **Seguimiento**: Control total del estado de cada consulta
3. **Priorización**: Gestión eficiente basada en importancia y urgencia
4. **Análisis**: Estadísticas y exportación para tomar decisiones
5. **Productividad**: Herramientas que agilizan la gestión diaria
6. **Profesionalismo**: Sistema estructurado que mejora la atención al cliente

## 🔄 Flujo de Trabajo Típico

1. **Cliente envía consulta** → Sistema registra automáticamente
2. **Notificación en dashboard** → Aparece en "Contactos Nuevos"
3. **Revisión y clasificación** → Asignar prioridad y responsable
4. **Contacto inicial** → Cambiar estado a "Contactado"
5. **Desarrollo de la consulta** → Estado "En Proceso" con notas
6. **Cierre** → Estado "Cerrado" con resultado final

## 📞 Soporte

Para dudas sobre el funcionamiento del sistema, consultar:
- Documentación técnica en los archivos PHP
- Logs de actividad en `admin/logs.php`
- Panel de administración para gestión completa

---

**JC3Design Mini CRM** - Sistema de gestión de contactos integrado
*Versión 1.0 - Implementado en 2025*
