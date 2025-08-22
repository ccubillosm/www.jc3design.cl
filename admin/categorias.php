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
    <title>Gestionar Categorías - Panel de Administración</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.4.1/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../css/dark-mode.css">
    <link rel="stylesheet" href="../css/admin-dark-mode.css">
    <link rel="stylesheet" href="../css/chart-dark-mode.css">
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
                        <a class="nav-link" href="dashboard.php">
                            <i class="fas fa-chart-line mr-2"></i>Dashboard de Ventas
                        </a>
                        <a class="nav-link" href="index.php">
                            <i class="fas fa-tachometer-alt mr-2"></i>Panel Principal
                        </a>
                        <a class="nav-link" href="productos.php">
                            <i class="fas fa-box mr-2"></i>Productos
                        </a>
                        <a class="nav-link active" href="categorias.php">
                            <i class="fas fa-tags mr-2"></i>Categorías
                        </a>
                        <a class="nav-link" href="imagenes.php">
                            <i class="fas fa-images mr-2"></i>Imágenes
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
                            <h2 class="mb-0">Gestionar Categorías</h2>
                            <p class="text-muted mb-0">Administra las categorías de productos</p>
                            <a href="dashboard.php" class="btn btn-outline-primary btn-sm mt-2">
                                <i class="fas fa-chart-line mr-1"></i>Ver Dashboard de Ventas
                            </a>
                        </div>
                        <a href="nueva_categoria.php" class="btn btn-success">
                            <i class="fas fa-plus mr-2"></i>Nueva Categoría
                        </a>
                    </div>
                    
                    <!-- Tabla de Categorías -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-tags mr-2"></i>Lista de Categorías
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($categorias)): ?>
                                <p class="text-muted">No hay categorías registradas</p>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Nombre</th>
                                                <th>Slug</th>
                                                <th>Productos</th>
                                                <th>Orden</th>
                                                <th>Estado</th>
                                                <th>Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($categorias as $categoria): ?>
                                                <tr>
                                                    <td>
                                                        <strong><?php echo htmlspecialchars($categoria['nombre']); ?></strong>
                                                        <br>
                                                        <small class="text-muted"><?php echo htmlspecialchars($categoria['descripcion']); ?></small>
                                                    </td>
                                                    <td>
                                                        <code><?php echo htmlspecialchars($categoria['slug']); ?></code>
                                                    </td>
                                                    <td>
                                                        <span class="badge badge-info"><?php echo $categoria['total_productos']; ?> productos</span>
                                                    </td>
                                                    <td>
                                                        <span class="badge badge-secondary"><?php echo $categoria['orden']; ?></span>
                                                    </td>
                                                    <td>
                                                        <?php if ($categoria['activo']): ?>
                                                            <span class="badge badge-success">Activa</span>
                                                        <?php else: ?>
                                                            <span class="badge badge-danger">Inactiva</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <a href="editar_categoria.php?id=<?php echo $categoria['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <button class="btn btn-sm btn-outline-danger" onclick="eliminarCategoria(<?php echo $categoria['id']; ?>, <?php echo $categoria['total_productos']; ?>)">
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
        function eliminarCategoria(id, totalProductos) {
            if (totalProductos > 0) {
                mostrarAlerta('No se puede eliminar una categoría que tiene productos asociados. Primero mueve o elimina los productos.', 'warning');
                return;
            }
            
            if (confirm('¿Estás seguro de que quieres eliminar esta categoría? Esta acción no se puede deshacer.')) {
                // Mostrar indicador de carga
                const btn = event.target.closest('button');
                const originalContent = btn.innerHTML;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                btn.disabled = true;
                
                fetch(`../api/categorias.php?id=${id}`, {
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
                            mostrarAlerta('Categoría eliminada correctamente', 'success');
                        }, 500);
                    } else {
                        throw new Error(data.error || 'Error al eliminar categoría');
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
    </script>
</body>
</html>
