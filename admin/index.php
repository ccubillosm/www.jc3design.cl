<?php
/**
 * Panel de Administración - JC3Design
 * Gestión de productos y categorías
 */

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

// Obtener estadísticas
$total_productos = $db->fetchOne("SELECT COUNT(*) as total FROM productos WHERE activo = 1")['total'];
$total_categorias = $db->fetchOne("SELECT COUNT(*) as total FROM categorias WHERE activo = 1")['total'];
$productos_destacados = $db->fetchOne("SELECT COUNT(*) as total FROM productos WHERE destacado = 1 AND activo = 1")['total'];
$total_contactos = $db->fetchOne("SELECT COUNT(*) as total FROM contactos")['total'] ?? 0;
$contactos_nuevos = $db->fetchOne("SELECT COUNT(*) as total FROM contactos WHERE estado = 'nuevo'")['total'] ?? 0;

// Obtener productos recientes
$productos_recientes = $db->fetchAll("
    SELECT p.*, c.nombre as categoria_nombre 
    FROM productos p 
    JOIN categorias c ON p.categoria_id = c.id 
    WHERE p.activo = 1 
    ORDER BY p.created_at DESC 
    LIMIT 5
");

// Obtener categorías con conteo de productos
$categorias = $db->fetchAll("
    SELECT c.*, COUNT(p.id) as total_productos 
    FROM categorias c 
    LEFT JOIN productos p ON c.id = p.categoria_id AND p.activo = 1 
    WHERE c.activo = 1 
    GROUP BY c.id 
    ORDER BY c.orden, c.nombre
");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración - JC3Design</title>
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
        .stat-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        .stat-card:hover {
            transform: translateY(-5px);
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
                        <a class="nav-link active" href="index.php">
                            <i class="fas fa-tachometer-alt mr-2"></i>Dashboard
                        </a>
                        <a class="nav-link" href="productos.php">
                            <i class="fas fa-box mr-2"></i>Productos
                        </a>
                        <a class="nav-link" href="imagenes.php">
                            <i class="fas fa-images mr-2"></i>Imágenes
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
                            <h1 class="h3 mb-0">Dashboard</h1>
                            <p class="text-muted">Bienvenido al panel de administración</p>
                        </div>
                        <div class="text-right">
                            <p class="mb-0"><strong>Usuario:</strong> <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Admin'); ?></p>
                            <small class="text-muted">Último acceso: <?php echo date('d/m/Y H:i'); ?></small>
                        </div>
                    </div>
                    
                    <!-- Estadísticas -->
                    <div class="row mb-4">
                        <div class="col-md-3 col-lg-2-4 mb-3">
                            <div class="stat-card p-4 text-center">
                                <i class="fas fa-box fa-3x text-primary mb-3"></i>
                                <h3 class="text-primary"><?php echo $total_productos; ?></h3>
                                <p class="text-muted mb-0">Productos Activos</p>
                            </div>
                        </div>
                        <div class="col-md-3 col-lg-2-4 mb-3">
                            <div class="stat-card p-4 text-center">
                                <i class="fas fa-tags fa-3x text-success mb-3"></i>
                                <h3 class="text-success"><?php echo $total_categorias; ?></h3>
                                <p class="text-muted mb-0">Categorías</p>
                            </div>
                        </div>
                        <div class="col-md-3 col-lg-2-4 mb-3">
                            <div class="stat-card p-4 text-center">
                                <i class="fas fa-star fa-3x text-warning mb-3"></i>
                                <h3 class="text-warning"><?php echo $productos_destacados; ?></h3>
                                <p class="text-muted mb-0">Productos Destacados</p>
                            </div>
                        </div>
                        <div class="col-md-3 col-lg-2-4 mb-3">
                            <div class="stat-card p-4 text-center">
                                <i class="fas fa-address-book fa-3x text-info mb-3"></i>
                                <h3 class="text-info"><?php echo $total_contactos; ?></h3>
                                <p class="text-muted mb-0">Total Contactos</p>
                            </div>
                        </div>
                        <div class="col-md-3 col-lg-2-4 mb-3">
                            <div class="stat-card p-4 text-center">
                                <i class="fas fa-bell fa-3x text-danger mb-3"></i>
                                <h3 class="text-danger"><?php echo $contactos_nuevos; ?></h3>
                                <p class="text-muted mb-0">Contactos Nuevos</p>
                                <?php if ($contactos_nuevos > 0): ?>
                                    <a href="contactos.php" class="btn btn-sm btn-outline-danger mt-2">Ver</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Productos Recientes -->
                    <div class="row">
                        <div class="col-md-8">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-clock mr-2"></i>Productos Recientes
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($productos_recientes)): ?>
                                        <p class="text-muted">No hay productos recientes</p>
                                    <?php else: ?>
                                        <div class="table-responsive">
                                            <table class="table table-hover">
                                                <thead>
                                                    <tr>
                                                        <th>Producto</th>
                                                        <th>Categoría</th>
                                                        <th>Estado</th>
                                                        <th>Acciones</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($productos_recientes as $producto): ?>
                                                        <tr>
                                                            <td>
                                                                <strong><?php echo htmlspecialchars($producto['nombre']); ?></strong>
                                                                <br>
                                                                <small class="text-muted"><?php echo htmlspecialchars($producto['codigo']); ?></small>
                                                            </td>
                                                            <td>
                                                                <span class="badge badge-info"><?php echo htmlspecialchars($producto['categoria_nombre']); ?></span>
                                                            </td>
                                                            <td>
                                                                <?php if ($producto['destacado']): ?>
                                                                    <span class="badge badge-warning">Destacado</span>
                                                                <?php else: ?>
                                                                    <span class="badge badge-secondary">Normal</span>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td>
                                                                <a href="editar_producto.php?id=<?php echo $producto['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                                    <i class="fas fa-edit"></i>
                                                                </a>
                                                                <a href="../pag/producto.html?id=<?php echo $producto['id']; ?>" target="_blank" class="btn btn-sm btn-outline-info">
                                                                    <i class="fas fa-eye"></i>
                                                                </a>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-tags mr-2"></i>Categorías
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($categorias)): ?>
                                        <p class="text-muted">No hay categorías</p>
                                    <?php else: ?>
                                        <ul class="list-unstyled">
                                            <?php foreach ($categorias as $categoria): ?>
                                                <li class="mb-2">
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <span><?php echo htmlspecialchars($categoria['nombre']); ?></span>
                                                        <span class="badge badge-light"><?php echo $categoria['total_productos']; ?></span>
                                                    </div>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                        <a href="categorias.php" class="btn btn-primary btn-sm">
                                            <i class="fas fa-plus mr-1"></i>Gestionar Categorías
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <!-- Acciones Rápidas -->
                            <div class="card mt-3">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-bolt mr-2"></i>Acciones Rápidas
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="d-grid gap-2">
                                        <a href="nuevo_producto.php" class="btn btn-success btn-block mb-2">
                                            <i class="fas fa-plus mr-2"></i>Nuevo Producto
                                        </a>
                                        <a href="nueva_categoria.php" class="btn btn-info btn-block mb-2">
                                            <i class="fas fa-tag mr-2"></i>Nueva Categoría
                                        </a>
                                        <a href="contactos.php" class="btn btn-primary btn-block mb-2">
                                            <i class="fas fa-address-book mr-2"></i>Ver Contactos CRM
                                        </a>
                                        <a href="backup.php" class="btn btn-warning btn-block">
                                            <i class="fas fa-download mr-2"></i>Backup
                                        </a>
                                    </div>
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
</body>
</html>
