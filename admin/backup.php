<?php
require_once '../database/config.php';

// Verificar autenticación
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isAuthenticated() || !isAdmin()) {
    header('Location: login.php');
    exit;
}

$db = getDB();
$error = '';
$success = '';

// Procesar backup manual
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'backup_manual') {
        try {
            $backup_dir = '../backups/';
            if (!is_dir($backup_dir)) {
                mkdir($backup_dir, 0755, true);
            }
            
            $timestamp = date('Y-m-d_H-i-s');
            $filename = "jc3design_backup_{$timestamp}.sql";
            $filepath = $backup_dir . $filename;
            
            // Crear backup usando mysqldump
            $command = sprintf(
                'mysqldump --host=%s --user=%s --password=%s %s > %s',
                escapeshellarg(DB_HOST),
                escapeshellarg(DB_USER),
                escapeshellarg(DB_PASS),
                escapeshellarg(DB_NAME),
                escapeshellarg($filepath)
            );
            
            exec($command, $output, $return_var);
            
            if ($return_var === 0 && file_exists($filepath)) {
                // Registrar en logs
                logActivity($_SESSION['user_id'], 'backup_manual', 'sistema', null, null, ['archivo' => $filename]);
                
                $success = "Backup creado exitosamente: {$filename}";
            } else {
                throw new Exception('Error al ejecutar mysqldump');
            }
            
        } catch (Exception $e) {
            $error = 'Error al crear backup: ' . $e->getMessage();
        }
    }
    
    // Restaurar backup
    if ($_POST['action'] === 'restore' && isset($_FILES['backup_file'])) {
        try {
            $file = $_FILES['backup_file'];
            
            if ($file['error'] !== UPLOAD_ERR_OK) {
                throw new Exception('Error al subir archivo');
            }
            
            if ($file['size'] > 50 * 1024 * 1024) { // 50MB max
                throw new Exception('Archivo demasiado grande (máximo 50MB)');
            }
            
            $allowed_extensions = ['sql', 'gz', 'zip'];
            $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            
            if (!in_array($file_extension, $allowed_extensions)) {
                throw new Exception('Tipo de archivo no permitido. Solo se permiten: ' . implode(', ', $allowed_extensions));
            }
            
            $upload_dir = '../backups/restore/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $upload_path = $upload_dir . $file['name'];
            
            if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
                throw new Exception('Error al mover archivo subido');
            }
            
            // Registrar en logs
            logActivity($_SESSION['user_id'], 'restore_upload', 'sistema', null, null, ['archivo' => $file['name']]);
            
            $success = "Archivo de backup subido exitosamente: {$file['name']}";
            
        } catch (Exception $e) {
            $error = 'Error al subir backup: ' . $e->getMessage();
        }
    }
}

// Obtener archivos de backup existentes
$backup_dir = '../backups/';
$backups = [];
if (is_dir($backup_dir)) {
    $files = glob($backup_dir . '*.sql');
    foreach ($files as $file) {
        $backups[] = [
            'filename' => basename($file),
            'size' => filesize($file),
            'modified' => filemtime($file),
            'path' => $file
        ];
    }
    
    // Ordenar por fecha de modificación (más reciente primero)
    usort($backups, function($a, $b) {
        return $b['modified'] - $a['modified'];
    });
}

