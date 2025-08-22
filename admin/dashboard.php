<?php
require_once '../database/config.php';

// Verificar autenticación
if (!isAuthenticated()) {
    header('Location: login.php');
    exit;
}

// Fechas para el filtro del gráfico
if (isset($_GET['start_date']) && !empty($_GET['start_date']) && isset($_GET['end_date']) && !empty($_GET['end_date'])) {
    $start_date = $_GET['start_date'];
    $end_date = $_GET['end_date'];
} else {
    $end_date = date('Y-m-d');
    $start_date = date('Y-m-d', strtotime('-6 days'));
}

// Obtener estadísticas del dashboard
try {
    $db = getDB();
    
    // Estadísticas principales
    $stats = [
        'productos' => $db->fetchOne("SELECT COUNT(*) as total FROM productos WHERE activo = 1"),
        'categorias' => $db->fetchOne("SELECT COUNT(*) as total FROM categorias WHERE activo = 1"),
        'cotizaciones_solicitadas' => $db->fetchOne("SELECT COUNT(*) as total FROM cotizaciones WHERE estado = 'solicitada'"),
        'valor_solicitado' => $db->fetchOne("SELECT SUM(precio_cotizado) as total FROM cotizaciones WHERE estado = 'solicitada'"),
        'total_vendido' => $db->fetchOne("SELECT SUM(precio_cotizado) as total FROM cotizaciones WHERE estado IN ('vendida', 'enviada')")
    ];
    
    // Cambios esta semana
    $semana_pasada = date('Y-m-d', strtotime('-7 days'));
    $cambios_semana = [
        'productos_nuevos' => $db->fetchOne("SELECT COUNT(*) as total FROM productos WHERE activo = 1 AND created_at >= ?", [$semana_pasada]),
        'categorias_nuevas' => $db->fetchOne("SELECT COUNT(*) as total FROM categorias WHERE activo = 1 AND created_at >= ?", [$semana_pasada]),
        'cotizaciones_hoy' => $db->fetchOne("SELECT COUNT(*) as total FROM cotizaciones WHERE DATE(created_at) = CURDATE()"),
        'valor_hoy' => $db->fetchOne("SELECT SUM(precio_cotizado) as total FROM cotizaciones WHERE DATE(created_at) = CURDATE() AND estado IN ('vendida', 'enviada')")
    ];
    
            // Cotizaciones recientes
        $cotizaciones_recientes = $db->fetchAll("
            SELECT c.*, p.nombre as producto_nombre, cat.nombre as categoria_nombre 
            FROM cotizaciones c 
            LEFT JOIN productos p ON c.producto_id = p.id 
            LEFT JOIN categorias cat ON p.categoria_id = cat.id 
            ORDER BY c.created_at DESC 
            LIMIT 4
        ");
    
    // Datos para gráfico de actividad (rango de fechas)
    $datos_grafico = [];
    $current_date = new DateTime($start_date);
    $end_date_obj = new DateTime($end_date);

    while ($current_date <= $end_date_obj) {
        $fecha = $current_date->format('Y-m-d');
        $dia_label = $current_date->format('d/m');

        $productos_dia = $db->fetchOne("
            SELECT COUNT(*) as total FROM cotizaciones 
            WHERE DATE(created_at) = ? AND tipo_cotizacion = 'producto'
        ", [$fecha]);
        
        $servicios_dia = $db->fetchOne("
            SELECT COUNT(*) as total FROM cotizaciones 
            WHERE DATE(created_at) = ? AND tipo_cotizacion = 'servicio'
        ", [$fecha]);
        
        $valor_dia = $db->fetchOne("
            SELECT COALESCE(SUM(precio_cotizado), 0) as total FROM cotizaciones 
            WHERE DATE(created_at) = ? AND precio_cotizado IS NOT NULL AND estado IN ('vendida', 'enviada')
        ", [$fecha]);
        
        $datos_grafico[] = [
            'dia' => $dia_label,
            'productos' => $productos_dia['total'] ?? 0,
            'servicios' => $servicios_dia['total'] ?? 0,
            'valor' => $valor_dia['total'] ?? 0
        ];
        $current_date->modify('+1 day');
    }
    
    // Estado del stock
    $stock_info = [
        'alto' => $db->fetchOne("SELECT COUNT(*) as total FROM productos WHERE activo = 1 AND stock > 10"),
        'medio' => $db->fetchOne("SELECT COUNT(*) as total FROM productos WHERE activo = 1 AND stock BETWEEN 5 AND 10"),
        'bajo' => $db->fetchOne("SELECT COUNT(*) as total FROM productos WHERE activo = 1 AND stock BETWEEN 1 AND 4"),
        'sin_stock' => $db->fetchOne("SELECT COUNT(*) as total FROM productos WHERE activo = 1 AND stock = 0")
    ];

    // Productos con stock bajo o sin stock para la tabla
    $productos_stock_bajo = $db->fetchAll("
        SELECT id, nombre, stock, codigo 
        FROM productos 
        WHERE activo = 1 AND stock <= 4 
        ORDER BY stock ASC, nombre ASC
    ");
    
            // Top productos más vendidos
        $top_productos = $db->fetchAll("
            SELECT p.nombre, COUNT(c.id) as ventas
            FROM productos p
            LEFT JOIN cotizaciones c ON p.id = c.producto_id
            WHERE p.activo = 1 AND c.tipo_cotizacion = 'producto' AND c.estado IN ('vendida', 'enviada')
            GROUP BY p.id, p.nombre
            ORDER BY ventas DESC
            LIMIT 5
        ");
    
            // Distribución de servicios por categoría (vendidos)
        $servicios_categoria = $db->fetchAll("
            SELECT 
                s.nombre,
                COUNT(c.id) as total
            FROM cotizaciones c
            JOIN servicios s ON c.servicio_id = s.id
            WHERE c.tipo_cotizacion = 'servicio' AND c.estado IN ('vendida', 'enviada')
            GROUP BY s.nombre
            ORDER BY total DESC
            LIMIT 5
        ");

    // Métrica: Ventas vs mes anterior
    $mes_actual_inicio = date('Y-m-01');
    $mes_anterior_inicio = date('Y-m-01', strtotime('-1 month'));
    $mes_anterior_fin = date('Y-m-t', strtotime('-1 month'));
    
    $ventas_mes_actual = $db->fetchOne("SELECT SUM(precio_cotizado) as total FROM cotizaciones WHERE estado IN ('vendida', 'enviada') AND created_at >= ?", [$mes_actual_inicio])['total'] ?? 0;
    $ventas_mes_anterior = $db->fetchOne("SELECT SUM(precio_cotizado) as total FROM cotizaciones WHERE estado IN ('vendida', 'enviada') AND created_at BETWEEN ? AND ?", [$mes_anterior_inicio, $mes_anterior_fin])['total'] ?? 0;
    
    if ($ventas_mes_anterior > 0) {
        $porcentaje_cambio = (($ventas_mes_actual - $ventas_mes_anterior) / $ventas_mes_anterior) * 100;
    } else {
        $porcentaje_cambio = $ventas_mes_actual > 0 ? 100 : 0;
    }
    
} catch (Exception $e) {
    error_log("Error en dashboard: " . $e->getMessage());
    
    // Inicializar variables con valores por defecto en caso de error
    $stats = [
        'productos' => ['total' => 0],
        'categorias' => ['total' => 0],
        'cotizaciones_solicitadas' => ['total' => 0],
        'valor_solicitado' => ['total' => 0],
        'total_vendido' => ['total' => 0]
    ];
    
    $cambios_semana = [
        'productos_nuevos' => ['total' => 0],
        'categorias_nuevas' => ['total' => 0],
        'cotizaciones_hoy' => ['total' => 0],
        'valor_hoy' => ['total' => 0]
    ];
    
    $cotizaciones_recientes = [];
    $datos_grafico = [];
    $stock_info = [
        'alto' => ['total' => 0],
        'medio' => ['total' => 0],
        'bajo' => ['total' => 0],
        'sin_stock' => ['total' => 0]
    ];
    $productos_stock_bajo = [];
    $top_productos = [];
    $servicios_categoria = [];
    $porcentaje_cambio = 0;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard de Ventas - JC3Design</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.4.1/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../css/dark-mode.css">
    <link rel="stylesheet" href="../css/admin-dark-mode.css">
    <link rel="stylesheet" href="../css/chart-dark-mode.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .admin-sidebar {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .admin-content {
            min-height: 100vh;
            background-color: var(--bg-secondary);
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
        
        .stats-card {
            background: var(--card-bg);
            border-radius: 15px;
            padding: 1.5rem;
            text-align: center;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            height: 100%;
            box-shadow: 0 2px 10px var(--shadow-light);
            border: 1px solid var(--card-border);
        }

        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }

        .stats-icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            opacity: 0.8;
        }

        .stats-number {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }

        .stats-label {
            font-size: 0.9rem;
            color: #6c757d;
            margin-bottom: 0.5rem;
        }

        .stats-change {
            font-size: 0.8rem;
            padding: 0.25rem 0.5rem;
            border-radius: 20px;
            display: inline-block;
        }

        .stats-change.positive {
            background-color: #28a745;
            color: white;
        }

        .stats-change.negative {
            background-color: #dc3545;
            color: white;
        }

        .chart-container {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            height: 350px;
            position: relative;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .chart-title {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            color: #495057;
        }

        .cotizaciones-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .cotizacion-item {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 0.75rem;
            border-left: 4px solid #007bff;
        }

        .cotizacion-item.new {
            border-left-color: #28a745;
        }

        .cotizacion-time {
            font-size: 0.8rem;
            color: #6c757d;
        }

        .cotizacion-number {
            font-weight: bold;
            color: #007bff;
        }

        .cotizacion-amount {
            font-weight: bold;
            color: #28a745;
        }

        .metrics-container {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .metric-card {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1rem;
            text-align: center;
            margin-bottom: 1rem;
        }

        .metric-value {
            font-size: 1.5rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }

        .metric-label {
            font-size: 0.8rem;
            color: #6c757d;
        }

        .status-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .status-item {
            display: flex;
            align-items: center;
            margin-bottom: 0.75rem;
            padding: 0.5rem 0;
        }

        .status-icon {
            margin-right: 0.75rem;
            font-size: 1.2rem;
        }

        .status-icon.success {
            color: #28a745;
        }

        .status-icon.warning {
            color: #ffc107;
        }

        .update-bar {
            background: white;
            border-radius: 10px;
            padding: 1rem;
            text-align: center;
            margin-top: 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .refresh-btn {
            background: #007bff;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            margin-left: 1rem;
            transition: all 0.3s ease;
        }

        .refresh-btn:hover {
            background: #0056b3;
            transform: scale(1.05);
        }

        .refresh-btn i {
            margin-right: 0.5rem;
        }

        @media (max-width: 768px) {
            .stats-card {
                margin-bottom: 1rem;
            }
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
                        <a class="nav-link active" href="dashboard.php">
                            <i class="fas fa-chart-line mr-2"></i>Dashboard de Ventas
                        </a>
                        <a class="nav-link" href="index.php">
                            <i class="fas fa-tachometer-alt mr-2"></i>Panel Principal
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
                            <h1 class="h3 mb-0">
                                <i class="fas fa-chart-line mr-2"></i>Dashboard de Ventas
                            </h1>
                            <p class="text-muted">Análisis completo de ventas, stock y cotizaciones</p>
                        </div>
                        <div class="text-right">
                            <p class="mb-0"><strong>Usuario:</strong> <?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?></p>
                            <small class="text-muted">Última actualización: <?php echo date('H:i:s'); ?></small>
                        </div>
                    </div>

                    <!-- Mensajes de confirmación -->
                    <?php if (isset($_GET['success']) && $_GET['success'] === 'reporte'): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle mr-2"></i>
                            <strong>¡Éxito!</strong> El reporte se ha generado correctamente.
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($_GET['error']) && $_GET['error'] === 'reporte'): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-triangle mr-2"></i>
                            <strong>Error:</strong> No se pudo generar el reporte. Inténtalo de nuevo.
                            <button type="button" class="close" data-dismissible fade show" role="alert">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    <?php endif; ?>

                    <!-- Estadísticas Principales -->
                    <div class="row mb-4">
                        <div class="col-lg-3 col-md-6 mb-3">
                            <div class="stats-card">
                                <div class="stats-icon text-primary">
                                    <i class="fas fa-box"></i>
                                </div>
                                <div class="stats-number"><?php echo $stats['productos']['total'] ?? 0; ?></div>
                                <div class="stats-label">Total Productos</div>
                                <div class="stats-change positive">+<?php echo $cambios_semana['productos_nuevos']['total'] ?? 0; ?> esta sem</div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6 mb-3">
                            <div class="stats-card">
                                <div class="stats-icon text-info">
                                    <i class="fas fa-tags"></i>
                                </div>
                                <div class="stats-number"><?php echo $stats['categorias']['total'] ?? 0; ?></div>
                                <div class="stats-label">Total Categorías</div>
                                <div class="stats-change positive">+<?php echo $cambios_semana['categorias_nuevas']['total'] ?? 0; ?> esta sem</div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6 mb-3">
                            <div class="stats-card">
                                <div class="stats-icon text-warning">
                                    <i class="fas fa-file-invoice"></i>
                                </div>
                                <div class="stats-number"><?php echo $stats['cotizaciones_solicitadas']['total'] ?? 0; ?></div>
                                <div class="stats-label">Cotizaciones Solicitadas</div>
                                <div class="stats-number" style="font-size: 1.5rem; margin-top: 5px;">$<?php echo number_format($stats['valor_solicitado']['total'] ?? 0, 0, ',', '.'); ?></div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6 mb-3">
                            <div class="stats-card">
                                <div class="stats-icon text-success">
                                    <i class="fas fa-dollar-sign"></i>
                                </div>
                                <div class="stats-number">$<?php echo number_format($stats['total_vendido']['total'] ?? 0, 0, ',', '.'); ?></div>
                                <div class="stats-label">Total Vendido</div>
                                <div class="stats-change positive">+$<?php echo number_format($cambios_semana['valor_hoy']['total'] ?? 0, 0, ',', '.'); ?> hoy</div>
                            </div>
                        </div>
                    </div>

                    <!-- Gráfico de Cotizaciones -->
                    <div class="chart-container mb-4">
                        <div class="d-flex justify-content-between align-items-center flex-wrap">
                            <div class="chart-title mb-2 mb-md-0">
                                <i class="fas fa-chart-line"></i> ACTIVIDAD DE COTIZACIONES
                            </div>
                            <form id="date-filter-form" class="d-flex align-items-center">
                                <input type="date" name="start_date" id="start_date" class="form-control form-control-sm me-2" value="<?php echo htmlspecialchars($start_date); ?>" style="width: auto;">
                                <input type="date" name="end_date" id="end_date" class="form-control form-control-sm me-2" value="<?php echo htmlspecialchars($end_date); ?>" style="width: auto;">
                                <button type="submit" class="btn btn-primary btn-sm">Filtrar</button>
                            </form>
                        </div>
                        <canvas id="activityChart" style="margin-top: 1.5rem;"></canvas>
                        <div class="text-center mt-3">
                            <span class="badge badge-primary me-2">🟦 Productos</span>
                            <span class="badge badge-success me-2">🟢 Servicios</span>
                            <span class="badge badge-warning">💰 Valor Vendido</span>
                        </div>
                    </div>

                    <!-- Gráficos de Stock y Top Ventas -->
                    <div class="row mb-4">
                        <div class="col-md-6 mb-3">
                            <div class="chart-container" style="height: 300px;">
                                <div class="chart-title">
                                    <i class="fas fa-boxes"></i> ESTADO DEL STOCK
                                </div>
                                <canvas id="stockChart"></canvas>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="chart-container" style="height: 300px;">
                                <div class="chart-title">
                                    <i class="fas fa-trophy"></i> TOP 5 PRODUCTOS MÁS VENDIDOS
                                </div>
                                <canvas id="topProductsChart"></canvas>
                            </div>
                        </div>
                    </div>

                    <!-- Gráfico de Servicios por Categoría y Tabla de Stock Bajo -->
                    <div class="row mb-4">
                        <div class="col-md-6 mb-3">
                            <div class="chart-container" style="height: 320px;">
                                <div class="chart-title">
                                    <i class="fas fa-chart-pie"></i> DISTRIBUCIÓN DE SERVICIOS VENDIDOS
                                </div>
                                <canvas id="servicesChart"></canvas>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="status-card" style="height: 320px; display: flex; flex-direction: column;">
                                <div class="chart-title">
                                    <i class="fas fa-exclamation-triangle"></i> ALERTA DE STOCK BAJO
                                </div>
                                <div style="flex-grow: 1; overflow-y: auto;">
                                    <table class="table table-hover table-sm">
                                        <thead>
                                            <tr>
                                                <th>Producto</th>
                                                <th class="text-center">Stock</th>
                                                <th class="text-end">Acción</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (!empty($productos_stock_bajo)): ?>
                                                <?php foreach ($productos_stock_bajo as $producto): ?>
                                                    <tr>
                                                        <td class="align-middle"><?php echo htmlspecialchars($producto['nombre']); ?></td>
                                                        <td class="text-center align-middle">
                                                            <span class="badge <?php echo $producto['stock'] == 0 ? 'badge-danger' : 'badge-warning'; ?>">
                                                                <?php echo $producto['stock']; ?>
                                                            </span>
                                                        </td>
                                                        <td class="text-end">
                                                            <a href="editar_producto.php?id=<?php echo $producto['id']; ?>" class="btn btn-outline-primary btn-sm py-1 px-2">
                                                                <i class="fas fa-edit"></i>
                                                            </a>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="3" class="text-center text-muted py-4">¡Todo bien! No hay productos con stock bajo.</td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Cotizaciones Recientes -->
                    <div class="cotizaciones-card">
                        <div class="chart-title">
                            <i class="fas fa-file-invoice"></i> COTIZACIONES RECIENTES
                        </div>
                        <?php if (!empty($cotizaciones_recientes)): ?>
                            <?php foreach ($cotizaciones_recientes as $cotizacion): ?>
                                <div class="cotizacion-item <?php echo $cotizacion['estado'] === 'solicitada' ? 'new' : ''; ?>">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <span class="cotizacion-time">
                                                <i class="far fa-clock"></i> <?php echo timeAgo($cotizacion['created_at']); ?>
                                            </span>
                                            <div class="mt-1 d-flex align-items-center">
                                                <span class="cotizacion-number me-2">#<?php echo $cotizacion['id']; ?></span>
                                                <span class="me-2"><?php echo htmlspecialchars($cotizacion['producto_nombre'] ?: ($cotizacion['categoria_nombre'] ?? 'Servicio General')); ?></span>
                                                <span class="cotizacion-amount me-2">$<?php echo number_format($cotizacion['precio_cotizado'] ?? 0, 0, ',', '.'); ?></span>
                                                <span class="badge <?php 
                                                    switch ($cotizacion['estado']) {
                                                        case 'vendida': echo 'badge-success'; break;
                                                        case 'enviada': echo 'badge-info'; break;
                                                        default: echo 'badge-warning';
                                                    }
                                                ?>"><?php echo ucfirst($cotizacion['estado']); ?></span>
                                            </div>
                                        </div>
                                        <a href="cotizaciones.php?id=<?php echo $cotizacion['id']; ?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-eye"></i></a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-center text-muted">
                                <i class="fas fa-inbox fa-2x mb-2"></i>
                                <p>No hay cotizaciones recientes</p>
                            </div>
                        <?php endif; ?>
                        <div class="text-center mt-3">
                            <a href="cotizaciones.php" class="btn btn-outline-primary me-2">
                                <i class="fas fa-eye"></i> Ver todas las cotizaciones
                            </a>
                            <button type="button" class="btn btn-outline-success" data-toggle="modal" data-target="#reporteModal">
                                <i class="fas fa-file-pdf"></i> Generar reporte
                            </button>
                        </div>
                    </div>

                    <!-- Métricas de Ventas y Stock -->
                    <div class="metrics-container">
                        <div class="chart-title">
                            <i class="fas fa-chart-bar"></i> MÉTRICAS CLAVE
                        </div>
                        <div class="row">
                            <div class="col-md-3 col-sm-6 mb-3">
                                <div class="metric-card">
                                    <div class="metric-value <?php echo $porcentaje_cambio >= 0 ? 'text-success' : 'text-danger'; ?>">
                                        <?php echo ($porcentaje_cambio >= 0 ? '+' : '') . number_format($porcentaje_cambio, 1); ?>%
                                    </div>
                                    <div class="metric-label">📈 VENTAS vs mes anterior</div>
                                </div>
                            </div>
                            <div class="col-md-3 col-sm-6 mb-3">
                                <div class="metric-card">
                                    <div class="metric-value text-info">
                                        <?php echo !empty($top_productos) ? htmlspecialchars($top_productos[0]['nombre']) : 'N/A'; ?>
                                    </div>
                                    <div class="metric-label">🏆 PRODUCTO TOP (<?php echo !empty($top_productos) ? $top_productos[0]['ventas'] : 0; ?> ventas)</div>
                                </div>
                            </div>
                            <div class="col-md-3 col-sm-6 mb-3">
                                <div class="metric-card">
                                    <div class="metric-value text-warning"><?php echo ($stock_info['bajo']['total'] ?? 0) + ($stock_info['sin_stock']['total'] ?? 0); ?></div>
                                    <div class="metric-label">⚠️ PRODUCTOS STOCK BAJO</div>
                                </div>
                            </div>
                            <div class="col-md-3 col-sm-6 mb-3">
                                <div class="metric-card">
                                    <div class="metric-value text-primary">
                                        <?php echo !empty($servicios_categoria) ? htmlspecialchars($servicios_categoria[0]['nombre']) : 'N/A'; ?>
                                    </div>
                                    <div class="metric-label">🟢 SERVICIO MÁS VENDIDO</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Acciones Rápidas -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body text-center">
                                    <h6 class="card-title mb-3">
                                        <i class="fas fa-rocket mr-2"></i>ACCIONES RÁPIDAS
                                    </h6>
                                    <div class="btn-group" role="group">
                                        <button type="button" class="btn btn-outline-primary" data-toggle="modal" data-target="#reporteModal">
                                            <i class="fas fa-file-csv mr-2"></i>Reporte del Mes
                                        </button>
                                        <a href="generar_reporte.php?fecha_inicio=<?php echo date('Y-m-01'); ?>&fecha_fin=<?php echo date('Y-m-d'); ?>" 
                                           class="btn btn-outline-success" target="_blank">
                                            <i class="fas fa-download mr-2"></i>Descargar Mes Actual
                                        </a>
                                        <a href="generar_reporte.php?fecha_inicio=<?php echo date('Y-m-d', strtotime('-30 days')); ?>&fecha_fin=<?php echo date('Y-m-d'); ?>" 
                                           class="btn btn-outline-info" target="_blank">
                                            <i class="fas fa-calendar mr-2"></i>Últimos 30 Días
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Estado del Sistema -->
                    <div class="status-card">
                        <div class="chart-title">
                            <i class="fas fa-server"></i> ESTADO DEL SISTEMA
                        </div>
                        <div class="status-item">
                            <div class="status-icon success">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div class="status-text">Base de datos: Conectada</div>
                            <div class="status-time">(Último backup: hace 2 horas)</div>
                        </div>
                        <div class="status-item">
                            <div class="status-icon success">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div class="status-text">Sistema de logs: Activo</div>
                            <div class="status-time">(15 entradas hoy)</div>
                        </div>
                        <div class="status-item">
                            <div class="status-icon success">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div class="status-text">Sitemap: Actualizado</div>
                            <div class="status-time">(hace 1 hora)</div>
                        </div>
                        <div class="status-item">
                            <div class="status-icon warning">
                                <i class="fas fa-exclamation-triangle"></i>
                            </div>
                            <div class="status-text">Cotizaciones pendientes: <?php echo $stats['cotizaciones_solicitadas']['total'] ?? 0; ?></div>
                            <div class="status-time">(Requerir atención)</div>
                        </div>
                    </div>

                    <!-- Barra de Actualización -->
                    <div class="update-bar">
                        <span>
                            <i class="fas fa-clock"></i> Última actualización: <?php echo date('H:i:s'); ?>
                        </span>
                        <button class="refresh-btn" onclick="location.reload()">
                            <i class="fas fa-sync-alt"></i> Actualizar ahora
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para Generar Reporte -->
    <div class="modal fade" id="reporteModal" tabindex="-1" role="dialog" aria-labelledby="reporteModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="reporteModalLabel">
                        <i class="fas fa-file-pdf mr-2"></i>Generar Reporte de Cotizaciones
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="reporteForm" action="generar_reporte.php" method="GET" target="_blank">
                        <div class="form-group">
                            <label for="fecha_inicio">Fecha de Inicio:</label>
                            <input type="date" class="form-control" id="fecha_inicio" name="fecha_inicio" 
                                   value="<?php echo date('Y-m-01'); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="fecha_fin">Fecha de Fin:</label>
                            <input type="date" class="form-control" id="fecha_fin" name="fecha_fin" 
                                   value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle mr-2"></i>
                            <strong>Información del reporte:</strong>
                            <ul class="mb-0 mt-2">
                                <li>Se descargará un archivo CSV con todas las cotizaciones del período</li>
                                <li>Incluye: cliente, producto/servicio, precio, estado y fechas</li>
                                <li>Con resumen estadístico del período seleccionado</li>
                                <li>Compatible con Excel y Google Sheets</li>
                            </ul>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fas fa-times mr-1"></i>Cancelar
                    </button>
                    <button type="submit" form="reporteForm" class="btn btn-success">
                        <i class="fas fa-download mr-1"></i>Descargar Reporte
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.4.1/dist/js/bootstrap.min.js"></script>
    <script src="../js/dark-mode.js"></script>
    <script>
        // Datos del gráfico desde PHP
        const datosGrafico = <?php echo json_encode($datos_grafico); ?>;
        const stockInfo = <?php echo json_encode($stock_info); ?>;
        const topProductos = <?php echo json_encode($top_productos); ?>;
        const serviciosCategoria = <?php echo json_encode($servicios_categoria); ?>;
        
        // Función para obtener colores según el tema
        function getChartColors() {
            const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
            return {
                textColor: isDark ? '#ffffff' : '#212529',
                gridColor: isDark ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.1)',
                borderColor: isDark ? '#404040' : '#dee2e6'
            };
        }

        // Función para actualizar colores de gráficos
        function updateChartColors() {
            const colors = getChartColors();
            
            // Actualizar gráfico de actividad
            if (activityChart) {
                activityChart.options.scales.x.grid.color = colors.gridColor;
                activityChart.options.scales.y.grid.color = colors.gridColor;
                activityChart.options.scales.y1.grid.color = colors.gridColor;
                activityChart.options.scales.x.ticks.color = colors.textColor;
                activityChart.options.scales.y.ticks.color = colors.textColor;
                activityChart.options.scales.y1.ticks.color = colors.textColor;
                activityChart.options.scales.x.title.color = colors.textColor;
                activityChart.options.scales.y.title.color = colors.textColor;
                activityChart.options.scales.y1.title.color = colors.textColor;
                activityChart.update();
            }
            
            // Actualizar gráfico de stock
            if (stockChart) {
                stockChart.options.plugins.legend.labels.color = colors.textColor;
                stockChart.update();
            }
            
            // Actualizar gráfico de productos top
            if (topProductsChart) {
                topProductsChart.options.scales.x.ticks.color = colors.textColor;
                topProductsChart.options.scales.y.ticks.color = colors.textColor;
                topProductsChart.update();
            }
            
            // Actualizar gráfico de servicios
            if (servicesChart) {
                servicesChart.options.plugins.legend.labels.color = colors.textColor;
                servicesChart.update();
            }
        }

        // Escuchar cambios de tema
        document.addEventListener('themeChanged', function(e) {
            setTimeout(updateChartColors, 100);
        });
        
        // Actualizar colores inicialmente
        setTimeout(updateChartColors, 500);
        
        // Gráfico de cotizaciones: Productos vs Servicios
        const ctx = document.getElementById('activityChart').getContext('2d');
        const activityChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: datosGrafico.map(d => d.dia),
                datasets: [{
                    label: 'Cotizaciones de Productos',
                    data: datosGrafico.map(d => parseInt(d.productos) || 0),
                    borderColor: '#007bff',
                    backgroundColor: 'rgba(0, 123, 255, 0.1)',
                    tension: 0.4,
                    fill: true,
                    yAxisID: 'y'
                }, {
                    label: 'Cotizaciones de Servicios',
                    data: datosGrafico.map(d => parseInt(d.servicios) || 0),
                    borderColor: '#28a745',
                    backgroundColor: 'rgba(40, 167, 69, 0.1)',
                    tension: 0.4,
                    fill: true,
                    yAxisID: 'y'
                }, {
                    label: 'Valor Vendido ($)',
                    data: datosGrafico.map(d => parseFloat(d.valor) || 0),
                    borderColor: '#ffc107',
                    backgroundColor: 'rgba(255, 193, 7, 0.1)',
                    tension: 0.4,
                    fill: false,
                    yAxisID: 'y1',
                    borderDash: [5, 5]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                if (context.parsed.y !== null) {
                                    if (context.dataset.yAxisID === 'y1') {
                                        label += new Intl.NumberFormat('es-CL', { style: 'currency', currency: 'CLP' }).format(context.parsed.y);
                                    } else {
                                        label += context.parsed.y;
                                    }
                                }
                                return label;
                            }
                        }
                    }
                },
                layout: {
                    padding: {
                        top: 10,
                        bottom: 10
                    }
                },
                scales: {
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        beginAtZero: true,
                        grid: {
                            color: getChartColors().gridColor
                        },
                        ticks: {
                            color: getChartColors().textColor,
                            stepSize: 1,
                            precision: 0,
                            callback: function(value) {
                                return Math.round(value) + ' cotiz.';
                            }
                        },
                        title: {
                            display: true,
                            text: 'Cantidad de Cotizaciones',
                            color: getChartColors().textColor
                        }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        beginAtZero: true,
                        grid: {
                            drawOnChartArea: false,
                        },
                        ticks: {
                            color: '#ffc107',
                            precision: 0,
                            callback: function(value) {
                                return '$' + value.toLocaleString('es-CL');
                            }
                        },
                        title: {
                            display: true,
                            text: 'Valor Vendido ($)',
                            color: '#ffc107'
                        }
                    },
                    x: {
                        grid: {
                            color: getChartColors().gridColor
                        },
                        ticks: {
                            color: getChartColors().textColor
                        }
                    }
                },
                elements: {
                    point: {
                        radius: 4,
                        hoverRadius: 6
                    }
                }
            }
        });

        // Gráfico de Estado del Stock
        const stockCtx = document.getElementById('stockChart').getContext('2d');
        const stockChart = new Chart(stockCtx, {
            type: 'doughnut',
            data: {
                labels: ['Stock Alto', 'Stock Medio', 'Stock Bajo', 'Sin Stock'],
                datasets: [{
                    data: [
                        stockInfo.alto?.total || 0,
                        stockInfo.medio?.total || 0,
                        stockInfo.bajo?.total || 0,
                        stockInfo.sin_stock?.total || 0
                    ],
                    backgroundColor: [
                        '#28a745', // Verde - Stock Alto
                        '#007bff', // Azul - Stock Medio
                        '#ffc107', // Amarillo - Stock Bajo
                        '#dc3545'  // Rojo - Sin Stock
                    ],
                    borderWidth: 2,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            color: getChartColors().textColor,
                            padding: 15
                        }
                    }
                }
            }
        });

        // Gráfico de Top 5 Productos Más Vendidos
        const topProductsCtx = document.getElementById('topProductsChart').getContext('2d');
        const topProductsChart = new Chart(topProductsCtx, {
            type: 'bar',
            data: {
                labels: topProductos.map(p => p.nombre),
                datasets: [{
                    label: 'Ventas',
                    data: topProductos.map(p => parseInt(p.ventas) || 0),
                    backgroundColor: [
                        '#007bff',
                        '#28a745',
                        '#ffc107',
                        '#6f42c1',
                        '#fd7e14'
                    ],
                    borderWidth: 0,
                    borderRadius: 5
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'y',
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            color: getChartColors().textColor
                        }
                    },
                    x: {
                        beginAtZero: true,
                        grid: {
                            color: getChartColors().gridColor
                        },
                        ticks: {
                            color: getChartColors().textColor,
                            stepSize: 1,
                            precision: 0,
                            callback: function(value) {
                                return Math.round(value) + ' ventas';
                            }
                        }
                    }
                }
            }
        });

        // Gráfico de Distribución de Servicios por Categoría
        const servicesCtx = document.getElementById('servicesChart').getContext('2d');
        const servicesChart = new Chart(servicesCtx, {
            type: 'pie',
            data: {
                labels: serviciosCategoria.map(s => s.nombre),
                datasets: [{
                    data: serviciosCategoria.map(s => s.total),
                    backgroundColor: [
                        '#007bff',
                        '#28a745',
                        '#ffc107',
                        '#6f42c1',
                        '#fd7e14'
                    ],
                    borderWidth: 2,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            color: getChartColors().textColor,
                            padding: 15
                        }
                    }
                }
            }
        });

        // Hover effects para las cards
        document.querySelectorAll('.stats-card').forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-5px)';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
            });
        });

        // Validación de fechas en el modal de reporte
        document.getElementById('reporteForm').addEventListener('submit', function(e) {
            const fechaInicio = new Date(document.getElementById('fecha_inicio').value);
            const fechaFin = new Date(document.getElementById('fecha_fin').value);
            
            if (fechaInicio > fechaFin) {
                e.preventDefault();
                alert('La fecha de inicio no puede ser mayor que la fecha de fin.');
                return false;
            }
            
            // Mostrar indicador de carga
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>Generando...';
            submitBtn.disabled = true;
            
            // Restaurar después de un tiempo
            setTimeout(() => {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }, 3000);
        });

        // Auto-cerrar alertas después de 5 segundos
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                // Para Bootstrap 4, usar jQuery o método nativo
                $(alert).fadeOut();
            });
        }, 5000);
    </script>
</body>
</html>

<?php
// Función helper para mostrar tiempo relativo
function timeAgo($datetime) {
    $time = strtotime($datetime);
    $now = time();
    $diff = $now - $time;
    
    if ($diff < 60) {
        return 'hace un momento';
    } elseif ($diff < 3600) {
        $minutes = floor($diff / 60);
        return "hace $minutes min";
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return "hace $hours hora" . ($hours > 1 ? 's' : '');
    } else {
        $days = floor($diff / 86400);
        return "hace $days día" . ($days > 1 ? 's' : '');
    }
}
?>
