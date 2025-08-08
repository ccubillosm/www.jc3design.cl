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

// Obtener logs con información de usuario
$logs = $db->fetchAll("
    SELECT l.*, u.username, u.nombre, u.apellido 
    FROM logs l 
    LEFT JOIN usuarios u ON l.usuario_id = u.id 
    ORDER BY l.created_at DESC 
    LIMIT 100
");

// Obtener estadísticas de logs
$total_logs = $db->fetchOne("SELECT COUNT(*) as total FROM logs")['total'];
$logs_hoy = $db->fetchOne("SELECT COUNT(*) as total FROM logs WHERE DATE(created_at) = CURDATE()")['total'];
$logs_semana = $db->fetchOne("SELECT COUNT(*) as total FROM logs WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")['total'];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logs de Actividad - Panel de Administración</title>
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
        .log-entry {
            border-left: 4px solid #007bff;
            padding-left: 15px;
            margin-bottom: 10px;
        }
        .log-entry.login { border-left-color: #28a745; }
        .log-entry.logout { border-left-color: #dc3545; }
        .log-entry.create { border-left-color: #17a2b8; }
        .log-entry.update { border-left-color: #ffc107; }
        .log-entry.delete { border-left-color: #dc3545; }
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
                        <a class="nav-link active" href="logs.php">
                            <i class="fas fa-history mr-2"></i>Logs
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
                            <h2 class="mb-0">Logs de Actividad</h2>
                            <p class="text-muted mb-0">Registro de actividades del sistema</p>
                        </div>
                        <div>
                            <button class="btn btn-outline-secondary" onclick="exportarLogs()">
                                <i class="fas fa-download mr-2"></i>Exportar
                            </button>
                            <button class="btn btn-outline-danger" onclick="limpiarLogs()">
                                <i class="fas fa-trash mr-2"></i>Limpiar
                            </button>
                        </div>
                    </div>
                    
                    <!-- Estadísticas -->
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <div class="card bg-primary text-white">
                                <div class="card-body">
                                    <h5 class="card-title">Total de Logs</h5>
                                    <h3><?php echo $total_logs; ?></h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-success text-white">
                                <div class="card-body">
                                    <h5 class="card-title">Hoy</h5>
                                    <h3><?php echo $logs_hoy; ?></h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-info text-white">
                                <div class="card-body">
                                    <h5 class="card-title">Esta Semana</h5>
                                    <h3><?php echo $logs_semana; ?></h3>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Lista de Logs -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-history mr-2"></i>Actividad Reciente
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($logs)): ?>
                                <p class="text-muted">No hay logs de actividad</p>
                            <?php else: ?>
                                <div class="logs-container">
                                    <?php foreach ($logs as $log): ?>
                                        <div class="log-entry <?php echo $log['accion']; ?>">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div>
                                                    <strong>
                                                        <?php echo htmlspecialchars($log['nombre'] . ' ' . $log['apellido']); ?>
                                                        (<?php echo htmlspecialchars($log['username']); ?>)
                                                    </strong>
                                                    <span class="badge badge-<?php echo getBadgeColor($log['accion']); ?> ml-2">
                                                        <?php echo ucfirst($log['accion']); ?>
                                                    </span>
                                                    <?php if ($log['tabla']): ?>
                                                        <span class="text-muted">en <?php echo $log['tabla']; ?></span>
                                                    <?php endif; ?>
                                                </div>
                                                <small class="text-muted">
                                                    <?php echo date('d/m/Y H:i:s', strtotime($log['created_at'])); ?>
                                                </small>
                                            </div>
                                            <?php if ($log['datos_anteriores'] || $log['datos_nuevos']): ?>
                                                <small class="text-muted">
                                                    <?php if ($log['datos_anteriores']): ?>
                                                        <strong>Antes:</strong> <?php echo htmlspecialchars(substr($log['datos_anteriores'], 0, 100)); ?>
                                                    <?php endif; ?>
                                                    <?php if ($log['datos_nuevos']): ?>
                                                        <strong>Después:</strong> <?php echo htmlspecialchars(substr($log['datos_nuevos'], 0, 100)); ?>
                                                    <?php endif; ?>
                                                </small>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
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
        function exportarLogs() {
            alert('Funcionalidad de exportación en desarrollo');
        }
        
        function limpiarLogs() {
            if (confirm('¿Estás seguro de que quieres limpiar todos los logs? Esta acción no se puede deshacer.')) {
                alert('Funcionalidad de limpieza en desarrollo');
            }
        }
    </script>
</body>
</html>

<?php
function getBadgeColor($accion) {
    switch ($accion) {
        case 'login': return 'success';
        case 'logout': return 'danger';
        case 'create': return 'primary';
        case 'update': return 'warning';
        case 'delete': return 'danger';
        default: return 'secondary';
    }
}
?>
