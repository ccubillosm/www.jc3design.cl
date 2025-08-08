/**
 * Manejador de cotizaciones de servicios
 */

document.addEventListener("DOMContentLoaded", function () {
    console.log("DOM cargado, iniciando cotizacionServicios.js");
    
    // Obtener el formulario de cotización
    const formulario = document.getElementById('formulario-cotizacion');
    if (formulario) {
        formulario.addEventListener('submit', enviarCotizacionServicio);
        console.log("Formulario de cotización encontrado y event listener agregado");
    } else {
        console.log("No se encontró formulario de cotización");
    }
});

/**
 * Función para enviar cotización de servicio
 */
async function enviarCotizacionServicio(event) {
    event.preventDefault();
    console.log("Enviando cotización de servicio...");
    
    // Obtener datos del formulario (incluyendo checkboxes múltiples)
    const formData = new FormData(event.target);
    const datos = {};
    
    // Procesar datos normales
    for (let [key, value] of formData.entries()) {
        if (datos[key]) {
            // Si ya existe, convertir en array
            if (Array.isArray(datos[key])) {
                datos[key].push(value);
            } else {
                datos[key] = [datos[key], value];
            }
        } else {
            datos[key] = value;
        }
    }
    
    console.log("Datos del formulario:", datos);
    
    // Validar datos requeridos
    if (!datos.nombre || !datos.email) {
        alert('Por favor completa todos los campos obligatorios (nombre y email).');
        return;
    }
    
    // Si no hay servicio_id, intentar determinarlo por la página
    if (!datos.servicio_id) {
        const currentPage = window.location.pathname;
        if (currentPage.includes('cotizacion-diseno')) {
            datos.servicio_id = '1'; // Diseño de Muebles 3D
        } else if (currentPage.includes('cotizacion-mueble')) {
            datos.servicio_id = '3'; // Fabricación de Muebles a Medida
        } else if (currentPage.includes('cotizacion-3d')) {
            datos.servicio_id = '2'; // Impresión 3D
        } else {
            alert('Por favor selecciona un tipo de servicio.');
            return;
        }
    }
    
    // Validar email
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(datos.email)) {
        alert('Por favor ingresa un email válido.');
        return;
    }
    
    try {
        // Mostrar indicador de carga
        const submitBtn = event.target.querySelector('button[type="submit"]');
        const textoOriginal = submitBtn.innerHTML;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enviando...';
        submitBtn.disabled = true;
        
        // Preparar datos específicos según el tipo de formulario
        const datosEspecificos = capturarDatosEspecificos(datos);
        
        // Preparar datos de la cotización
        const cotizacionData = {
            tipo_cotizacion: 'servicio',
            servicio_id: parseInt(datos.servicio_id),
            nombre_cliente: datos.nombre,
            email_cliente: datos.email,
            telefono_cliente: datos.telefono || '',
            mensaje: datos.mensaje || '',
            detalles_proyecto: datos.detalles_proyecto || datos['descripcion-pieza'] || datos.descripcion || '',
            presupuesto_estimado: datos.presupuesto_estimado || datos.presupuesto || '',
            fecha_requerida: datos.fecha_requerida || null,
            datos_especificos: JSON.stringify(datosEspecificos)
        };
        
        console.log("Datos de cotización a enviar:", cotizacionData);
        
        // Enviar cotización a la API
        const response = await fetch('../api/cotizaciones.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(cotizacionData)
        });
        
        console.log("Respuesta del servidor:", response.status, response.statusText);
        
        const result = await response.json();
        console.log("Resultado:", result);
        
        if (response.ok) {
            // Mostrar notificación de éxito
            mostrarNotificacionExito('¡Cotización de servicio enviada!', '¡Gracias por elegirnos! Tu solicitud de cotización ha sido enviada exitosamente. Nuestro equipo la revisará y te contactaremos pronto con una propuesta detallada.');
            
            // Limpiar el formulario
            event.target.reset();
        } else {
            throw new Error(result.error || 'Error al enviar la cotización');
        }
        
    } catch (error) {
        console.error('Error:', error);
        mostrarNotificacionError('Error al enviar cotización', error.message || 'Ha ocurrido un error al enviar tu solicitud. Por favor, intenta nuevamente.');
    } finally {
        // Restaurar botón
        const submitBtn = event.target.querySelector('button[type="submit"]');
        submitBtn.innerHTML = '<i class="fas fa-paper-plane mr-2"></i>Solicitar Cotización';
        submitBtn.disabled = false;
    }
}

/**
 * Mostrar notificación de éxito con estilo mejorado
 */
