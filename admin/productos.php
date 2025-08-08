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

// Obtener productos con información de categoría
$productos = $db->fetchAll("
    SELECT p.*, c.nombre as categoria_nombre 
    FROM productos p 
    JOIN categorias c ON p.categoria_id = c.id 
    WHERE p.activo = 1 
    ORDER BY p.created_at DESC
");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestionar Productos - Panel de Administración</title>
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
                        <a class="nav-link active" href="productos.php">
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
                            <h2 class="mb-0">Gestionar Productos</h2>
                            <p class="text-muted mb-0">Administra el catálogo de productos</p>
                        </div>
                        <a href="nuevo_producto.php" class="btn btn-success">
                            <i class="fas fa-plus mr-2"></i>Nuevo Producto
                        </a>
                    </div>
                    
                    <!-- Tabla de Productos -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-box mr-2"></i>Lista de Productos
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($productos)): ?>
                                <p class="text-muted">No hay productos registrados</p>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Producto</th>
                                                <th>Categoría</th>
                                                <th>Estado</th>
                                                <th>Destacado</th>
                                                <th>Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($productos as $producto): ?>
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
                                                        <?php if ($producto['activo']): ?>
                                                            <span class="badge badge-success">Activo</span>
                                                        <?php else: ?>
                                                            <span class="badge badge-danger">Inactivo</span>
                                                        <?php endif; ?>
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
                                                        <button class="btn btn-sm btn-outline-danger" onclick="eliminarProducto(<?php echo $producto['id']; ?>)">
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
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.4.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.4.1/dist/js/bootstrap.min.js"></script>
    <script src="../js/dark-mode.js"></script>
    
    <script>
        function eliminarProducto(id) {
            if (confirm('¿Estás seguro de que quieres eliminar este producto?')) {
                // Aquí iría la lógica para eliminar el producto
                alert('Funcionalidad de eliminación en desarrollo');
            }
        }
    </script>
</body>
</html>
