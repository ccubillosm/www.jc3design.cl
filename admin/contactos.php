<?php
/**
 * Panel de administración - Gestión de Contactos (Mini CRM)
 */

session_start();
require_once '../database/config.php';

// Verificar autenticación
if (!isAuthenticated()) {
    header('Location: login.php');
    exit;
}

// Obtener información del usuario
$db = getDB();
$usuario = $db->fetchOne("SELECT * FROM usuarios WHERE id = ?", [$_SESSION['user_id']]);

$pageTitle = "Gestión de Contactos - Mini CRM";
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> | JC3Design Admin</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.4.1/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/dark-mode.css">
    <link rel="stylesheet" href="../css/admin-dark-mode.css">
    <link rel="stylesheet" href="../css/chart-dark-mode.css">
    <style>
        .admin-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
        }
        
        .stats-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border-left: 4px solid;
        }
        
        .stats-card.nuevo { border-left-color: #28a745; }
        .stats-card.contactado { border-left-color: #17a2b8; }
        .stats-card.en-proceso { border-left-color: #ffc107; }
        .stats-card.cerrado { border-left-color: #6c757d; }
        
        .contacto-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: transform 0.2s;
        }
        
        .contacto-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.15);
        }
        
        .prioridad-badge {
            position: absolute;
            top: 10px;
            right: 10px;
        }
        
        .prioridad-urgente { background-color: #dc3545; }
        .prioridad-alta { background-color: #fd7e14; }
        .prioridad-media { background-color: #ffc107; }
        .prioridad-baja { background-color: #28a745; }
        
        .estado-badge {
            font-size: 0.8rem;
            padding: 0.3rem 0.6rem;
        }
        
        .filtros-section {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .contacto-detail {
            max-width: 600px;
        }
        
        .timeline-item {
            border-left: 2px solid #dee2e6;
            padding-left: 1rem;
            margin-bottom: 1rem;
            position: relative;
        }
        
        .timeline-item::before {
            content: '';
            position: absolute;
            left: -6px;
            top: 0.5rem;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background-color: #007bff;
        }
        
        .btn-sm {
            font-size: 0.8rem;
            padding: 0.25rem 0.5rem;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="admin-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h1><i class="fas fa-address-book mr-2"></i><?php echo $pageTitle; ?></h1>
                    <p class="mb-0">Gestiona todas las consultas y contactos de clientes</p>
                </div>
                <div class="col-md-6 text-right">
                    <a href="dashboard.php" class="btn btn-primary mr-2">
                        <i class="fas fa-chart-line mr-1"></i>Dashboard de Ventas
                    </a>
                    <a href="index.php" class="btn btn-light mr-2">
                        <i class="fas fa-arrow-left mr-1"></i>Volver al Dashboard
                    </a>
                    <a href="logout.php" class="btn btn-outline-light">
                        <i class="fas fa-sign-out-alt mr-1"></i>Cerrar Sesión
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="container-fluid">
        <!-- Estadísticas -->
        <div class="row mb-4" id="estadisticas">
            <!-- Se cargarán vía JavaScript -->
        </div>

        <!-- Filtros -->
        <div class="filtros-section">
            <h4><i class="fas fa-filter mr-2"></i>Filtros</h4>
            <div class="row">
                <div class="col-md-3">
                    <label for="filtro-estado">Estado:</label>
                    <select class="form-control" id="filtro-estado">
                        <option value="">Todos</option>
                        <option value="nuevo">Nuevo</option>
                        <option value="contactado">Contactado</option>
                        <option value="en_proceso">En Proceso</option>
                        <option value="cerrado">Cerrado</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="filtro-tipo">Tipo:</label>
                    <select class="form-control" id="filtro-tipo">
                        <option value="">Todos</option>
                        <option value="cotizacion">Cotización</option>
                        <option value="consulta">Consulta</option>
                        <option value="felicitacion">Felicitación</option>
                        <option value="reclamo">Reclamo</option>
                        <option value="sugerencia">Sugerencia</option>
                        <option value="trabajo">Trabajo</option>
                        <option value="otro">Otro</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="filtro-prioridad">Prioridad:</label>
                    <select class="form-control" id="filtro-prioridad">
                        <option value="">Todas</option>
                        <option value="urgente">Urgente</option>
                        <option value="alta">Alta</option>
                        <option value="media">Media</option>
                        <option value="baja">Baja</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="filtro-buscar">Buscar:</label>
                    <input type="text" class="form-control" id="filtro-buscar" placeholder="Nombre, email, asunto...">
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-12">
                    <button class="btn btn-primary" onclick="aplicarFiltros()">
                        <i class="fas fa-search mr-1"></i>Buscar
                    </button>
                    <button class="btn btn-secondary ml-2" onclick="limpiarFiltros()">
                        <i class="fas fa-eraser mr-1"></i>Limpiar
                    </button>
                    <button class="btn btn-success ml-2" onclick="exportarContactos()">
                        <i class="fas fa-download mr-1"></i>Exportar CSV
                    </button>
                </div>
            </div>
        </div>

        <!-- Lista de contactos -->
        <div class="row">
            <div class="col-md-8">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4><i class="fas fa-list mr-2"></i>Contactos <span id="total-contactos" class="badge badge-primary">0</span></h4>
                    <div>
                        <button class="btn btn-sm btn-outline-secondary" onclick="cambiarVista('lista')">
                            <i class="fas fa-list"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-secondary ml-1" onclick="cambiarVista('tarjetas')">
                            <i class="fas fa-th"></i>
                        </button>
                    </div>
                </div>
                
                <!-- Loading -->
                <div id="loading" class="text-center py-4">
                    <i class="fas fa-spinner fa-spin fa-2x"></i>
                    <p>Cargando contactos...</p>
                </div>
                
                <!-- Lista de contactos -->
                <div id="contactos-lista">
                    <!-- Se cargarán vía JavaScript -->
                </div>
                
                <!-- Paginación -->
                <nav id="paginacion" class="mt-4">
                    <!-- Se cargará vía JavaScript -->
                </nav>
            </div>
            
            <div class="col-md-4">
                <div id="contacto-detalle" class="contacto-detail">
                    <div class="card">
                        <div class="card-body text-center text-muted">
                            <i class="fas fa-hand-pointer fa-3x mb-3"></i>
                            <p>Selecciona un contacto para ver los detalles</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para editar contacto -->
    <div class="modal fade" id="modalEditarContacto" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Editar Contacto</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="formEditarContacto">
                        <input type="hidden" id="edit-contacto-id">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="edit-estado">Estado *</label>
                                    <select class="form-control" id="edit-estado" required>
                                        <option value="nuevo">Nuevo</option>
                                        <option value="contactado">Contactado</option>
                                        <option value="en_proceso">En Proceso</option>
                                        <option value="cerrado">Cerrado</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="edit-prioridad">Prioridad *</label>
                                    <select class="form-control" id="edit-prioridad" required>
                                        <option value="baja">Baja</option>
                                        <option value="media">Media</option>
                                        <option value="alta">Alta</option>
                                        <option value="urgente">Urgente</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="edit-asignado">Asignar a:</label>
                            <select class="form-control" id="edit-asignado">
                                <option value="">Sin asignar</option>
                                <!-- Se cargarán los usuarios vía JavaScript -->
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="edit-notas">Notas Administrativas:</label>
                            <textarea class="form-control" id="edit-notas" rows="4" placeholder="Agregar notas internas sobre este contacto..."></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" onclick="guardarCambios()">
                        <i class="fas fa-save mr-1"></i>Guardar Cambios
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.4.1/dist/js/bootstrap.min.js"></script>
    
    <script>
        // Variables globales
        let contactos = [];
        let contactoSeleccionado = null;
        let vistaActual = 'tarjetas';
        let paginaActual = 1;
        let filtrosActivos = {};
        
        // Inicializar cuando se carga la página
        $(document).ready(function() {
            cargarEstadisticas();
            cargarContactos();
            cargarUsuarios();
            
            // Auto-aplicar filtros cuando cambian
            $('#filtro-estado, #filtro-tipo, #filtro-prioridad').change(aplicarFiltros);
            
            // Buscar con delay
            let timeoutBuscar;
            $('#filtro-buscar').on('input', function() {
                clearTimeout(timeoutBuscar);
                timeoutBuscar = setTimeout(aplicarFiltros, 500);
            });
        });
        
        // Cargar estadísticas
        function cargarEstadisticas() {
            fetch('../api/contactos.php')
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        mostrarEstadisticas(data.data);
                    } else {
                        console.error('Error en respuesta:', data);
                    }
                })
                .catch(error => {
                    console.error('Error al cargar estadísticas:', error);
                    mostrarError('Error al cargar estadísticas: ' + error.message);
                });
        }
        
        // Mostrar estadísticas
        function mostrarEstadisticas(contactos) {
            const stats = {
                nuevo: contactos.filter(c => c.estado === 'nuevo').length,
                contactado: contactos.filter(c => c.estado === 'contactado').length,
                en_proceso: contactos.filter(c => c.estado === 'en_proceso').length,
                cerrado: contactos.filter(c => c.estado === 'cerrado').length
            };
            
            const html = `
                <div class="col-md-3">
                    <div class="stats-card nuevo">
                        <h3>${stats.nuevo}</h3>
                        <p><i class="fas fa-star mr-1"></i>Nuevos</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card contactado">
                        <h3>${stats.contactado}</h3>
                        <p><i class="fas fa-phone mr-1"></i>Contactados</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card en-proceso">
                        <h3>${stats.en_proceso}</h3>
                        <p><i class="fas fa-cog mr-1"></i>En Proceso</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card cerrado">
                        <h3>${stats.cerrado}</h3>
                        <p><i class="fas fa-check mr-1"></i>Cerrados</p>
                    </div>
                </div>
            `;
            
            $('#estadisticas').html(html);
        }
        
        // Cargar contactos
        function cargarContactos(page = 1) {
            $('#loading').show();
            
            let url = `../api/contactos.php?page=${page}`;
            
            // Agregar filtros a la URL
            Object.keys(filtrosActivos).forEach(key => {
                if (filtrosActivos[key]) {
                    url += `&${key}=${encodeURIComponent(filtrosActivos[key])}`;
                }
            });
            
            console.log('Cargando contactos desde:', url);
            
            fetch(url)
                .then(response => {
                    console.log('Response status:', response.status);
                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                    }
                    return response.json();
                })
                .then(data => {
                    $('#loading').hide();
                    console.log('Datos recibidos:', data);
                    if (data.success) {
                        contactos = data.data;
                        mostrarContactos(data.data);
                        mostrarPaginacion(data.pagination);
                        $('#total-contactos').text(data.pagination.total_items);
                    } else {
                        mostrarError('Error al cargar contactos: ' + (data.error || 'Error desconocido'));
                    }
                })
                .catch(error => {
                    $('#loading').hide();
                    console.error('Error al cargar contactos:', error);
                    mostrarError('Error de conexión: ' + error.message);
                });
        }
        
        // Mostrar contactos
        function mostrarContactos(contactos) {
            const container = $('#contactos-lista');
            
            if (contactos.length === 0) {
                container.html(`
                    <div class="text-center py-4">
                        <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                        <p class="text-muted">No se encontraron contactos</p>
                    </div>
                `);
                return;
            }
            
            if (vistaActual === 'tarjetas') {
                mostrarContactosTarjetas(contactos);
            } else {
                mostrarContactosLista(contactos);
            }
        }
        
        // Mostrar contactos como tarjetas
        function mostrarContactosTarjetas(contactos) {
            let html = '<div class="row">';
            
            contactos.forEach(contacto => {
                html += `
                    <div class="col-md-6 mb-3">
                        <div class="contacto-card position-relative" onclick="seleccionarContacto(${contacto.id})">
                            <span class="badge prioridad-${contacto.prioridad} prioridad-badge">${contacto.prioridad.toUpperCase()}</span>
                            
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h6 class="mb-0">${contacto.nombre}</h6>
                                <span class="badge estado-badge badge-${getEstadoColor(contacto.estado)}">${formatearEstado(contacto.estado)}</span>
                            </div>
                            
                            <p class="text-muted small mb-1">
                                <i class="fas fa-envelope mr-1"></i>${contacto.email}
                            </p>
                            
                            <p class="text-muted small mb-2">
                                <i class="fas fa-tag mr-1"></i>${formatearTipo(contacto.tipo_consulta)}
                            </p>
                            
                            <p class="mb-2"><strong>${contacto.asunto}</strong></p>
                            
                            <p class="text-muted small mb-2">${truncarTexto(contacto.mensaje, 100)}</p>
                            
                            <div class="d-flex justify-content-between align-items-center">
                                <small class="text-muted">
                                    <i class="fas fa-clock mr-1"></i>${formatearFecha(contacto.created_at)}
                                </small>
                                <div>
                                    <button class="btn btn-sm btn-outline-primary" onclick="event.stopPropagation(); editarContacto(${contacto.id})">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    ${usuario.rol === 'admin' ? `<button class="btn btn-sm btn-outline-danger ml-1" onclick="event.stopPropagation(); eliminarContacto(${contacto.id})">
                                        <i class="fas fa-trash"></i>
                                    </button>` : ''}
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            });
            
            html += '</div>';
            $('#contactos-lista').html(html);
        }
        
        // Mostrar contactos como lista
        function mostrarContactosLista(contactos) {
            let html = `
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Nombre</th>
                                <th>Email</th>
                                <th>Tipo</th>
                                <th>Estado</th>
                                <th>Prioridad</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
            `;
            
            contactos.forEach(contacto => {
                html += `
                    <tr onclick="seleccionarContacto(${contacto.id})" style="cursor: pointer;">
                        <td>${formatearFecha(contacto.created_at)}</td>
                        <td>${contacto.nombre}</td>
                        <td>${contacto.email}</td>
                        <td><span class="badge badge-info">${formatearTipo(contacto.tipo_consulta)}</span></td>
                        <td><span class="badge badge-${getEstadoColor(contacto.estado)}">${formatearEstado(contacto.estado)}</span></td>
                        <td><span class="badge prioridad-${contacto.prioridad}">${contacto.prioridad.toUpperCase()}</span></td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary" onclick="event.stopPropagation(); editarContacto(${contacto.id})">
                                <i class="fas fa-edit"></i>
                            </button>
                            ${usuario.rol === 'admin' ? `<button class="btn btn-sm btn-outline-danger ml-1" onclick="event.stopPropagation(); eliminarContacto(${contacto.id})">
                                <i class="fas fa-trash"></i>
                            </button>` : ''}
                        </td>
                    </tr>
                `;
            });
            
            html += '</tbody></table></div>';
            $('#contactos-lista').html(html);
        }
        
        // Seleccionar contacto
        function seleccionarContacto(id) {
            contactoSeleccionado = contactos.find(c => c.id == id);
            if (contactoSeleccionado) {
                mostrarDetalleContacto(contactoSeleccionado);
            }
        }
        
        // Mostrar detalle del contacto
        function mostrarDetalleContacto(contacto) {
            const preferencias = contacto.preferencias_contacto ? 
                (Array.isArray(contacto.preferencias_contacto) ? contacto.preferencias_contacto : []) : [];
            
            const html = `
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-user mr-2"></i>${contacto.nombre}
                            <span class="badge badge-${getEstadoColor(contacto.estado)} ml-2">${formatearEstado(contacto.estado)}</span>
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <h6><i class="fas fa-envelope mr-1"></i>Información de Contacto</h6>
                            <p class="mb-1"><strong>Email:</strong> <a href="mailto:${contacto.email}">${contacto.email}</a></p>
                            ${contacto.telefono ? `<p class="mb-1"><strong>Teléfono:</strong> <a href="tel:${contacto.telefono}">${contacto.telefono}</a></p>` : ''}
                            ${contacto.ciudad ? `<p class="mb-1"><strong>Ciudad:</strong> ${contacto.ciudad}</p>` : ''}
                        </div>
                        
                        <div class="mb-3">
                            <h6><i class="fas fa-comment mr-1"></i>Consulta</h6>
                            <p class="mb-1"><strong>Tipo:</strong> <span class="badge badge-info">${formatearTipo(contacto.tipo_consulta)}</span></p>
                            <p class="mb-1"><strong>Asunto:</strong> ${contacto.asunto}</p>
                            <p class="mb-1"><strong>Mensaje:</strong></p>
                            <div class="bg-light p-2 rounded">${contacto.mensaje.replace(/\n/g, '<br>')}</div>
                        </div>
                        
                        ${contacto.presupuesto || contacto.plazo ? `
                        <div class="mb-3">
                            <h6><i class="fas fa-info-circle mr-1"></i>Información del Proyecto</h6>
                            ${contacto.presupuesto ? `<p class="mb-1"><strong>Presupuesto:</strong> ${contacto.presupuesto}</p>` : ''}
                            ${contacto.plazo ? `<p class="mb-1"><strong>Plazo:</strong> ${contacto.plazo}</p>` : ''}
                        </div>` : ''}
                        
                        <div class="mb-3">
                            <h6><i class="fas fa-cog mr-1"></i>Gestión</h6>
                            <p class="mb-1"><strong>Prioridad:</strong> <span class="badge prioridad-${contacto.prioridad}">${contacto.prioridad.toUpperCase()}</span></p>
                            ${contacto.asignado_nombre ? `<p class="mb-1"><strong>Asignado a:</strong> ${contacto.asignado_nombre} ${contacto.asignado_apellido}</p>` : ''}
                            ${contacto.notas_admin ? `
                            <p class="mb-1"><strong>Notas:</strong></p>
                            <div class="bg-warning p-2 rounded">${contacto.notas_admin.replace(/\n/g, '<br>')}</div>
                            ` : ''}
                        </div>
                        
                        <div class="mb-3">
                            <h6><i class="fas fa-clock mr-1"></i>Timeline</h6>
                            <div class="timeline-item">
                                <small class="text-muted">Creado: ${formatearFechaCompleta(contacto.created_at)}</small>
                            </div>
                            ${contacto.fecha_contacto ? `
                            <div class="timeline-item">
                                <small class="text-muted">Contactado: ${formatearFechaCompleta(contacto.fecha_contacto)}</small>
                            </div>` : ''}
                            ${contacto.fecha_cierre ? `
                            <div class="timeline-item">
                                <small class="text-muted">Cerrado: ${formatearFechaCompleta(contacto.fecha_cierre)}</small>
                            </div>` : ''}
                        </div>
                        
                        <div class="text-center">
                            <button class="btn btn-primary btn-sm" onclick="editarContacto(${contacto.id})">
                                <i class="fas fa-edit mr-1"></i>Editar
                            </button>
                            <a href="mailto:${contacto.email}" class="btn btn-info btn-sm ml-1">
                                <i class="fas fa-reply mr-1"></i>Responder
                            </a>
                            ${contacto.telefono ? `
                            <a href="tel:${contacto.telefono}" class="btn btn-success btn-sm ml-1">
                                <i class="fas fa-phone mr-1"></i>Llamar
                            </a>` : ''}
                        </div>
                    </div>
                </div>
            `;
            
            $('#contacto-detalle').html(html);
        }
        
        // Editar contacto
        function editarContacto(id) {
            const contacto = contactos.find(c => c.id == id);
            if (!contacto) return;
            
            $('#edit-contacto-id').val(contacto.id);
            $('#edit-estado').val(contacto.estado);
            $('#edit-prioridad').val(contacto.prioridad);
            $('#edit-asignado').val(contacto.asignado_a || '');
            $('#edit-notas').val(contacto.notas_admin || '');
            
            $('#modalEditarContacto').modal('show');
        }
        
        // Guardar cambios
        function guardarCambios() {
            const contactoId = $('#edit-contacto-id').val();
            const data = {
                estado: $('#edit-estado').val(),
                prioridad: $('#edit-prioridad').val(),
                asignado_a: $('#edit-asignado').val() || null,
                notas_admin: $('#edit-notas').val()
            };
            
            fetch(`../api/contactos.php?id=${contactoId}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    $('#modalEditarContacto').modal('hide');
                    cargarContactos(paginaActual);
                    cargarEstadisticas();
                    mostrarAlerta('Contacto actualizado exitosamente', 'success');
                } else {
                    mostrarAlerta('Error al actualizar contacto', 'danger');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                mostrarAlerta('Error de conexión', 'danger');
            });
        }
        
        // Eliminar contacto
        function eliminarContacto(id) {
            if (!confirm('¿Estás seguro de que quieres eliminar este contacto?')) return;
            
            fetch(`../api/contactos.php?id=${id}`, {
                method: 'DELETE'
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    cargarContactos(paginaActual);
                    cargarEstadisticas();
                    mostrarAlerta('Contacto eliminado exitosamente', 'success');
                    
                    // Limpiar detalle si era el contacto seleccionado
                    if (contactoSeleccionado && contactoSeleccionado.id == id) {
                        $('#contacto-detalle').html(`
                            <div class="card">
                                <div class="card-body text-center text-muted">
                                    <i class="fas fa-hand-pointer fa-3x mb-3"></i>
                                    <p>Selecciona un contacto para ver los detalles</p>
                                </div>
                            </div>
                        `);
                    }
                } else {
                    mostrarAlerta('Error al eliminar contacto', 'danger');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                mostrarAlerta('Error de conexión', 'danger');
            });
        }
        
        // Cargar usuarios para asignación
        function cargarUsuarios() {
            // Aquí deberías hacer una llamada a la API para obtener usuarios
            // Por ahora, simularemos algunos usuarios
            const usuarios = [
                { id: 1, nombre: 'Administrador', apellido: 'JC3Design' }
            ];
            
            let options = '<option value="">Sin asignar</option>';
            usuarios.forEach(usuario => {
                options += `<option value="${usuario.id}">${usuario.nombre} ${usuario.apellido}</option>`;
            });
            
            $('#edit-asignado').html(options);
        }
        
        // Aplicar filtros
        function aplicarFiltros() {
            filtrosActivos = {
                estado: $('#filtro-estado').val(),
                tipo: $('#filtro-tipo').val(),
                prioridad: $('#filtro-prioridad').val(),
                search: $('#filtro-buscar').val()
            };
            
            paginaActual = 1;
            cargarContactos(1);
        }
        
        // Limpiar filtros
        function limpiarFiltros() {
            $('#filtro-estado, #filtro-tipo, #filtro-prioridad').val('');
            $('#filtro-buscar').val('');
            filtrosActivos = {};
            paginaActual = 1;
            cargarContactos(1);
        }
        
        // Cambiar vista
        function cambiarVista(vista) {
            vistaActual = vista;
            mostrarContactos(contactos);
        }
        
        // Exportar contactos
        function exportarContactos() {
            let url = '../api/contactos.php?export=csv';
            
            Object.keys(filtrosActivos).forEach(key => {
                if (filtrosActivos[key]) {
                    url += `&${key}=${encodeURIComponent(filtrosActivos[key])}`;
                }
            });
            
            window.open(url, '_blank');
        }
        
        // Mostrar paginación
        function mostrarPaginacion(pagination) {
            if (pagination.total_pages <= 1) {
                $('#paginacion').html('');
                return;
            }
            
            let html = '<ul class="pagination justify-content-center">';
            
            // Botón anterior
            if (pagination.current_page > 1) {
                html += `<li class="page-item">
                    <a class="page-link" href="#" onclick="cargarContactos(${pagination.current_page - 1})">Anterior</a>
                </li>`;
            }
            
            // Páginas
            for (let i = 1; i <= pagination.total_pages; i++) {
                if (i === pagination.current_page) {
                    html += `<li class="page-item active">
                        <span class="page-link">${i}</span>
                    </li>`;
                } else {
                    html += `<li class="page-item">
                        <a class="page-link" href="#" onclick="cargarContactos(${i})">${i}</a>
                    </li>`;
                }
            }
            
            // Botón siguiente
            if (pagination.current_page < pagination.total_pages) {
                html += `<li class="page-item">
                    <a class="page-link" href="#" onclick="cargarContactos(${pagination.current_page + 1})">Siguiente</a>
                </li>`;
            }
            
            html += '</ul>';
            $('#paginacion').html(html);
            
            paginaActual = pagination.current_page;
        }
        
        // Funciones auxiliares
        function formatearEstado(estado) {
            const estados = {
                'nuevo': 'Nuevo',
                'contactado': 'Contactado',
                'en_proceso': 'En Proceso',
                'cerrado': 'Cerrado'
            };
            return estados[estado] || estado;
        }
        
        function formatearTipo(tipo) {
            const tipos = {
                'cotizacion': 'Cotización',
                'consulta': 'Consulta',
                'felicitacion': 'Felicitación',
                'reclamo': 'Reclamo',
                'sugerencia': 'Sugerencia',
                'trabajo': 'Trabajo',
                'otro': 'Otro'
            };
            return tipos[tipo] || tipo;
        }
        
        function getEstadoColor(estado) {
            const colores = {
                'nuevo': 'success',
                'contactado': 'info',
                'en_proceso': 'warning',
                'cerrado': 'secondary'
            };
            return colores[estado] || 'primary';
        }
        
        function formatearFecha(fecha) {
            return new Date(fecha).toLocaleDateString('es-CL');
        }
        
        function formatearFechaCompleta(fecha) {
            return new Date(fecha).toLocaleString('es-CL');
        }
        
        function truncarTexto(texto, limite) {
            if (texto.length <= limite) return texto;
            return texto.substring(0, limite) + '...';
        }
        
        function mostrarAlerta(mensaje, tipo) {
            const alerta = `
                <div class="alert alert-${tipo} alert-dismissible fade show" role="alert">
                    ${mensaje}
                    <button type="button" class="close" data-dismiss="alert">
                        <span>&times;</span>
                    </button>
                </div>
            `;
            
            // Insertar al inicio del container
            $('.container-fluid').prepend(alerta);
            
            // Auto-ocultar después de 5 segundos
            setTimeout(() => {
                $('.alert').fadeOut();
            }, 5000);
        }
        
        function mostrarError(mensaje) {
            $('#contactos-lista').html(`
                <div class="text-center py-4">
                    <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3"></i>
                    <p class="text-muted">${mensaje}</p>
                    <button class="btn btn-primary" onclick="cargarContactos()">Reintentar</button>
                </div>
            `);
        }
        
        // Información del usuario para JavaScript
        const usuario = <?php echo json_encode($usuario); ?>;
    </script>
    <script src="../js/dark-mode.js"></script>
</body>
</html>