function mostrarNotificacionExito(titulo, mensaje) {
    // Crear contenedor de notificación si no existe
    let contenedorNotificaciones = document.getElementById('notificaciones-container');
    if (!contenedorNotificaciones) {
        contenedorNotificaciones = document.createElement('div');
        contenedorNotificaciones.id = 'notificaciones-container';
        contenedorNotificaciones.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            max-width: 400px;
        `;
        document.body.appendChild(contenedorNotificaciones);
    }
    
    // Crear notificación
    const notificacion = document.createElement('div');
    notificacion.className = 'notificacion-exito';
    notificacion.innerHTML = `
        <div style="
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 8px 32px rgba(40, 167, 69, 0.3);
            margin-bottom: 15px;
            animation: slideInRight 0.5s ease-out;
            border-left: 5px solid #155724;
        ">
            <div style="display: flex; align-items: center; margin-bottom: 8px;">
                <i class="fas fa-check-circle" style="font-size: 24px; margin-right: 12px;"></i>
                <h5 style="margin: 0; font-weight: 600;">${titulo}</h5>
            </div>
            <p style="margin: 0; font-size: 14px; line-height: 1.4;">${mensaje}</p>
        </div>
    `;
    
    contenedorNotificaciones.appendChild(notificacion);
    
    // Auto-remover después de 6 segundos
    setTimeout(() => {
        notificacion.style.animation = 'slideOutRight 0.5s ease-out';
        setTimeout(() => {
            if (notificacion.parentNode) {
                notificacion.parentNode.removeChild(notificacion);
            }
        }, 500);
    }, 6000);
}

/**
 * Mostrar notificación de error con estilo mejorado
 */
function mostrarNotificacionError(titulo, mensaje) {
    // Crear contenedor de notificación si no existe
    let contenedorNotificaciones = document.getElementById('notificaciones-container');
    if (!contenedorNotificaciones) {
        contenedorNotificaciones = document.createElement('div');
        contenedorNotificaciones.id = 'notificaciones-container';
        contenedorNotificaciones.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            max-width: 400px;
        `;
        document.body.appendChild(contenedorNotificaciones);
    }
    
    // Crear notificación
    const notificacion = document.createElement('div');
    notificacion.className = 'notificacion-error';
    notificacion.innerHTML = `
        <div style="
            background: linear-gradient(135deg, #dc3545, #e74c3c);
            color: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 8px 32px rgba(220, 53, 69, 0.3);
            margin-bottom: 15px;
            animation: slideInRight 0.5s ease-out;
            border-left: 5px solid #721c24;
        ">
            <div style="display: flex; align-items: center; margin-bottom: 8px;">
                <i class="fas fa-exclamation-circle" style="font-size: 24px; margin-right: 12px;"></i>
                <h5 style="margin: 0; font-weight: 600;">${titulo}</h5>
            </div>
            <p style="margin: 0; font-size: 14px; line-height: 1.4;">${mensaje}</p>
        </div>
    `;
    
    contenedorNotificaciones.appendChild(notificacion);
    
    // Auto-remover después de 8 segundos
    setTimeout(() => {
        notificacion.style.animation = 'slideOutRight 0.5s ease-out';
        setTimeout(() => {
            if (notificacion.parentNode) {
                notificacion.parentNode.removeChild(notificacion);
            }
        }, 500);
    }, 8000);
}

/**
 * Cargar servicios disponibles
 */
async function cargarServicios() {
    try {
        console.log("Cargando servicios...");
        const response = await fetch('../api/servicios.php');
        const data = await response.json();
        
        if (response.ok) {
            console.log("Servicios cargados:", data.servicios);
            return data.servicios;
        } else {
            console.error("Error al cargar servicios:", data.error);
            return [];
        }
    } catch (error) {
        console.error("Error al cargar servicios:", error);
        return [];
    }
}

/**
 * Poblar select de servicios
 */
async function poblarSelectServicios(selectId) {
    const select = document.getElementById(selectId);
    if (!select) return;
    
    const servicios = await cargarServicios();
    
    // Limpiar opciones existentes (excepto la primera)
    const primerOpcion = select.firstElementChild;
    select.innerHTML = '';
    if (primerOpcion) {
        select.appendChild(primerOpcion);
    }
    
    // Determinar página actual para preseleccionar servicio
    const currentPage = window.location.pathname;
    let servicioRecomendado = null;
    
    if (currentPage.includes('cotizacion-diseno')) {
        servicioRecomendado = 'diseno-muebles-3d';
    } else if (currentPage.includes('cotizacion-mueble')) {
        servicioRecomendado = 'fabricacion-muebles';
    } else if (currentPage.includes('cotizacion-3d')) {
        servicioRecomendado = 'impresion-3d';
    }
    
    // Agregar servicios
    servicios.forEach(servicio => {
        const option = document.createElement('option');
        option.value = servicio.id;
        option.textContent = `${servicio.nombre} - ${servicio.precio_mostrar}`;
        
        // Preseleccionar servicio recomendado
        if (servicio.slug === servicioRecomendado) {
            option.selected = true;
        }
        
        select.appendChild(option);
    });
}

