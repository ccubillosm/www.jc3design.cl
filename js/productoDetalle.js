document.addEventListener("DOMContentLoaded", function () {
  const contenedor = document.getElementById("producto-detalle");
  
  // Obtener parámetros de la URL
  const urlParams = new URLSearchParams(window.location.search);
  const id = urlParams.get("id");
  const tipo = urlParams.get("tipo");
  
  if (!id) {
    contenedor.innerHTML = `
      <div class="text-center">
        <h3 class="mb-4">Producto no encontrado</h3>
        <p class="text-muted">No se especificó un ID de producto válido.</p>
        <a href="productos.html" class="btn btn-custom mt-3">Volver a productos</a>
      </div>
    `;
    return;
  }
  
  // Cargar producto desde la API
  cargarProductoDetalle(id);
});

/**
 * Cargar detalle del producto desde la API
 */
async function cargarProductoDetalle(id) {
  const contenedor = document.getElementById("producto-detalle");
  
  try {
    // Mostrar loading
    contenedor.innerHTML = `
      <div class="text-center">
        <div class="spinner-border text-primary" role="status">
          <span class="sr-only">Cargando...</span>
        </div>
        <p class="mt-3">Cargando producto...</p>
      </div>
    `;
    
    // Hacer petición a la API
    const url = `../api/productos.php?id=${id}`;
    console.log("Cargando producto con ID:", id);
    const response = await fetch(url);
    
    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }
    
    const producto = await response.json();
    console.log("Producto cargado:", producto);
    
    if (producto.error) {
      throw new Error(producto.error);
    }

    // --- MEJORA SEO ---
    actualizarMetaTags(producto);
    
    // Renderizar el producto
    renderizarProductoDetalle(producto);
    
  } catch (error) {
    console.error('Error al cargar producto:', error);
    contenedor.innerHTML = `
      <div class="text-center">
        <h3 class="mb-4 text-danger">Error al cargar producto</h3>
        <p class="text-muted">El producto con ID "${id}" no existe o ha ocurrido un error.</p>
        <a href="productos.html" class="btn btn-custom mt-3">
          <i class="fas fa-arrow-left mr-2"></i>Volver a productos
        </a>
      </div>
    `;
  }
}

/**
 * Renderizar detalle del producto
 */
