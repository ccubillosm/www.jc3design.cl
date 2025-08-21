# Sistema de Backups - JC3Design

## 📁 Estructura de Directorios

```
backups/
├── README.md                    # Este archivo
├── jc3design_backup_*.sql      # Backups manuales
├── jc3design_backup_*.sql.gz   # Backups automáticos comprimidos
└── restore/                     # Archivos para restauración
```

## 🔧 Funcionalidades Disponibles

### **1. Backup Manual**
- **Ubicación**: Panel de Administración → Backup
- **Función**: Crear backup completo de la base de datos
- **Formato**: Archivo SQL sin comprimir
- **Nomenclatura**: `jc3design_backup_YYYY-MM-DD_HH-MM-SS.sql`

### **2. Backup Automático**
- **Script**: `scripts/backup_automatico.php`
- **Función**: Backup programado con cron
- **Compresión**: Automática para archivos > 1MB
- **Limpieza**: Mantiene solo los últimos 30 backups

### **3. Restauración**
- **Ubicación**: Panel de Administración → Backup → Restaurar
- **Formatos Soportados**: `.sql`, `.gz`, `.zip`
- **Límite**: Máximo 50MB por archivo
- **⚠️ Precaución**: Sobrescribe la base de datos actual

## 🚀 Configuración de Cron

### **Backup Diario (2:00 AM)**
```bash
0 2 * * * /usr/bin/php /ruta/a/tu/proyecto/scripts/backup_automatico.php
```

### **Backup Semanal (Domingos 3:00 AM)**
```bash
0 3 * * 0 /usr/bin/php /ruta/a/tu/proyecto/scripts/backup_automatico.php
```

### **Verificar Cron Activo**
```bash
crontab -l
```

## 📊 Estadísticas de la Base de Datos

El panel muestra en tiempo real:
- **Tamaño de la BD**: Espacio ocupado en MB
- **Número de Tablas**: Total de tablas en la base de datos
- **Total de Registros**: Suma de todos los registros

## 🛡️ Seguridad

### **Validaciones Implementadas**
- ✅ Solo usuarios autenticados pueden acceder
- ✅ Solo administradores pueden crear/eliminar backups
- ✅ Validación de tipos de archivo para restauración
- ✅ Límite de tamaño de archivo (50MB)
- ✅ Logs de auditoría para todas las operaciones

### **Logs de Auditoría**
- **Creación de backup**: Quién, cuándo, nombre del archivo
- **Eliminación de backup**: Quién, cuándo, archivo eliminado
- **Subida de archivo**: Quién, cuándo, archivo subido

## 📋 Recomendaciones

### **Frecuencia de Backups**
- **Desarrollo**: Diario o semanal
- **Producción**: Diario (mínimo)
- **Crítico**: Cada 6-12 horas

### **Almacenamiento**
- **Local**: Para desarrollo y pruebas
- **Remoto**: Para producción (Google Drive, Dropbox, AWS S3)
- **Múltiples ubicaciones**: Nunca confíes en una sola ubicación

### **Pruebas**
- **Restauración**: Prueba mensualmente en entorno de desarrollo
- **Verificación**: Confirma que los datos se restauran correctamente
- **Documentación**: Mantén un registro de las restauraciones exitosas

## 🔍 Solución de Problemas

### **Error: "mysqldump no encontrado"**
```bash
# En Ubuntu/Debian
sudo apt-get install mysql-client

# En CentOS/RHEL
sudo yum install mysql

# En macOS
brew install mysql-client
```

### **Error: "Permisos denegados"**
```bash
# Verificar permisos del directorio
ls -la backups/

# Cambiar permisos si es necesario
chmod 755 backups/
chown www-data:www-data backups/  # En Linux
```

### **Error: "Espacio insuficiente"**
```bash
# Verificar espacio disponible
df -h

# Limpiar backups antiguos manualmente
rm backups/jc3design_backup_*.sql
```

## 📞 Soporte

Si encuentras problemas:
1. Revisa los logs en `logs/backup.log`
2. Verifica los permisos de archivos y directorios
3. Confirma que `mysqldump` esté instalado
4. Verifica la configuración de la base de datos

## 📝 Notas Importantes

- **Backups automáticos**: Se comprimen automáticamente si son > 1MB
- **Limpieza automática**: Solo se mantienen los últimos 30 backups
- **Restauración**: Siempre haz un backup antes de restaurar
- **Pruebas**: Nunca restaures en producción sin probar antes

---

**Última actualización**: <?php echo date('Y-m-d H:i:s'); ?>
**Versión**: 1.0.0