/**
 * Capturar datos específicos según el tipo de formulario
 */
function capturarDatosEspecificos(datos) {
    const currentPage = window.location.pathname;
    const datosEspecificos = {};
    
    if (currentPage.includes('cotizacion-mueble')) {
        // Datos específicos para cotización de muebles
        datosEspecificos.tipo = 'muebles';
        datosEspecificos.tipo_mueble = datos['tipo-mueble'] || '';
        datosEspecificos.dimensiones = {
            ancho: datos.ancho || '',
            alto: datos.alto || '',
            profundidad: datos.profundidad || ''
        };
        datosEspecificos.descripcion_mueble = datos['descripcion-mueble'] || '';
        datosEspecificos.material = datos.material || '';
        datosEspecificos.color = datos.color || '';
        datosEspecificos.accesorios = datos.accesorios || '';
        datosEspecificos.tipo_instalacion = datos['tipo-instalacion'] || '';
        datosEspecificos.plazo_entrega = datos['plazo-entrega'] || '';
        
    } else if (currentPage.includes('cotizacion-3d')) {
        // Datos específicos para cotización 3D
        datosEspecificos.tipo = '3d';
        datosEspecificos.tipo_proyecto = datos['tipo-proyecto'] || '';
        datosEspecificos.descripcion_pieza = datos['descripcion-pieza'] || '';
        datosEspecificos.cantidad = datos.cantidad || '';
        datosEspecificos.tamaño_aprox = datos['tamaño-aprox'] || '';
        datosEspecificos.peso_aprox = datos['peso-aprox'] || '';
        datosEspecificos.material = datos.material || '';
        datosEspecificos.color = datos.color || '';
        datosEspecificos.requisitos_especiales = datos['requisitos-especiales'] || '';
        datosEspecificos.archivo_3d = datos['archivo-3d'] || '';
        datosEspecificos.observaciones = datos.observaciones || '';
        datosEspecificos.plazo = datos.plazo || '';
        
    } else if (currentPage.includes('cotizacion-diseno')) {
        // Datos específicos para cotización de diseño
        datosEspecificos.tipo = 'diseno';
        datosEspecificos.tipo_proyecto = datos['tipo-proyecto'] || '';
        datosEspecificos.superficie = datos.superficie || '';
        datosEspecificos.descripcion = datos.descripcion || '';
        datosEspecificos.servicios = [];
        
        // Capturar servicios seleccionados (checkboxes)
        if (datos['servicios[]']) {
            if (Array.isArray(datos['servicios[]'])) {
                datosEspecificos.servicios = datos['servicios[]'];
            } else {
                datosEspecificos.servicios.push(datos['servicios[]']);
            }
        }
        
        datosEspecificos.plazo = datos.plazo || '';
    }
    
    console.log('Datos específicos capturados:', datosEspecificos);
    return datosEspecificos;
}

/**
 * Agregar campo de servicio si no existe
 */
function agregarCampoServicio() {
    const formulario = document.getElementById('formulario-cotizacion');
    if (!formulario) return;
    
    let servicioField = document.getElementById('servicio_id');
    
    // Si no existe el campo servicio_id, crearlo como campo oculto
    if (!servicioField) {
        servicioField = document.createElement('input');
        servicioField.type = 'hidden';
        servicioField.id = 'servicio_id';
        servicioField.name = 'servicio_id';
        
        // Determinar servicio por página
        const currentPage = window.location.pathname;
        if (currentPage.includes('cotizacion-diseno')) {
            servicioField.value = '1'; // Diseño de Muebles 3D
        } else if (currentPage.includes('cotizacion-mueble')) {
            servicioField.value = '3'; // Fabricación de Muebles a Medida
        } else if (currentPage.includes('cotizacion-3d')) {
            servicioField.value = '2'; // Impresión 3D
        }
        
        formulario.appendChild(servicioField);
        console.log('Campo servicio_id agregado automáticamente:', servicioField.value);
    }
}

// Cargar servicios cuando se carga la página
document.addEventListener("DOMContentLoaded", function () {
    // Intentar poblar el select si existe
    poblarSelectServicios('servicio_id');
    
    // Agregar campo oculto si no existe el select
    setTimeout(() => {
        agregarCampoServicio();
    }, 100);
});