function renderizarProductoDetalle(producto) {
  const contenedor = document.getElementById("producto-detalle");
  
  // Breadcrumbs dinámicos para SEO y navegación
  const breadcrumbs = `
    <nav aria-label="breadcrumb" class="mb-4">
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="../index.html">Inicio</a></li>
        <li class="breadcrumb-item"><a href="productos.html">Productos</a></li>
        <li class="breadcrumb-item"><a href="productos.html?categoria=${producto.categoria_slug}">${producto.categoria_nombre}</a></li>
        <li class="breadcrumb-item active" aria-current="page">${producto.nombre}</li>
      </ol>
    </nav>
  `;

  // Asegurar que la imagen tenga la ruta correcta
  const imagenUrl = producto.imagen.startsWith('images/') ? `../${producto.imagen}` : producto.imagen;
  
  // Preparar galería de imágenes adicionales
  let galeriaHTML = '';
  if (producto.imagenes && producto.imagenes.length > 0) {
    galeriaHTML = `
      <div class="product-gallery mt-3">
        <h5 class="mb-3"><i class="fas fa-images mr-2"></i>Galería de imágenes</h5>
        <div class="gallery-thumbnails">
          ${producto.imagenes.map((img, index) => {
            const imgUrl = img.imagen.startsWith('images/') ? `../${img.imagen}` : img.imagen;
            return `
              <div class="gallery-thumbnail" onclick="cambiarImagenPrincipal('${imgUrl}', '${img.imagen_alt || producto.nombre}')">
                <img src="${imgUrl}" 
                     alt="${img.imagen_alt || 'Imagen del producto'}" 
                     class="gallery-image"
                     onerror="this.src='../images/logo.png'">
              </div>
            `;
          }).join('')}
        </div>
      </div>
    `;
  }
  
  contenedor.innerHTML = `
    ${breadcrumbs}
    <div class="row" data-aos="fade-up">
      <!-- Imagen del producto -->
      <div class="col-md-6 mb-4">
        <div class="card producto-detalle-card">
          <div class="main-image-container">
            <img src="${imagenUrl}" 
                 id="main-product-image"
                 class="card-img-top main-product-image" 
                 alt="${producto.nombre}" 
                 style="max-height: 400px; object-fit: contain;" 
                 onerror="this.src='../images/logo.png'">
          </div>
          ${galeriaHTML}
        </div>
      </div>
      
      <!-- Información del producto -->
      <div class="col-md-6 mb-4">
        <div class="card producto-detalle-card">
          <div class="card-body">
            <h1 class="card-title mb-3">${producto.nombre}</h1>
            <p class="card-text text-muted mb-3">${producto.descripcion}</p>
            <h4 class="text-primary mb-4">${producto.precio_mostrar}</h4>
            
            <!-- Especificaciones -->
            <div id="acordeon-especificaciones">
              <div class="card">
                <div class="card-header" id="especificaciones-header">
                  <button class="btn btn-link" type="button" data-toggle="collapse" data-target="#especificaciones-collapse" aria-expanded="true" aria-controls="especificaciones-collapse">
                    <i class="fas fa-info-circle mr-2"></i>Especificaciones técnicas
                    <i class="fas fa-chevron-down arrow-icon ml-2"></i>
                  </button>
                </div>
                
                <div id="especificaciones-collapse" class="collapse show" aria-labelledby="especificaciones-header">
                  <div class="card-body">
                    <div class="row">
                      <div class="col-md-6">
                        <p><strong>Material:</strong> ${producto.material || 'No especificado'}</p>
                        ${producto.dimensiones ? `<p><strong>Dimensiones:</strong> ${producto.dimensiones}</p>` : ''}
                        ${producto.peso ? `<p><strong>Peso:</strong> ${producto.peso}</p>` : ''}
                      </div>
                      <div class="col-md-6">
                        ${producto.uso ? `<p><strong>Uso:</strong> ${producto.uso}</p>` : ''}
                        ${producto.otras_caracteristicas ? `<p><strong>Características:</strong> ${producto.otras_caracteristicas}</p>` : ''}
                        <p><strong>Garantía:</strong> ${producto.garantia || 'No especificada'}</p>
                      </div>
                    </div>
                    ${producto.observaciones ? `<p class="mt-3"><strong>Observaciones:</strong> ${producto.observaciones}</p>` : ''}
                  </div>
                </div>
              </div>
            </div>
            
            <!-- Botones de acción -->
            <div class="mt-4">
              <button class="btn btn-custom mr-2" data-toggle="modal" data-target="#contactoModal">
                <i class="fas fa-envelope mr-2"></i>Solicitar cotización
              </button>
              <a href="productos.html?tipo=${producto.categoria_slug}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left mr-2"></i>Volver a productos
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>
  `;
}

/**
 * Actualizar meta tags para SEO
 */
