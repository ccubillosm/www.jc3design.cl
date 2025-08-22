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

// Obtener productos con información de imágenes
$productos = $db->fetchAll("
    SELECT p.*, c.nombre as categoria_nombre,
           (SELECT COUNT(*) FROM producto_imagenes WHERE producto_id = p.id) as total_imagenes
    FROM productos p 
    JOIN categorias c ON p.categoria_id = c.id 
    WHERE p.activo = 1 
    ORDER BY p.nombre
");

// Procesar formulario de subida
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $producto_id = $_POST['producto_id'] ?? null;
    $imagen_url = $_POST['imagen_url'] ?? '';
    $imagen_alt = $_POST['imagen_alt'] ?? '';
    $orden = (int)($_POST['orden'] ?? 0);
    $principal = isset($_POST['principal']) ? 1 : 0;
    
    if ($producto_id && $imagen_url) {
        try {
            // Si se marca como principal, desmarcar las demás
            if ($principal) {
                $db->query("UPDATE producto_imagenes SET principal = 0 WHERE producto_id = ?", [$producto_id]);
            }
            
            $data = [
                'producto_id' => $producto_id,
                'imagen' => $imagen_url,
                'imagen_alt' => $imagen_alt,
                'orden' => $orden,
                'principal' => $principal
            ];
            
            $imagen_id = $db->insert('producto_imagenes', $data);
            
            // Registrar log
            logActivity($_SESSION['user_id'], 'crear', 'producto_imagenes', $imagen_id, null, $data);
            
            $mensaje = "Imagen agregada exitosamente";
            $tipo_mensaje = "success";
        } catch (Exception $e) {
            $mensaje = "Error al agregar imagen: " . $e->getMessage();
            $tipo_mensaje = "danger";
        }
    }
}

