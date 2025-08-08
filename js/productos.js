document.addEventListener("DOMContentLoaded", function () {
    console.log("DOM cargado, iniciando productos.js");
    const contenedor = document.getElementById("productos-container");
    console.log("Contenedor encontrado:", contenedor);
  
    const urlParams = new URLSearchParams(window.location.search);
    const tipo = urlParams.get("tipo");
    console.log("Tipo de producto:", tipo);
  
    if (!tipo) {
      console.log("No hay tipo especificado, mostrando categorías");
      mostrarCategorias();
      return;
    }

    // Cargar productos desde la API
    console.log("Cargando productos para categoría:", tipo);
    cargarProductos(tipo);
});

/**
 * Mostrar las categorías disponibles
 */
function mostrarCategorias() {
    const contenedor = document.getElementById("productos-container");
    
    contenedor.innerHTML = `
        <div class="text-center w-100">
          <h3 class="mb-5">Elige una categoría de productos</h3>
          <div class="row justify-content-center">

            <!-- Card Muebles -->
            <div class="col-md-5 col-lg-4 mb-4" data-aos="fade-up" data-aos-delay="100">
              <div class="card h-100 shadow-lg border-0">
                <img src="../images/mueble_1.jpg" class="card-img-top responsive-image" alt="Muebles personalizados">
                <div class="card-body d-flex flex-column">
                  <h4 class="card-title text-primary">Muebles a Medida</h4>
                  <p class="card-text">Descubre nuestra colección de muebles personalizados diseñados y fabricados especialmente para ti. Desde muebles de cocina hasta mobiliario de oficina.</p>
                  <ul class="list-unstyled text-muted mb-3">
                    <li><i class="fas fa-check text-success mr-2"></i>Diseño personalizado</li>
                    <li><i class="fas fa-check text-success mr-2"></i>Materiales de calidad</li>
                    <li><i class="fas fa-check text-success mr-2"></i>Fabricación artesanal</li>
                  </ul>
                  <a href="productos.html?tipo=muebles" class="btn btn-custom mt-auto">
                    <i class="fas fa-arrow-right mr-2"></i>Ver Muebles
                  </a>
                </div>
              </div>
            </div>

            <!-- Card 3D -->
            <div class="col-md-5 col-lg-4 mb-4" data-aos="fade-up" data-aos-delay="200">
              <div class="card h-100 shadow-lg border-0">
                <img src="../images/p13w_jc3d.jpg" class="card-img-top responsive-image" alt="Piezas 3D">
                <div class="card-body d-flex flex-column">
                  <h4 class="card-title text-primary">Piezas 3D</h4>
                  <p class="card-text">Explora nuestra línea de piezas impresas en 3D de alta precisión. Desde prototipos hasta piezas funcionales para diferentes aplicaciones.</p>
                  <ul class="list-unstyled text-muted mb-3">
                    <li><i class="fas fa-check text-success mr-2"></i>Alta precisión</li>
                    <li><i class="fas fa-check text-success mr-2"></i>Materiales resistentes</li>
                    <li><i class="fas fa-check text-success mr-2"></i>Diseños únicos</li>
                  </ul>
                  <a href="productos.html?tipo=productos3d" class="btn btn-custom mt-auto">
                    <i class="fas fa-arrow-right mr-2"></i>Ver Piezas 3D
                  </a>
                </div>
              </div>
            </div>

          </div>
          
          <!-- Información adicional -->
          <div class="row mt-5">
            <div class="col-12">
              <div class="alert alert-info" role="alert">
                <h5 class="alert-heading"><i class="fas fa-info-circle mr-2"></i>¿No encuentras lo que buscas?</h5>
                <p class="mb-0">Contáctanos para solicitar un diseño personalizado o cotización especial. Estamos aquí para hacer realidad tus ideas.</p>
                <hr>
                <a href="contacto.html" class="btn btn-outline-info btn-sm">
                  <i class="fas fa-envelope mr-1"></i>Contactar
                </a>
              </div>
            </div>
          </div>
        </div>
      `;
}

/**
 * Cargar productos desde la API
 */
async function cargarProductos(categoria) {
    console.log("Iniciando cargarProductos para categoría:", categoria);
    const contenedor = document.getElementById("productos-container");
    
    try {
        // Mostrar loading
        contenedor.innerHTML = `
            <div class="text-center w-100">
                <div class="spinner-border text-primary" role="status">
                    <span class="sr-only">Cargando...</span>
                </div>
                <p class="mt-3">Cargando productos...</p>
            </div>
        `;
        
        // Hacer petición a la API
        const url = `../api/productos.php?slug=${categoria}`;
        console.log("Haciendo petición a:", url);
        const response = await fetch(url);
        
        console.log("Respuesta recibida:", response.status, response.statusText);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        console.log("Datos recibidos:", data);
        
        if (data.error) {
            throw new Error(data.error);
        }
        
        // Verificar si hay productos
        if (!data.productos || data.productos.length === 0) {
            console.log("No se encontraron productos");
            contenedor.innerHTML = `
                <div class="text-center w-100">
                    <h3 class="mb-4">No se encontraron productos</h3>
                    <p class="text-muted">No hay productos disponibles en esta categoría.</p>
                    <a href="productos.html" class="btn btn-custom">
                        <i class="fas fa-arrow-left mr-2"></i>Volver a categorías
                    </a>
                </div>
            `;
            return;
        }
        
        console.log("Renderizando", data.productos.length, "productos");
        // Renderizar productos
        renderizarProductos(data.productos, categoria);
        
        // Agregar paginación si es necesario
        if (data.paginacion && data.paginacion.total_paginas > 1) {
            renderizarPaginacion(data.paginacion, categoria);
        }
        
    } catch (error) {
        console.error('Error al cargar productos:', error);
        contenedor.innerHTML = `
            <div class="text-center w-100">
                <h3 class="mb-4 text-danger">Error al cargar productos</h3>
                <p class="text-muted">Ha ocurrido un error al cargar los productos. Por favor, intenta nuevamente.</p>
                <button class="btn btn-custom" onclick="location.reload()">
                    <i class="fas fa-redo mr-2"></i>Reintentar
                </button>
            </div>
        `;
    }
}

