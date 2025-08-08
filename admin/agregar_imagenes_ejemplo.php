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

// Datos de ejemplo para imágenes adicionales
$imagenes_ejemplo = [
    // Escuadra de refuerzo (ID: 1)
    [
        'producto_id' => 1,
        'imagen' => 'images/p13w_jc3d.jpg',
        'imagen_alt' => 'Vista frontal de la escuadra de refuerzo',
        'orden' => 1,
        'principal' => 0
    ],
    [
        'producto_id' => 1,
        'imagen' => 'images/p13w_jc3d.jpg',
        'imagen_alt' => 'Vista lateral mostrando el grosor',
        'orden' => 2,
        'principal' => 0
    ],
    [
        'producto_id' => 1,
        'imagen' => 'images/p13w_jc3d.jpg',
        'imagen_alt' => 'Escuadra instalada en unión de madera',
        'orden' => 3,
        'principal' => 0
    ],
    
    // Soporte de barra intermedia (ID: 2)
    [
        'producto_id' => 2,
        'imagen' => 'images/p13w_jc3d.jpg',
        'imagen_alt' => 'Vista completa del soporte de barra',
        'orden' => 1,
        'principal' => 0
    ],
    [
        'producto_id' => 2,
        'imagen' => 'images/p13w_jc3d.jpg',
        'imagen_alt' => 'Detalle de la unión',
        'orden' => 2,
        'principal' => 0
    ],
    
    // Mueble de Cocina Personalizado (ID: 14)
    [
        'producto_id' => 14,
        'imagen' => 'images/mueble_1.jpg',
        'imagen_alt' => 'Cocina completa instalada',
        'orden' => 1,
        'principal' => 0
    ],
    [
        'producto_id' => 14,
        'imagen' => 'images/mueble_1.jpg',
        'imagen_alt' => 'Detalle de cajones con correderas',
        'orden' => 2,
        'principal' => 0
    ],
    [
        'producto_id' => 14,
        'imagen' => 'images/mueble_1.jpg',
        'imagen_alt' => 'Detalle de acabados y materiales',
        'orden' => 3,
        'principal' => 0
    ],
    
    // Estantería Modular (ID: 15)
    [
        'producto_id' => 15,
        'imagen' => 'images/mueble_1.jpg',
        'imagen_alt' => 'Estantería modular completa',
        'orden' => 1,
        'principal' => 0
    ],
    [
        'producto_id' => 15,
        'imagen' => 'images/mueble_1.jpg',
        'imagen_alt' => 'Detalle de módulos intercambiables',
        'orden' => 2,
        'principal' => 0
    ]
];

$mensajes = [];
$errores = [];

// Insertar imágenes de ejemplo
foreach ($imagenes_ejemplo as $imagen) {
    try {
        // Verificar si ya existe
        $existe = $db->fetchOne(
            "SELECT id FROM producto_imagenes WHERE producto_id = ? AND imagen = ?", 
            [$imagen['producto_id'], $imagen['imagen']]
        );
        
        if (!$existe) {
            $imagen_id = $db->insert('producto_imagenes', $imagen);
            $mensajes[] = "Imagen agregada para producto ID {$imagen['producto_id']}";
        } else {
            $errores[] = "Imagen ya existe para producto ID {$imagen['producto_id']}";
        }
    } catch (Exception $e) {
        $errores[] = "Error al agregar imagen para producto ID {$imagen['producto_id']}: " . $e->getMessage();
    }
}

// Obtener estadísticas
$total_imagenes = $db->fetchOne("SELECT COUNT(*) as total FROM producto_imagenes")['total'];
$productos_con_imagenes = $db->fetchOne("SELECT COUNT(DISTINCT producto_id) as total FROM producto_imagenes")['total'];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agregar Imágenes de Ejemplo - Panel de Administración</title>
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
            
            <!-- Contenido principal -->
            <div class="col-md-9 col-lg-10">
                <div class="admin-content p-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2><i class="fas fa-images mr-2"></i>Agregar Imágenes de Ejemplo</h2>
                        <a href="imagenes.php" class="btn btn-outline-primary">
                            <i class="fas fa-arrow-left mr-2"></i>Volver a Imágenes
                        </a>
                    </div>
                    
                    <!-- Estadísticas -->
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <div class="card text-center">
                                <div class="card-body">
                                    <i class="fas fa-images fa-3x text-primary mb-3"></i>
                                    <h3 class="text-primary"><?php echo $total_imagenes; ?></h3>
                                    <p class="text-muted">Total de Imágenes</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card text-center">
                                <div class="card-body">
                                    <i class="fas fa-box fa-3x text-success mb-3"></i>
                                    <h3 class="text-success"><?php echo $productos_con_imagenes; ?></h3>
                                    <p class="text-muted">Productos con Imágenes</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card text-center">
                                <div class="card-body">
                                    <i class="fas fa-plus fa-3x text-info mb-3"></i>
                                    <h3 class="text-info"><?php echo count($imagenes_ejemplo); ?></h3>
                                    <p class="text-muted">Imágenes de Ejemplo</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Mensajes de éxito -->
                    <?php if (!empty($mensajes)): ?>
                        <div class="alert alert-success">
                            <h5><i class="fas fa-check-circle mr-2"></i>Imágenes agregadas exitosamente:</h5>
                            <ul class="mb-0">
                                <?php foreach ($mensajes as $mensaje): ?>
                                    <li><?php echo htmlspecialchars($mensaje); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Errores -->
                    <?php if (!empty($errores)): ?>
                        <div class="alert alert-warning">
                            <h5><i class="fas fa-exclamation-triangle mr-2"></i>Advertencias:</h5>
                            <ul class="mb-0">
                                <?php foreach ($errores as $error): ?>
                                    <li><?php echo htmlspecialchars($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Información -->
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="fas fa-info-circle mr-2"></i>Información</h5>
                        </div>
                        <div class="card-body">
                            <p>Se han agregado imágenes de ejemplo para los siguientes productos:</p>
                            <ul>
                                <li><strong>Escuadra de refuerzo</strong> - 3 imágenes adicionales</li>
                                <li><strong>Soporte de barra intermedia</strong> - 2 imágenes adicionales</li>
                                <li><strong>Mueble de Cocina Personalizado</strong> - 3 imágenes adicionales</li>
                                <li><strong>Estantería Modular</strong> - 2 imágenes adicionales</li>
                            </ul>
                            <p class="text-muted">
                                <i class="fas fa-lightbulb mr-1"></i>
                                Ahora puedes ver estas imágenes en la página de detalle de cada producto.
                            </p>
                        </div>
                    </div>
                    
                    <!-- Acciones -->
                    <div class="mt-4">
                        <a href="imagenes.php" class="btn btn-primary mr-2">
                            <i class="fas fa-images mr-2"></i>Gestionar Imágenes
                        </a>
                        <a href="../pag/producto.html?id=1" target="_blank" class="btn btn-outline-info">
                            <i class="fas fa-eye mr-2"></i>Ver Ejemplo en Frontend
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.4.1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