// Eliminar imagen
if (isset($_GET['eliminar'])) {
    $imagen_id = (int)$_GET['eliminar'];
    try {
        $db->query("DELETE FROM producto_imagenes WHERE id = ?", [$imagen_id]);
        logActivity($_SESSION['user_id'], 'eliminar', 'producto_imagenes', $imagen_id);
        $mensaje = "Imagen eliminada exitosamente";
        $tipo_mensaje = "success";
    } catch (Exception $e) {
        $mensaje = "Error al eliminar imagen: " . $e->getMessage();
        $tipo_mensaje = "danger";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestionar Imágenes - Panel de Administración</title>
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
        .product-image {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 8px;
        }
        .image-preview {
            max-width: 200px;
            max-height: 200px;
            object-fit: contain;
            border: 2px solid #ddd;
            border-radius: 8px;
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
                        <a class="nav-link active" href="imagenes.php">
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
            
            <!-- Contenido principal -->
            <div class="col-md-9 col-lg-10">
                <div class="admin-content p-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h2 class="mb-0"><i class="fas fa-images mr-2"></i>Gestionar Imágenes de Productos</h2>
                            <a href="dashboard.php" class="btn btn-outline-primary btn-sm mt-2">
                                <i class="fas fa-chart-line mr-1"></i>Ver Dashboard de Ventas
                            </a>
                        </div>
                        <button class="btn btn-primary" data-toggle="modal" data-target="#agregarImagenModal">
                            <i class="fas fa-plus mr-2"></i>Agregar Imagen
                        </button>
                    </div>
                    
                    <?php if (isset($mensaje)): ?>
                        <div class="alert alert-<?php echo $tipo_mensaje; ?> alert-dismissible fade show" role="alert">
                            <?php echo htmlspecialchars($mensaje); ?>
                            <button type="button" class="close" data-dismiss="alert">
                                <span>&times;</span>
                            </button>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Lista de productos con imágenes -->
                    <div class="row">
                        <?php foreach ($productos as $producto): ?>
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card h-100">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <h6 class="mb-0"><?php echo htmlspecialchars($producto['nombre']); ?></h6>
                                        <span class="badge badge-info"><?php echo $producto['total_imagenes']; ?> imágenes</span>
                                    </div>
                                    <div class="card-body">
                                        <p class="text-muted small mb-2">
                                            <i class="fas fa-tag mr-1"></i><?php echo htmlspecialchars($producto['categoria_nombre']); ?>
                                        </p>
                                        
                                        <!-- Imagen principal -->
                                        <div class="text-center mb-3">
                                            <img src="../<?php echo htmlspecialchars($producto['imagen']); ?>" 
                                                 alt="<?php echo htmlspecialchars($producto['nombre']); ?>" 
                                                 class="product-image"
                                                 onerror="this.src='../images/logo.png'">
                                            <p class="text-muted small mt-1">Imagen Principal</p>
                                        </div>
                                        
                                        <!-- Imágenes adicionales -->
                                        <?php
                                        $imagenes_adicionales = $db->fetchAll(
                                            "SELECT * FROM producto_imagenes WHERE producto_id = ? ORDER BY orden, principal DESC",
                                            [$producto['id']]
                                        );
                                        ?>
                                        
                                        <?php if (!empty($imagenes_adicionales)): ?>
                                            <div class="mb-3">
                                                <h6 class="text-muted">Imágenes Adicionales:</h6>
                                                <div class="row">
                                                    <?php foreach ($imagenes_adicionales as $imagen): ?>
                                                        <div class="col-4 mb-2">
                                                            <div class="position-relative">
                                                                <img src="../<?php echo htmlspecialchars($imagen['imagen']); ?>" 
                                                                     alt="<?php echo htmlspecialchars($imagen['imagen_alt']); ?>" 
                                                                     class="product-image"
                                                                     onerror="this.src='../images/logo.png'">
                                                                <a href="?eliminar=<?php echo $imagen['id']; ?>" 
                                                                   class="btn btn-sm btn-danger position-absolute" 
                                                                   style="top: -5px; right: -5px;"
                                                                   onclick="return confirm('¿Eliminar esta imagen?')">
                                                                    <i class="fas fa-times"></i>
                                                                </a>
                                                                <?php if ($imagen['principal']): ?>
                                                                    <span class="badge badge-success position-absolute" 
                                                                          style="bottom: 5px; left: 5px;">Principal</span>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        <?php else: ?>
                                            <p class="text-muted small">No hay imágenes adicionales</p>
                                        <?php endif; ?>
                                        
                                        <button class="btn btn-outline-primary btn-sm w-100" 
                                                onclick="agregarImagenParaProducto(<?php echo $producto['id']; ?>, '<?php echo htmlspecialchars($producto['nombre']); ?>')">
                                            <i class="fas fa-plus mr-1"></i>Agregar Imagen
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal para agregar imagen -->
    <div class="modal fade" id="agregarImagenModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Agregar Imagen</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="producto_id">Producto</label>
                            <select class="form-control" id="producto_id" name="producto_id" required>
                                <option value="">Seleccionar producto...</option>
                                <?php foreach ($productos as $producto): ?>
                                    <option value="<?php echo $producto['id']; ?>">
                                        <?php echo htmlspecialchars($producto['nombre']); ?> 
                                        (<?php echo htmlspecialchars($producto['categoria_nombre']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="imagen_url">URL de la imagen</label>
                            <input type="text" class="form-control" id="imagen_url" name="imagen_url" 
                                   placeholder="images/nombre_imagen.jpg" required>
                            <small class="form-text text-muted">
                                Ruta relativa desde la carpeta images/ (ej: images/producto_vista1.jpg)
                            </small>
                        </div>
                        
                        <div class="form-group">
                            <label for="imagen_alt">Texto alternativo</label>
                            <input type="text" class="form-control" id="imagen_alt" name="imagen_alt" 
                                   placeholder="Descripción de la imagen">
                        </div>
                        
                        <div class="form-group">
                            <label for="orden">Orden</label>
                            <input type="number" class="form-control" id="orden" name="orden" value="0" min="0">
                        </div>
                        
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="principal" name="principal">
                            <label class="form-check-label" for="principal">
                                Marcar como imagen principal
                            </label>
                        </div>
                        
                        <div class="mt-3">
                            <img id="preview" class="image-preview d-none" alt="Vista previa">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Agregar Imagen</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.4.1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/dark-mode.js"></script>
    <script>
        // Vista previa de imagen
        document.getElementById('imagen_url').addEventListener('input', function() {
            const url = this.value;
            const preview = document.getElementById('preview');
            
            if (url) {
                preview.src = '../' + url;
                preview.classList.remove('d-none');
            } else {
                preview.classList.add('d-none');
            }
        });
        
        // Función para agregar imagen para un producto específico
        function agregarImagenParaProducto(productoId, productoNombre) {
            document.getElementById('producto_id').value = productoId;
            $('#agregarImagenModal').modal('show');
        }
    </script>
</body>
</html>
