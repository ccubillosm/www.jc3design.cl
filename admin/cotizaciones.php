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

// Obtener filtros
$estado = $_GET['estado'] ?? '';
$page = (int)($_GET['page'] ?? 1);
$limit = 20;
$offset = ($page - 1) * $limit;

// Construir consulta
$sql = "SELECT c.*, 
               p.nombre as producto_nombre, p.codigo as producto_codigo,
               s.nombre as servicio_nombre, s.slug as servicio_slug
        FROM cotizaciones c 
        LEFT JOIN productos p ON c.producto_id = p.id 
        LEFT JOIN servicios s ON c.servicio_id = s.id";
$params = [];

if ($estado) {
    $sql .= " WHERE c.estado = ?";
    $params[] = $estado;
}

$sql .= " ORDER BY c.created_at DESC LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;

$cotizaciones = $db->fetchAll($sql, $params);

// Obtener estadísticas
$total_cotizaciones = $db->fetchOne("SELECT COUNT(*) as total FROM cotizaciones")['total'];
$solicitadas = $db->fetchOne("SELECT COUNT(*) as total FROM cotizaciones WHERE estado = 'solicitada'")['total'];
$enviadas = $db->fetchOne("SELECT COUNT(*) as total FROM cotizaciones WHERE estado = 'enviada'")['total'];
$vendidas = $db->fetchOne("SELECT COUNT(*) as total FROM cotizaciones WHERE estado = 'vendida'")['total'];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestionar Cotizaciones - Panel de Administración</title>
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
        .estado-solicitada { background-color: #fff3cd; }
        .estado-enviada { background-color: #d1ecf1; }
        .estado-vendida { background-color: #d4edda; }
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
                        <a class="nav-link active" href="cotizaciones.php">
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
                            <h2 class="mb-0">Gestionar Cotizaciones</h2>
                            <p class="text-muted mb-0">Administra las solicitudes de cotización</p>
                        </div>
                    </div>
                    
                    <!-- Estadísticas -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card bg-primary text-white">
                                <div class="card-body">
                                    <h5 class="card-title">Total</h5>
                                    <h3><?php echo $total_cotizaciones; ?></h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-warning text-white">
                                <div class="card-body">
                                    <h5 class="card-title">Solicitadas</h5>
                                    <h3><?php echo $solicitadas; ?></h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-info text-white">
                                <div class="card-body">
                                    <h5 class="card-title">Enviadas</h5>
                                    <h3><?php echo $enviadas; ?></h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-success text-white">
                                <div class="card-body">
                                    <h5 class="card-title">Vendidas</h5>
                                    <h3><?php echo $vendidas; ?></h3>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Filtros -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <form method="GET" class="row">
                                <div class="col-md-4">
                                    <label for="estado">Filtrar por estado:</label>
                                    <select name="estado" id="estado" class="form-control" onchange="this.form.submit()">
                                        <option value="">Todos los estados</option>
                                        <option value="solicitada" <?php echo $estado === 'solicitada' ? 'selected' : ''; ?>>Solicitadas</option>
                                        <option value="enviada" <?php echo $estado === 'enviada' ? 'selected' : ''; ?>>Enviadas</option>
                                        <option value="vendida" <?php echo $estado === 'vendida' ? 'selected' : ''; ?>>Vendidas</option>
                                    </select>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Lista de Cotizaciones -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-file-invoice mr-2"></i>Lista de Cotizaciones
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($cotizaciones)): ?>
                                <p class="text-muted">No hay cotizaciones registradas</p>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Cliente</th>
                                                <th>Tipo</th>
                                                <th>Producto/Servicio</th>
                                                <th>Estado</th>
                                                <th>Fecha</th>
                                                <th>Precio</th>
                                                <th>Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($cotizaciones as $cotizacion): ?>
                                                <tr class="estado-<?php echo $cotizacion['estado']; ?>">
                                                    <td>
                                                        <strong><?php echo htmlspecialchars($cotizacion['nombre_cliente']); ?></strong>
                                                        <br>
                                                        <small class="text-muted">
                                                            <?php echo htmlspecialchars($cotizacion['email_cliente']); ?>
                                                            <?php if ($cotizacion['telefono_cliente']): ?>
                                                                <br><?php echo htmlspecialchars($cotizacion['telefono_cliente']); ?>
                                                            <?php endif; ?>
                                                        </small>
                                                    </td>
                                                    <td>
                                                        <span class="badge badge-<?php echo $cotizacion['tipo_cotizacion'] === 'producto' ? 'primary' : 'info'; ?>">
                                                            <?php echo ucfirst($cotizacion['tipo_cotizacion']); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <?php if ($cotizacion['tipo_cotizacion'] === 'producto'): ?>
                                                            <strong><?php echo htmlspecialchars($cotizacion['producto_nombre']); ?></strong>
                                                            <br>
                                                            <small class="text-muted"><?php echo htmlspecialchars($cotizacion['producto_codigo']); ?></small>
                                                        <?php else: ?>
                                                            <strong><?php echo htmlspecialchars($cotizacion['servicio_nombre']); ?></strong>
                                                            <br>
                                                            <small class="text-muted"><?php echo htmlspecialchars($cotizacion['servicio_slug']); ?></small>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php
                                                        $badgeClass = '';
                                                        $estadoText = '';
                                                        switch ($cotizacion['estado']) {
                                                            case 'solicitada':
                                                                $badgeClass = 'badge-warning';
                                                                $estadoText = 'Solicitada';
                                                                break;
                                                            case 'enviada':
                                                                $badgeClass = 'badge-info';
                                                                $estadoText = 'Enviada';
                                                                break;
                                                            case 'vendida':
                                                                $badgeClass = 'badge-success';
                                                                $estadoText = 'Vendida';
                                                                break;
                                                        }
                                                        ?>
                                                        <span class="badge <?php echo $badgeClass; ?>"><?php echo $estadoText; ?></span>
                                                    </td>
                                                    <td>
                                                        <small class="text-muted">
                                                            <?php echo date('d/m/Y H:i', strtotime($cotizacion['created_at'])); ?>
                                                        </small>
                                                    </td>
                                                    <td>
                                                        <?php if ($cotizacion['precio_cotizado']): ?>
                                                            <strong>$<?php echo number_format($cotizacion['precio_cotizado'], 0, ',', '.'); ?></strong>
                                                        <?php else: ?>
                                                            <span class="text-muted">Sin cotizar</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <button class="btn btn-sm btn-outline-primary" onclick="verCotizacion(<?php echo $cotizacion['id']; ?>)">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                        <button class="btn btn-sm btn-outline-success" onclick="actualizarEstado(<?php echo $cotizacion['id']; ?>, 'enviada')">
                                                            <i class="fas fa-paper-plane"></i>
                                                        </button>
                                                        <button class="btn btn-sm btn-outline-warning" onclick="actualizarEstado(<?php echo $cotizacion['id']; ?>, 'vendida')">
                                                            <i class="fas fa-check"></i>
                                                        </button>
                                                        <button class="btn btn-sm btn-outline-danger" onclick="eliminarCotizacion(<?php echo $cotizacion['id']; ?>)">
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

    <!-- Modal para ver cotización -->
    <div class="modal fade" id="cotizacionModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detalle de Cotización</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body" id="cotizacionModalBody">
                    <!-- Contenido se cargará dinámicamente -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.4.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.4.1/dist/js/bootstrap.min.js"></script>
    <script src="../js/dark-mode.js"></script>
    
    <script>
        async function verCotizacion(id) {
            try {
                const response = await fetch(`../api/cotizaciones.php?id=${id}`);
                const cotizacion = await response.json();
                
                if (response.ok) {
                    const modalBody = document.getElementById('cotizacionModalBody');
                    modalBody.innerHTML = `
                        <div class="row">
                            <div class="col-md-6">
                                <h6>Información del Cliente</h6>
                                <p><strong>Nombre:</strong> ${cotizacion.nombre_cliente}</p>
                                <p><strong>Email:</strong> ${cotizacion.email_cliente}</p>
                                <p><strong>Teléfono:</strong> ${cotizacion.telefono_cliente || 'No especificado'}</p>
                                <p><strong>Fecha de solicitud:</strong> ${new Date(cotizacion.created_at).toLocaleString()}</p>
                            </div>
                            <div class="col-md-6">
                                <h6>Información del ${cotizacion.tipo_cotizacion === 'producto' ? 'Producto' : 'Servicio'}</h6>
                                ${cotizacion.tipo_cotizacion === 'producto' ? `
                                    <p><strong>Producto:</strong> ${cotizacion.producto_nombre}</p>
                                    <p><strong>Código:</strong> ${cotizacion.producto_codigo}</p>
                                ` : `
                                    <p><strong>Servicio:</strong> ${cotizacion.servicio_nombre}</p>
                                    <p><strong>Slug:</strong> ${cotizacion.servicio_slug}</p>
                                `}
                                <p><strong>Tipo:</strong> <span class="badge badge-${cotizacion.tipo_cotizacion === 'producto' ? 'primary' : 'info'}">${cotizacion.tipo_cotizacion}</span></p>
                                <p><strong>Estado:</strong> <span class="badge badge-${getBadgeClass(cotizacion.estado)}">${cotizacion.estado}</span></p>
                                ${cotizacion.precio_cotizado ? `<p><strong>Precio cotizado:</strong> $${Number(cotizacion.precio_cotizado).toLocaleString()}</p>` : ''}
                                ${cotizacion.presupuesto_estimado ? `<p><strong>Presupuesto estimado:</strong> ${cotizacion.presupuesto_estimado}</p>` : ''}
                                ${cotizacion.fecha_requerida ? `<p><strong>Fecha requerida:</strong> ${new Date(cotizacion.fecha_requerida).toLocaleDateString()}</p>` : ''}
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-12">
                                <h6>Mensaje del Cliente</h6>
                                <p>${cotizacion.mensaje || 'Sin mensaje'}</p>
                            </div>
                        </div>
                        ${cotizacion.detalles_proyecto ? `
                        <div class="row mt-3">
                            <div class="col-12">
                                <h6>Detalles del Proyecto</h6>
                                <p>${cotizacion.detalles_proyecto}</p>
                            </div>
                        </div>
                        ` : ''}
                        ${cotizacion.datos_especificos ? mostrarDatosEspecificos(cotizacion.datos_especificos) : ''}
                        ${cotizacion.notas_admin ? `
                        <div class="row mt-3">
                            <div class="col-12">
                                <h6>Notas del Administrador</h6>
                                <p>${cotizacion.notas_admin}</p>
                            </div>
                        </div>
                        ` : ''}
                    `;
                    
                    $('#cotizacionModal').modal('show');
                } else {
                    alert('Error al cargar la cotización');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error al cargar la cotización');
            }
        }
        
        async function actualizarEstado(id, nuevoEstado) {
            if (!confirm(`¿Estás seguro de que quieres cambiar el estado a "${nuevoEstado}"?`)) {
                return;
            }
            
            try {
                const response = await fetch('../api/cotizaciones.php', {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        id: id,
                        estado: nuevoEstado
                    })
                });
                
                const result = await response.json();
                
                if (response.ok) {
                    alert('Estado actualizado exitosamente');
                    location.reload();
                } else {
                    alert('Error al actualizar el estado');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error al actualizar el estado');
            }
        }
        
        async function eliminarCotizacion(id) {
            if (!confirm('¿Estás seguro de que quieres eliminar esta cotización?')) {
                return;
            }
            
            try {
                const response = await fetch(`../api/cotizaciones.php?id=${id}`, {
                    method: 'DELETE'
                });
                
                const result = await response.json();
                
                if (response.ok) {
                    alert('Cotización eliminada exitosamente');
                    location.reload();
                } else {
                    alert('Error al eliminar la cotización');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error al eliminar la cotización');
            }
        }
        
        function getBadgeClass(estado) {
            switch (estado) {
                case 'solicitada': return 'warning';
                case 'enviada': return 'info';
                case 'vendida': return 'success';
                default: return 'secondary';
            }
        }
        
        function mostrarDatosEspecificos(datosEspecificosStr) {
            try {
                const datos = JSON.parse(datosEspecificosStr);
                let html = `
                    <div class="row mt-3">
                        <div class="col-12">
                            <h6>Datos Específicos del ${datos.tipo === 'muebles' ? 'Mueble' : datos.tipo === '3d' ? 'Proyecto 3D' : 'Diseño'}</h6>
                `;
                
                if (datos.tipo === 'muebles') {
                    html += `
                        <div class="row">
                            <div class="col-md-6">
                                ${datos.tipo_mueble ? `<p><strong>Tipo de mueble:</strong> ${datos.tipo_mueble}</p>` : ''}
                                ${datos.material ? `<p><strong>Material:</strong> ${datos.material}</p>` : ''}
                                ${datos.color ? `<p><strong>Color:</strong> ${datos.color}</p>` : ''}
                                ${datos.tipo_instalacion ? `<p><strong>Instalación:</strong> ${datos.tipo_instalacion}</p>` : ''}
                            </div>
                            <div class="col-md-6">
                                ${datos.dimensiones && (datos.dimensiones.ancho || datos.dimensiones.alto || datos.dimensiones.profundidad) ? `
                                    <p><strong>Dimensiones:</strong> 
                                    ${datos.dimensiones.ancho ? datos.dimensiones.ancho + 'cm (ancho)' : ''} 
                                    ${datos.dimensiones.alto ? datos.dimensiones.alto + 'cm (alto)' : ''} 
                                    ${datos.dimensiones.profundidad ? datos.dimensiones.profundidad + 'cm (prof.)' : ''}</p>
                                ` : ''}
                                ${datos.plazo_entrega ? `<p><strong>Plazo de entrega:</strong> ${datos.plazo_entrega}</p>` : ''}
                            </div>
                        </div>
                        ${datos.descripcion_mueble ? `<p><strong>Descripción:</strong> ${datos.descripcion_mueble}</p>` : ''}
                        ${datos.accesorios ? `<p><strong>Accesorios:</strong> ${datos.accesorios}</p>` : ''}
                    `;
                } else if (datos.tipo === '3d') {
                    html += `
                        <div class="row">
                            <div class="col-md-6">
                                ${datos.tipo_proyecto ? `<p><strong>Tipo de proyecto:</strong> ${datos.tipo_proyecto}</p>` : ''}
                                ${datos.cantidad ? `<p><strong>Cantidad:</strong> ${datos.cantidad} piezas</p>` : ''}
                                ${datos.tamaño_aprox ? `<p><strong>Tamaño aprox:</strong> ${datos.tamaño_aprox}</p>` : ''}
                                ${datos.peso_aprox ? `<p><strong>Peso aprox:</strong> ${datos.peso_aprox}g</p>` : ''}
                            </div>
                            <div class="col-md-6">
                                ${datos.material ? `<p><strong>Material:</strong> ${datos.material}</p>` : ''}
                                ${datos.color ? `<p><strong>Color:</strong> ${datos.color}</p>` : ''}
                                ${datos.archivo_3d ? `<p><strong>Archivo 3D:</strong> ${datos.archivo_3d}</p>` : ''}
                                ${datos.plazo ? `<p><strong>Plazo:</strong> ${datos.plazo}</p>` : ''}
                            </div>
                        </div>
                        ${datos.descripcion_pieza ? `<p><strong>Descripción de la pieza:</strong> ${datos.descripcion_pieza}</p>` : ''}
                        ${datos.requisitos_especiales ? `<p><strong>Requisitos especiales:</strong> ${datos.requisitos_especiales}</p>` : ''}
                        ${datos.observaciones ? `<p><strong>Observaciones:</strong> ${datos.observaciones}</p>` : ''}
                    `;
                } else if (datos.tipo === 'diseno') {
                    html += `
                        <div class="row">
                            <div class="col-md-6">
                                ${datos.tipo_proyecto ? `<p><strong>Tipo de proyecto:</strong> ${datos.tipo_proyecto}</p>` : ''}
                                ${datos.superficie ? `<p><strong>Superficie:</strong> ${datos.superficie} m²</p>` : ''}
                                ${datos.plazo ? `<p><strong>Plazo:</strong> ${datos.plazo}</p>` : ''}
                            </div>
                            <div class="col-md-6">
                                ${datos.servicios && datos.servicios.length > 0 ? `<p><strong>Servicios requeridos:</strong> ${datos.servicios.join(', ')}</p>` : ''}
                            </div>
                        </div>
                        ${datos.descripcion ? `<p><strong>Descripción:</strong> ${datos.descripcion}</p>` : ''}
                    `;
                }
                
                html += `
                        </div>
                    </div>
                `;
                
                return html;
            } catch (e) {
                console.error('Error parsing datos_especificos:', e);
                return '';
            }
        }
    </script>
</body>
</html>