function actualizarMetaTags(producto) {
  // 1. Actualizar el <title> de la página
  document.title = `${producto.nombre} - JC3Design`;

  // 2. Actualizar la <meta name="description">
  let metaDescription = document.querySelector('meta[name="description"]');
  if (!metaDescription) {
    metaDescription = document.createElement('meta');
    metaDescription.name = 'description';
    document.head.appendChild(metaDescription);
  }
  // Crear una descripción corta y atractiva
  const descripcionCorta = producto.descripcion.substring(0, 155).replace(/"/g, '&quot;') + '...';
  metaDescription.content = `Descubre ${producto.nombre} en JC3Design. ${descripcionCorta} Cotiza ahora.`;

  // 3. (Opcional) Actualizar meta tags para redes sociales (Open Graph)
  actualizarMetaTag('og:title', `${producto.nombre} - JC3Design`);
  actualizarMetaTag('og:description', descripcionCorta);
  actualizarMetaTag('og:image', new URL(producto.imagen, window.location.href).href);
  actualizarMetaTag('og:url', window.location.href);
  actualizarMetaTag('og:type', 'product');
}

function actualizarMetaTag(property, content) {
    let metaTag = document.querySelector(`meta[property='${property}']`);
    if (!metaTag) {
        metaTag = document.createElement('meta');
        metaTag.setAttribute('property', property);
        document.head.appendChild(metaTag);
    }
    metaTag.content = content;
}

/**
 * Cambiar imagen principal
 */
function cambiarImagenPrincipal(nuevaImagen, nuevoAlt) {
  const mainImage = document.getElementById('main-product-image');
  if (mainImage) {
    mainImage.src = nuevaImagen;
    mainImage.alt = nuevoAlt;
    
    // Agregar efecto de transición
    mainImage.style.opacity = '0.7';
    setTimeout(() => {
      mainImage.style.opacity = '1';
    }, 200);
  }
}

// Modal de contacto
document.addEventListener("DOMContentLoaded", function () {
  // Agregar el modal al final del body si no existe
  if (!document.getElementById("contactoModal")) {
    const modalHTML = `
      <div class="modal fade" id="contactoModal" tabindex="-1" role="dialog" aria-labelledby="contactoModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title" id="contactoModalLabel">Solicitar cotización</h5>
              <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
              </button>
            </div>
            <div class="modal-body">
              <form>
                <div class="form-group">
                  <label for="nombre">Nombre completo</label>
                  <input type="text" class="form-control" id="nombre" required>
                </div>
                <div class="form-group">
                  <label for="email">Email</label>
                  <input type="email" class="form-control" id="email" required>
                </div>
                <div class="form-group">
                  <label for="telefono">Teléfono</label>
                  <input type="tel" class="form-control" id="telefono">
                </div>
                <div class="form-group">
                  <label for="mensaje">Mensaje</label>
                  <textarea class="form-control" id="mensaje" rows="4" placeholder="Describe tu consulta o solicitud..."></textarea>
                </div>
              </form>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
              <button type="button" class="btn btn-custom" onclick="enviarCotizacion()">Enviar solicitud</button>
            </div>
          </div>
        </div>
      </div>
    `;
    document.body.insertAdjacentHTML('beforeend', modalHTML);
  }
});

// Función para enviar cotización
async function enviarCotizacion() {
    const nombre = document.getElementById('nombre').value;
    const email = document.getElementById('email').value;
    const telefono = document.getElementById('telefono').value;
    const mensaje = document.getElementById('mensaje').value;
    
    if (!nombre || !email) {
        alert('Por favor completa los campos obligatorios (nombre y email).');
        return;
    }
    
    // Obtener el ID del producto desde la URL
    const urlParams = new URLSearchParams(window.location.search);
    const productoId = urlParams.get('id');
    
    if (!productoId) {
        alert('Error: No se pudo identificar el producto.');
        return;
    }
    
    try {
        // Preparar datos de la cotización
        const cotizacionData = {
            tipo_cotizacion: 'producto',
            producto_id: parseInt(productoId),
            nombre_cliente: nombre,
            email_cliente: email,
            telefono_cliente: telefono,
            mensaje: mensaje
        };
        
        // Enviar cotización a la API
        const response = await fetch('../api/cotizaciones.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(cotizacionData)
        });
        
        const result = await response.json();
        
        if (response.ok) {
            // Mostrar notificación de éxito
            mostrarNotificacionExito('¡Cotización enviada exitosamente!', '¡Gracias por tu interés! Tu solicitud de cotización ha sido enviada. Te contactaremos pronto con una propuesta personalizada.');
            
            // Cerrar el modal
            $('#contactoModal').modal('hide');
            
            // Limpiar el formulario
            document.getElementById('nombre').value = '';
            document.getElementById('email').value = '';
            document.getElementById('telefono').value = '';
            document.getElementById('mensaje').value = '';
        } else {
            throw new Error(result.error || 'Error al enviar la cotización');
        }
        
    } catch (error) {
        console.error('Error:', error);
        mostrarNotificacionError('Error al enviar cotización', 'Ha ocurrido un error al enviar tu solicitud. Por favor, intenta nuevamente.');
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
