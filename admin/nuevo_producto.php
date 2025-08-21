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

// Obtener categorías para el select
$categorias = $db->fetchAll("SELECT id, nombre FROM categorias WHERE activo = 1 ORDER BY nombre");

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $categoria_id = (int)($_POST['categoria_id'] ?? 0);
    $codigo = trim($_POST['codigo'] ?? '');
    $nombre = trim($_POST['nombre'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $precio = $_POST['precio'] ? (float)$_POST['precio'] : null;
    $precio_mostrar = trim($_POST['precio_mostrar'] ?? '');
    $dimensiones = trim($_POST['dimensiones'] ?? '');
    $material = trim($_POST['material'] ?? '');
    $peso = trim($_POST['peso'] ?? '');
    $uso = trim($_POST['uso'] ?? '');
    $otras_caracteristicas = trim($_POST['otras_caracteristicas'] ?? '');
    $observaciones = trim($_POST['observaciones'] ?? '');
    $garantia = trim($_POST['garantia'] ?? '');
    $stock = (int)($_POST['stock'] ?? 0);
    $activo = isset($_POST['activo']) ? 1 : 0;
    $destacado = isset($_POST['destacado']) ? 1 : 0;
    $orden = (int)($_POST['orden'] ?? 0);
    
    // Validaciones
    if (empty($nombre)) {
        $error = 'El nombre es obligatorio';
    } elseif (empty($categoria_id)) {
        $error = 'La categoría es obligatoria';
    } else {
        try {
            // Verificar si el código ya existe
            if (!empty($codigo)) {
                $codigoExists = $db->fetchOne("SELECT id FROM productos WHERE codigo = ?", [$codigo]);
                if ($codigoExists) {
                    $error = 'El código ya existe en otro producto';
                }
            }
            
            if (empty($error)) {
                // Insertar nuevo producto
                $sql = "INSERT INTO productos (categoria_id, codigo, nombre, descripcion, 
                        precio, precio_mostrar, dimensiones, material, peso, uso, 
                        otras_caracteristicas, observaciones, garantia, stock, activo, 
                        destacado, orden) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                
                $producto_id = $db->insert('productos', [
                    'categoria_id' => $categoria_id,
                    'codigo' => $codigo,
                    'nombre' => $nombre,
                    'descripcion' => $descripcion,
                    'precio' => $precio,
                    'precio_mostrar' => $precio_mostrar,
                    'dimensiones' => $dimensiones,
                    'material' => $material,
                    'peso' => $peso,
                    'uso' => $uso,
                    'otras_caracteristicas' => $otras_caracteristicas,
                    'observaciones' => $observaciones,
                    'garantia' => $garantia,
                    'stock' => $stock,
                    'activo' => $activo,
                    'destacado' => $destacado,
                    'orden' => $orden
                ]);
                
                // Registrar en logs
                logActivity($_SESSION['user_id'], 'crear', 'productos', $producto_id, null, $_POST);
                
                $success = 'Producto creado correctamente con ID: ' . $producto_id;
                
                // Limpiar formulario
                $_POST = [];
            }
        } catch (Exception $e) {
            $error = 'Error al crear el producto: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nuevo Producto - Panel de Administración</title>
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
                            <h2 class="mb-0">Nuevo Producto</h2>
                            <p class="text-muted mb-0">Crea un nuevo producto en el catálogo</p>
                        </div>
                        <a href="productos.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left mr-2"></i>Volver a Productos
                        </a>
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
                    
                    <!-- Formulario -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-plus mr-2"></i>Datos del Nuevo Producto
                            </h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="categoria_id">Categoría *</label>
                                            <select class="form-control" id="categoria_id" name="categoria_id" required>
                                                <option value="">Seleccionar categoría</option>
                                                <?php foreach ($categorias as $cat): ?>
                                                    <option value="<?php echo $cat['id']; ?>" 
                                                            <?php echo (isset($_POST['categoria_id']) && $_POST['categoria_id'] == $cat['id']) ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($cat['nombre']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="codigo">Código</label>
                                            <input type="text" class="form-control" id="codigo" name="codigo" 
                                                   value="<?php echo htmlspecialchars($_POST['codigo'] ?? ''); ?>"
                                                   placeholder="ej: P3D-001">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="nombre">Nombre *</label>
                                    <input type="text" class="form-control" id="nombre" name="nombre" 
                                           value="<?php echo htmlspecialchars($_POST['nombre'] ?? ''); ?>" required
                                           placeholder="ej: Escuadra de refuerzo">
                                </div>
                                
                                <div class="form-group">
                                    <label for="descripcion">Descripción</label>
                                    <textarea class="form-control" id="descripcion" name="descripcion" rows="3" 
                                              placeholder="Descripción detallada del producto"><?php echo htmlspecialchars($_POST['descripcion'] ?? ''); ?></textarea>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="precio">Precio</label>
                                            <input type="number" class="form-control" id="precio" name="precio" 
                                                   value="<?php echo htmlspecialchars($_POST['precio'] ?? ''); ?>" 
                                                   step="0.01" min="0" placeholder="0.00">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="precio_mostrar">Precio a Mostrar</label>
                                            <input type="text" class="form-control" id="precio_mostrar" name="precio_mostrar" 
                                                   value="<?php echo htmlspecialchars($_POST['precio_mostrar'] ?? ''); ?>"
                                                   placeholder="ej: Consultar precio">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="dimensiones">Dimensiones</label>
                                            <input type="text" class="form-control" id="dimensiones" name="dimensiones" 
                                                   value="<?php echo htmlspecialchars($_POST['dimensiones'] ?? ''); ?>"
                                                   placeholder="ej: 50 x 50 x 3 mm">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="material">Material</label>
                                            <input type="text" class="form-control" id="material" name="material" 
                                                   value="<?php echo htmlspecialchars($_POST['material'] ?? ''); ?>"
                                                   placeholder="ej: PETG">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="peso">Peso</label>
                                            <input type="text" class="form-control" id="peso" name="peso" 
                                                   value="<?php echo htmlspecialchars($_POST['peso'] ?? ''); ?>"
                                                   placeholder="ej: 50g">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="uso">Uso</label>
                                            <input type="text" class="form-control" id="uso" name="uso" 
                                                   value="<?php echo htmlspecialchars($_POST['uso'] ?? ''); ?>"
                                                   placeholder="ej: Carpintería y construcción">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="otras_caracteristicas">Otras Características</label>
                                    <textarea class="form-control" id="otras_caracteristicas" name="otras_caracteristicas" rows="2"
                                              placeholder="Características adicionales del producto"><?php echo htmlspecialchars($_POST['otras_caracteristicas'] ?? ''); ?></textarea>
                                </div>
                                
                                <div class="form-group">
                                    <label for="observaciones">Observaciones</label>
                                    <textarea class="form-control" id="observaciones" name="observaciones" rows="2"
                                              placeholder="Observaciones internas"><?php echo htmlspecialchars($_POST['observaciones'] ?? ''); ?></textarea>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="garantia">Garantía</label>
                                            <input type="text" class="form-control" id="garantia" name="garantia" 
                                                   value="<?php echo htmlspecialchars($_POST['garantia'] ?? ''); ?>"
                                                   placeholder="ej: 6 meses">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="stock">Stock</label>
                                            <input type="number" class="form-control" id="stock" name="stock" 
                                                   value="<?php echo htmlspecialchars($_POST['stock'] ?? 0); ?>" min="0">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <div class="custom-control custom-switch">
                                                <input type="checkbox" class="custom-control-input" id="activo" name="activo" 
                                                       <?php echo isset($_POST['activo']) ? 'checked' : ''; ?>>
                                                <label class="custom-control-label" for="activo">Producto Activo</label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <div class="custom-control custom-switch">
                                                <input type="checkbox" class="custom-control-input" id="destacado" name="destacado" 
                                                       <?php echo isset($_POST['destacado']) ? 'checked' : ''; ?>>
                                                <label class="custom-control-label" for="destacado">Producto Destacado</label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="orden">Orden</label>
                                            <input type="number" class="form-control" id="orden" name="orden" 
                                                   value="<?php echo htmlspecialchars($_POST['orden'] ?? 0); ?>" min="0">
                                        </div>
                                    </div>
                                </div>
                                
                                <hr>
                                
                                <div class="d-flex justify-content-between">
                                    <a href="productos.php" class="btn btn-secondary">
                                        <i class="fas fa-times mr-2"></i>Cancelar
                                    </a>
                                    <button type="submit" class="btn btn-success">
                                        <i class="fas fa-save mr-2"></i>Crear Producto
                                    </button>
                                </div>
                            </form>
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