/**
 * Renderizar productos en el contenedor
 */
function renderizarProductos(productos, categoria) {
    console.log("Renderizando productos:", productos.length);
    const contenedor = document.getElementById("productos-container");
    
    if (!contenedor) {
        console.error("No se encontró el contenedor productos-container");
        return;
    }
    
    // Limpiar contenedor
    contenedor.innerHTML = '';
    
    // Agregar título de la categoría
    const tituloCategoria = categoria === 'productos3d' ? 'Piezas 3D' : 'Muebles a Medida';
    
    // Crear el HTML completo
    let html = `
        <div class="col-12 mb-4">
            <h2 class="text-center mb-3">${tituloCategoria}</h2>
            <p class="text-center text-muted">Mostrando ${productos.length} productos</p>
        </div>
    `;
    
    // Agregar productos
    productos.forEach((producto, index) => {
        console.log("Renderizando producto:", producto.nombre);
        
        // Asegurar que la imagen tenga la ruta correcta
        const imagenUrl = producto.imagen.startsWith('images/') ? `../${producto.imagen}` : producto.imagen;
        
        html += `
            <div class="col-md-4 mb-4" data-aos="fade-up" data-aos-delay="${index * 100}">
                <div class="card h-100 shadow">
                    <img src="${imagenUrl}" class="card-img-top responsive-image product-image" alt="${producto.nombre}" onerror="this.src='../images/logo.png'" style="height: 200px; object-fit: cover;">
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title">${producto.nombre}</h5>
                        <p class="card-text">${producto.descripcion || 'Sin descripción disponible'}</p>
                        <div class="mt-auto">
                            <strong class="text-primary">${producto.precio_mostrar}</strong>
                            <a href="producto.html?id=${producto.id}&tipo=${categoria}" class="btn btn-custom mt-2 w-100">
                                <i class="fas fa-eye mr-2"></i>Ver más
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        `;
    });
    
    // Insertar todo el HTML de una vez
    contenedor.innerHTML = html;
    console.log("Productos renderizados exitosamente");
}

/**
 * Renderizar paginación
 */
function renderizarPaginacion(paginacion, categoria) {
    const contenedor = document.getElementById("productos-container");
    
    const paginacionHTML = document.createElement('div');
    paginacionHTML.className = 'row mt-4';
    paginacionHTML.innerHTML = `
        <div class="col-12">
            <nav aria-label="Navegación de productos">
                <ul class="pagination justify-content-center">
                    ${generarPaginacionHTML(paginacion, categoria)}
                </ul>
            </nav>
        </div>
    `;
    
    contenedor.appendChild(paginacionHTML);
}

/**
 * Generar HTML de paginación
 */
function generarPaginacionHTML(paginacion, categoria) {
    const { pagina_actual, total_paginas } = paginacion;
    let html = '';
    
    // Botón anterior
    if (pagina_actual > 1) {
        html += `
            <li class="page-item">
                <a class="page-link" href="productos.html?tipo=${categoria}&page=${pagina_actual - 1}">
                    <i class="fas fa-chevron-left"></i>
                </a>
            </li>
        `;
    }
    
    // Números de página
    const inicio = Math.max(1, pagina_actual - 2);
    const fin = Math.min(total_paginas, pagina_actual + 2);
    
    for (let i = inicio; i <= fin; i++) {
        const active = i === pagina_actual ? 'active' : '';
        html += `
            <li class="page-item ${active}">
                <a class="page-link" href="productos.html?tipo=${categoria}&page=${i}">${i}</a>
            </li>
        `;
    }
    
    // Botón siguiente
    if (pagina_actual < total_paginas) {
        html += `
            <li class="page-item">
                <a class="page-link" href="productos.html?tipo=${categoria}&page=${pagina_actual + 1}">
                    <i class="fas fa-chevron-right"></i>
                </a>
            </li>
        `;
    }
    
    return html;
}

/**
 * Función para buscar productos (futura implementación)
 */
function buscarProductos(termino) {
    // Implementar búsqueda de productos
    console.log('Buscando:', termino);
}

/**
 * Función para filtrar productos (futura implementación)
 */
function filtrarProductos(filtros) {
    // Implementar filtros de productos
    console.log('Filtros:', filtros);
}
  
  