// Obtener estadísticas de la base de datos
$stats = [];
try {
    // Tamaño de la base de datos
    $db_size = $db->fetchOne("SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size FROM information_schema.tables WHERE table_schema = ?", [DB_NAME]);
    $stats['db_size'] = $db_size['size'] ?? 0;
    
    // Número de tablas
    $tables = $db->fetchOne("SELECT COUNT(*) as total FROM information_schema.tables WHERE table_schema = ?", [DB_NAME]);
    $stats['tables'] = $tables['total'] ?? 0;
    
    // Total de registros
    $total_records = $db->fetchOne("SELECT SUM(table_rows) as total FROM information_schema.tables WHERE table_schema = ?", [DB_NAME]);
    $stats['records'] = $total_records['total'] ?? 0;
    
} catch (Exception $e) {
    $stats = ['error' => $e->getMessage()];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Backup y Restauración - Panel de Administración</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.4.1/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../css/dark-mode.css">
    <style>
        .admin-sidebar {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .admin-content {
            min-height: 100vh;
            background-color: #f8f9fa;
        }
        .nav-link {
            color: rgba(255,255,255,0.8);
            transition: all 0.3s ease;
        }
        .nav-link:hover {
            color: white;
            background-color: rgba(255,255,255,0.1);
        }
        .nav-link.active {
            color: white;
            background-color: rgba(255,255,255,0.2);
        }
        .backup-card {
            transition: transform 0.2s ease;
        }
        .backup-card:hover {
            transform: translateY(-2px);
        }
        .stats-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
    </style>
</head>

<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 px-0">
                <div class="admin-sidebar p-3">
                    <div class="text-center mb-4">
                        <h4 class="text-white">JC3Design</h4>
                        <p class="text-white-50 small">Panel de Administración</p>
                    </div>
                    
                    <nav class="nav flex-column">
                        <a class="nav-link" href="index.php">
                            <i class="fas fa-tachometer-alt mr-2"></i>Dashboard
                        </a>
                        <a class="nav-link" href="productos.php">
                            <i class="fas fa-box mr-2"></i>Productos
                        </a>
                        <a class="nav-link" href="categorias.php">
                            <i class="fas fa-tags mr-2"></i>Categorías
                        </a>
                        <a class="nav-link" href="cotizaciones.php">
                            <i class="fas fa-file-invoice mr-2"></i>Cotizaciones
                        </a>
                        <a class="nav-link" href="contactos.php">
                            <i class="fas fa-address-book mr-2"></i>Contactos CRM
                        </a>
                        <a class="nav-link" href="usuarios.php">
                            <i class="fas fa-users mr-2"></i>Usuarios
                        </a>
                        <a class="nav-link" href="logs.php">
                            <i class="fas fa-history mr-2"></i>Logs
                        </a>
                        <a class="nav-link active" href="backup.php">
                            <i class="fas fa-database mr-2"></i>Backup
                        </a>
                        <hr class="bg-white">
                        <a class="nav-link" href="../index.html" target="_blank">
                            <i class="fas fa-external-link-alt mr-2"></i>Ver Sitio
                        </a>
                        <a class="nav-link" href="logout.php">
                            <i class="fas fa-sign-out-alt mr-2"></i>Cerrar Sesión
                        </a>
                    </nav>
                </div>
            </div>
            
            <!-- Contenido Principal -->
            <div class="col-md-9 col-lg-10">
                <div class="admin-content p-4">
                    <!-- Header -->
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h2 class="mb-0">Backup y Restauración</h2>
                            <p class="text-muted mb-0">Gestiona la seguridad de tu base de datos</p>
                        </div>
                    </div>
                    
                    <!-- Alertas -->
                    <?php if ($error): ?>
                        <div class="alert alert-danger" role="alert">
                            <i class="fas fa-exclamation-triangle mr-2"></i><?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success" role="alert">
                            <i class="fas fa-check-circle mr-2"></i><?php echo htmlspecialchars($success); ?>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Estadísticas de la Base de Datos -->
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <div class="card stats-card">
                                <div class="card-body text-center">
                                    <i class="fas fa-database fa-3x mb-3"></i>
                                    <h4><?php echo $stats['db_size'] ?? 'N/A'; ?> MB</h4>
                                    <p class="mb-0">Tamaño de la BD</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card stats-card">
                                <div class="card-body text-center">
                                    <i class="fas fa-table fa-3x mb-3"></i>
                                    <h4><?php echo $stats['tables'] ?? 'N/A'; ?></h4>
                                    <p class="mb-0">Tablas</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card stats-card">
                                <div class="card-body text-center">
                                    <i class="fas fa-list fa-3x mb-3"></i>
                                    <h4><?php echo number_format($stats['records'] ?? 0); ?></h4>
                                    <p class="mb-0">Registros</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <!-- Crear Backup Manual -->
                        <div class="col-md-6 mb-4">
                            <div class="card backup-card h-100">
                                <div class="card-header bg-primary text-white">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-download mr-2"></i>Crear Backup Manual
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <p class="text-muted">Crea un backup completo de la base de datos en este momento.</p>
                                    
                                    <form method="POST">
                                        <input type="hidden" name="action" value="backup_manual">
                                        <button type="submit" class="btn btn-primary btn-block">
                                            <i class="fas fa-download mr-2"></i>Crear Backup Ahora
                                        </button>
                                    </form>
                                    
                                    <div class="mt-3">
                                        <small class="text-muted">
                                            <i class="fas fa-info-circle mr-1"></i>
                                            El backup se guardará en la carpeta <code>backups/</code>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Restaurar Backup -->
                        <div class="col-md-6 mb-4">
                            <div class="card backup-card h-100">
                                <div class="card-header bg-warning text-dark">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-upload mr-2"></i>Restaurar Backup
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <p class="text-muted">Sube un archivo de backup para restaurar la base de datos.</p>
                                    
                                    <form method="POST" enctype="multipart/form-data">
                                        <input type="hidden" name="action" value="restore">
                                        <div class="form-group">
                                            <div class="custom-file">
                                                <input type="file" class="custom-file-input" id="backup_file" name="backup_file" accept=".sql,.gz,.zip" required>
                                                <label class="custom-file-label" for="backup_file">Seleccionar archivo...</label>
                                            </div>
                                        </div>
                                        
                                        <button type="submit" class="btn btn-warning btn-block">
                                            <i class="fas fa-upload mr-2"></i>Subir y Restaurar
                                        </button>
                                    </form>
                                    
                                    <div class="mt-3">
                                        <small class="text-muted">
                                            <i class="fas fa-exclamation-triangle mr-1"></i>
                                            <strong>¡Cuidado!</strong> Esto sobrescribirá la base de datos actual
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Lista de Backups Existentes -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-list mr-2"></i>Backups Disponibles
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($backups)): ?>
                                <p class="text-muted">No hay backups disponibles</p>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Archivo</th>
                                                <th>Tamaño</th>
                                                <th>Fecha de Creación</th>
                                                <th>Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($backups as $backup): ?>
                                                <tr>
                                                    <td>
                                                        <strong><?php echo htmlspecialchars($backup['filename']); ?></strong>
                                                    </td>
                                                    <td>
                                                        <span class="badge badge-info">
                                                            <?php echo round($backup['size'] / 1024 / 1024, 2); ?> MB
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <small class="text-muted">
                                                            <?php echo date('d/m/Y H:i:s', $backup['modified']); ?>
                                                        </small>
                                                    </td>
                                                    <td>
                                                        <a href="../backups/<?php echo urlencode($backup['filename']); ?>" 
                                                           class="btn btn-sm btn-outline-primary" download>
                                                            <i class="fas fa-download"></i>
                                                        </a>
                                                        <button class="btn btn-sm btn-outline-danger" 
                                                                onclick="eliminarBackup('<?php echo htmlspecialchars($backup['filename']); ?>')">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Información Adicional -->
                    <div class="row mt-4">
                        <div class="col-md-6">
                            <div class="card border-info">
                                <div class="card-header bg-info text-white">
                                    <h6 class="card-title mb-0">
                                        <i class="fas fa-lightbulb mr-2"></i>Recomendaciones
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <ul class="mb-0">
                                        <li>Realiza backups regulares (diarios o semanales)</li>
                                        <li>Guarda los backups en ubicaciones seguras</li>
                                        <li>Prueba la restauración en un entorno de desarrollo</li>
                                        <li>Mantén múltiples versiones de backup</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card border-warning">
                                <div class="card-header bg-warning text-dark">
                                    <h6 class="card-title mb-0">
                                        <i class="fas fa-exclamation-triangle mr-2"></i>Precauciones
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <ul class="mb-0">
                                        <li>La restauración sobrescribirá datos existentes</li>
                                        <li>Verifica el archivo antes de restaurar</li>
                                        <li>Haz un backup antes de restaurar</li>
                                        <li>Prueba en desarrollo primero</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.4.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.4.1/dist/js/bootstrap.min.js"></script>
    <script src="../js/dark-mode.js"></script>
    
    <script>
        // Actualizar nombre del archivo seleccionado
        document.querySelector('.custom-file-input').addEventListener('change', function(e) {
            var fileName = e.target.files[0]?.name || 'Seleccionar archivo...';
            e.target.nextElementSibling.innerHTML = fileName;
        });
        
        // Función para eliminar backup
        function eliminarBackup(filename) {
            if (confirm(`¿Estás seguro de que quieres eliminar el backup "${filename}"? Esta acción no se puede deshacer.`)) {
                // Mostrar indicador de carga
                const btn = event.target.closest('button');
                const originalContent = btn.innerHTML;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                btn.disabled = true;
                
                fetch(`../api/backup.php?filename=${encodeURIComponent(filename)}`, {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Eliminar fila de la tabla
                        const row = btn.closest('tr');
                        row.style.backgroundColor = '#d4edda';
                        row.style.transition = 'background-color 0.5s';
                        
                        setTimeout(() => {
                            row.remove();
                            // Mostrar mensaje de éxito
                            mostrarAlerta('Backup eliminado correctamente', 'success');
                        }, 500);
                    } else {
                        throw new Error(data.error || 'Error al eliminar backup');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    mostrarAlerta(error.message, 'danger');
                    // Restaurar botón
                    btn.innerHTML = originalContent;
                    btn.disabled = false;
                });
            }
        }
        
        function mostrarAlerta(mensaje, tipo) {
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${tipo} alert-dismissible fade show`;
            alertDiv.innerHTML = `
                ${mensaje}
                <button type="button" class="close" data-dismiss="alert">
                    <span>&times;</span>
                </button>
            `;
            
            // Insertar después del header
            const header = document.querySelector('.d-flex.justify-content-between');
            header.parentNode.insertBefore(alertDiv, header.nextSibling);
            
            // Auto-ocultar después de 5 segundos
            setTimeout(() => {
                if (alertDiv.parentNode) {
                    alertDiv.remove();
                }
            }, 5000);
        }
        
        // Auto-ocultar alertas después de 5 segundos
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                if (alert.parentNode) {
                    alert.remove();
                }
            });
        }, 5000);
    </script>
</body>
</html>
