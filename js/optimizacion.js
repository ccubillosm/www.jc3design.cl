// Optimización de Imágenes y Lazy Loading
// Este archivo detecta automáticamente si se ejecuta desde la raíz del sitio o desde el directorio pag/
// y ajusta las rutas de los recursos en consecuencia.

// Función para detectar la ruta base del sitio
function getBasePath() {
  // Detectar si estamos en el directorio pag/ o en la raíz
  const currentPath = window.location.pathname;
  console.log('🔍 Pathname completo:', currentPath);
  
  // Si estamos en /pag/ o en cualquier subdirectorio de pag/
  if (currentPath.includes('/pag/')) {
    console.log('📍 Detectado directorio pag/, usando ruta relativa ../');
    return '../';
  }
  
  // Si estamos en la raíz o en cualquier otro directorio
  console.log('📍 Detectada raíz del sitio, usando ruta relativa ./');
  return './';
}

// Función helper para construir rutas
function buildPath(relativePath) {
  // Asegurarse de que BASE_PATH esté disponible
  if (typeof window.BASE_PATH === 'undefined') {
    console.warn('⚠️ BASE_PATH no está definido, usando ruta por defecto');
    return relativePath;
  }
  
  const fullPath = window.BASE_PATH + relativePath;
  console.log(`🔗 Construyendo ruta: ${relativePath} → ${fullPath}`);
  return fullPath;
}

// Función de prueba para verificar rutas
function testPaths() {
  console.log('🧪 === PRUEBA DE RUTAS ===');
  console.log('Ruta base:', window.BASE_PATH);
  console.log('Logo:', buildPath('images/logo.png'));
  console.log('CSS:', buildPath('css/style.css'));
  console.log('JS:', buildPath('js/script.js'));
  console.log('Imagen carrusel:', buildPath('images/carr_2.jpg'));
  
  // Verificar URLs completas
  const testUrls = [
    'images/logo.png',
    'css/style.css',
    'js/script.js',
    'images/carr_2.jpg'
  ];
  
  console.log('🔗 URLs completas generadas:');
  testUrls.forEach(path => {
    const fullPath = buildPath(path);
    const absoluteUrl = new URL(fullPath, window.location.href).href;
    console.log(`  ${path} → ${fullPath} → ${absoluteUrl}`);
  });
  
  console.log('========================');
}

document.addEventListener("DOMContentLoaded", function() {
  // Inicializar la ruta base
  window.BASE_PATH = getBasePath();
  
  // Debug: mostrar la ruta base en consola
  console.log('🔧 Ruta base detectada:', window.BASE_PATH);
  console.log('🔧 Pathname actual:', window.location.pathname);
  console.log('🔧 URL completa:', window.location.href);
  
  // Ejecutar prueba de rutas
  testPaths();
  
  // Detectar soporte para WebP
  const webpSupported = detectWebPSupport();
  
  // Implementar lazy loading para todas las imágenes
  implementLazyLoading();
  
  // Optimizar imágenes de carrusel
  optimizeCarouselImages();
  
  // Preload imágenes críticas
  preloadCriticalImages();
  
  // Optimizaciones adicionales
  implementPerformanceOptimizations();
  
  // Optimización de videos
  optimizeVideos();
  
  // Optimización de conexión
  optimizeForConnection();
});

// Detectar soporte para WebP
function detectWebPSupport() {
  const canvas = document.createElement('canvas');
  canvas.width = 1;
  canvas.height = 1;
  const ctx = canvas.getContext('2d');
  ctx.drawImage(new Image(), 0, 0);
  
  try {
    const webpData = canvas.toDataURL('image/webp');
    return webpData.indexOf('data:image/webp') === 0;
  } catch (e) {
    return false;
  }
}

// Implementar lazy loading
function implementLazyLoading() {
  const images = document.querySelectorAll('img[data-src]');
  
  if ('IntersectionObserver' in window) {
    const imageObserver = new IntersectionObserver((entries, observer) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          const img = entry.target;
          loadImage(img);
          imageObserver.unobserve(img);
        }
      });
    });
    
    images.forEach(img => imageObserver.observe(img));
  } else {
    // Fallback para navegadores sin IntersectionObserver
    images.forEach(img => loadImage(img));
  }
  
  // Cargar inmediatamente las imágenes que ya están en el viewport
  images.forEach(img => {
    if (img.getBoundingClientRect().top < window.innerHeight) {
      loadImage(img);
    }
  });
}

// Cargar imagen con lazy loading
function loadImage(img) {
  const src = img.getAttribute('data-src');
  if (!src) return;
  
  // Crear imagen temporal para precargar
  const tempImage = new Image();
  
  tempImage.onload = function() {
    img.src = src;
    img.classList.add('loaded');
    img.classList.remove('lazy-image');
    
    // Remover atributo data-src
    img.removeAttribute('data-src');
  };
  
  tempImage.onerror = function() {
    // Fallback si la imagen no carga
    img.src = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjIwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZjBmMGYwIi8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGZvbnQtZmFtaWx5PSJBcmlhbCwgc2Fucy1zZXJpZiIgZm9udC1zaXplPSIxNCIgZmlsbD0iIzk5OTk5OSIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZHk9Ii4zZW0iPkltYWdlbiBubyBkaXNwb25pYmxlPC90ZXh0Pjwvc3ZnPg==';
    img.classList.add('loaded');
    img.classList.remove('lazy-image');
  };
  
  tempImage.src = src;
}

// Optimizar imágenes de carrusel
function optimizeCarouselImages() {
  const carouselImages = document.querySelectorAll('.carousel-item img');
  
  console.log(`🎠 Optimizando ${carouselImages.length} imágenes del carrusel...`);
  
  carouselImages.forEach((img, index) => {
    // NO aplicar lazy loading a imágenes del carrusel - deben cargar inmediatamente
    img.classList.add('loaded'); // Marcar como cargada para evitar lazy loading
    
    // Verificar que la imagen cargue correctamente
    if (img.complete && img.naturalWidth > 0) {
      console.log(`✅ Imagen ${index + 1} del carrusel ya está cargada: ${img.src}`);
    } else {
      // Si no está cargada, forzar la carga
      console.log(`🔄 Forzando carga de imagen ${index + 1} del carrusel: ${img.src}`);
      
      img.onload = function() {
        console.log(`✅ Imagen ${index + 1} del carrusel cargada correctamente`);
      };
      
      img.onerror = function() {
        console.error(`❌ Error cargando imagen ${index + 1} del carrusel: ${img.src}`);
      };
    }
  });
}

// Preload imágenes críticas
function preloadCriticalImages() {
  const criticalImages = [
    buildPath('images/logo.png'),
    buildPath('images/logo_blanco.png')
  ];
  
  console.log('🖼️ Preload imágenes críticas:', criticalImages);
  
  criticalImages.forEach(src => {
    const link = document.createElement('link');
    link.rel = 'preload';
    link.as = 'image';
    link.href = src;
    console.log(`📎 Creando preload para: ${src}`);
    document.head.appendChild(link);
  });
}

// Optimizar imágenes de productos dinámicamente
function optimizeProductImages() {
  const productImages = document.querySelectorAll('.card-img-top');
  
  productImages.forEach(img => {
    // Agregar lazy loading si no está cargada
    if (!img.classList.contains('loaded')) {
      img.classList.add('lazy-image');
      img.setAttribute('data-src', img.src);
      img.src = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjIwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZjBmMGYwIi8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGZvbnQtZmFtaWx5PSJBcmlhbCwgc2Fucy1zZXJpZiIgZm9udC1zaXplPSIxNCIgZmlsbD0iIzk5OTk5OSIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZHk9Ii4zZW0iPkNhcmdhbmRvIHByb2R1Y3RvLi4uPC90ZXh0Pjwvc3ZnPg==';
    }
  });
}

// Función para optimizar imágenes de fondo
function optimizeBackgroundImages() {
  const bgElements = document.querySelectorAll('[data-bg-src]');
  
  bgElements.forEach(element => {
    const bgSrc = element.getAttribute('data-bg-src');
    if (bgSrc) {
      const img = new Image();
      img.onload = function() {
        element.style.backgroundImage = `url(${bgSrc})`;
        element.classList.add('loaded');
      };
      img.src = bgSrc;
    }
  });
}

// Optimizar imágenes cuando se cargan dinámicamente
function optimizeDynamicImages() {
  // Observar cambios en el DOM para imágenes agregadas dinámicamente
  const observer = new MutationObserver((mutations) => {
    mutations.forEach((mutation) => {
      mutation.addedNodes.forEach((node) => {
        if (node.nodeType === 1) { // Element node
          const images = node.querySelectorAll ? node.querySelectorAll('img') : [];
          images.forEach(img => {
            // Excluir imágenes del carrusel y imágenes que ya tienen la clase loaded
            const isCarouselImage = img.closest('.carousel-item') !== null;
            
            if (img.src && !img.classList.contains('loaded') && !isCarouselImage) {
              img.classList.add('lazy-image');
              img.setAttribute('data-src', img.src);
              img.src = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjIwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZjBmMGYwIi8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGZvbnQtZmFtaWx5PSJBcmlhbCwgc2Fucy1zZXJpZiIgZm9udC1zaXplPSIxNCIgZmlsbD0iIzk5OTk5OSIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZHk9Ii4zZW0iPkNhcmdhbmRvLi4uPC90ZXh0Pjwvc3ZnPg==';
            }
          });
        }
      });
    });
  });
  
  observer.observe(document.body, {
    childList: true,
    subtree: true
  });
}

// ===== NUEVAS OPTIMIZACIONES =====

// Optimizaciones de rendimiento
function implementPerformanceOptimizations() {
  // Optimizar animaciones
  optimizeAnimations();
  
  // Optimizar scroll
  optimizeScroll();
  
  // Optimizar memoria
  optimizeMemory();
  
  // Optimizar carga de recursos
  optimizeResourceLoading();
}

// Optimizar animaciones
function optimizeAnimations() {
  // Detectar preferencias de movimiento reducido
  if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
    document.body.classList.add('reduced-motion');
  }
  
  // Optimizar animaciones AOS
  const aosElements = document.querySelectorAll('[data-aos]');
  aosElements.forEach(element => {
    element.style.willChange = 'auto';
  });
}

// Optimizar scroll
function optimizeScroll() {
  let ticking = false;
  
  function updateOnScroll() {
    // Optimizaciones durante el scroll
    ticking = false;
  }
  
  function requestTick() {
    if (!ticking) {
      requestAnimationFrame(updateOnScroll);
      ticking = true;
    }
  }
  
  window.addEventListener('scroll', requestTick, { passive: true });
}

// Optimizar memoria
function optimizeMemory() {
  // Limpiar event listeners innecesarios
  window.addEventListener('beforeunload', () => {
    // Cleanup antes de salir
    const observers = window.intersectionObservers || [];
    observers.forEach(observer => observer.disconnect());
  });
  
  // Optimizar imágenes grandes
  const largeImages = document.querySelectorAll('img[src*="large"]');
  largeImages.forEach(img => {
    img.classList.add('memory-optimized');
  });
}

// Optimizar carga de recursos
function optimizeResourceLoading() {
  // Preload recursos críticos
  const criticalResources = [
    { href: buildPath('css/style.css'), as: 'style' },
    { href: buildPath('js/script.js'), as: 'script' }
  ];
  
  console.log('📚 Preload recursos críticos:', criticalResources);
  
  criticalResources.forEach(resource => {
    const link = document.createElement('link');
    link.rel = 'preload';
    link.as = resource.as;
    link.href = resource.href;
    console.log(`📎 Creando preload para: ${resource.href} (${resource.as})`);
    document.head.appendChild(link);
  });
}

// Optimizar videos
function optimizeVideos() {
  const videos = document.querySelectorAll('video');
  
  videos.forEach(video => {
    // Optimizar carga de video
    video.preload = 'metadata';
    
    // Agregar lazy loading para videos
    if ('IntersectionObserver' in window) {
      const videoObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
          if (entry.isIntersecting) {
            const video = entry.target;
            video.preload = 'auto';
            videoObserver.unobserve(video);
          }
        });
      });
      
      videoObserver.observe(video);
    }
    
    // Optimizar para conexiones lentas
    if ('connection' in navigator) {
      const connection = navigator.connection;
      if (connection.effectiveType === 'slow-2g' || connection.effectiveType === '2g') {
        video.classList.add('video-low-bandwidth');
      }
    }
  });
}

// Optimizar para conexión
function optimizeForConnection() {
  if ('connection' in navigator) {
    const connection = navigator.connection;
    
    // Optimizar para conexiones lentas
    if (connection.effectiveType === 'slow-2g' || connection.effectiveType === '2g') {
      document.body.classList.add('slow-connection');
      
      // Reducir calidad de imágenes
      const images = document.querySelectorAll('img');
      images.forEach(img => {
        if (img.src.includes('images/')) {
          img.classList.add('low-quality');
        }
      });
    }
    
    // Optimizar para conexiones rápidas
    if (connection.effectiveType === '4g') {
      document.body.classList.add('fast-connection');
      
      // Precargar más recursos
      preloadAdditionalResources();
    }
  }
}

// Precargar recursos adicionales para conexiones rápidas
function preloadAdditionalResources() {
  const additionalResources = [
    buildPath('images/carr_2.jpg'),
    buildPath('images/carr_3.jpg'),
    buildPath('images/mueble_1.jpg'),
    buildPath('images/p13w_jc3d.jpg'),
    buildPath('images/dise_1.jpg')
  ];
  
  console.log('🖼️ Prefetch recursos adicionales:', additionalResources);
  
  additionalResources.forEach(src => {
    const link = document.createElement('link');
    link.rel = 'prefetch';
    link.href = src;
    console.log(`📎 Creando prefetch para: ${src}`);
    document.head.appendChild(link);
  });
}

// Optimización de SEO y accesibilidad
function optimizeSEOAndAccessibility() {
  // Agregar atributos de accesibilidad
  const images = document.querySelectorAll('img:not([alt])');
  images.forEach(img => {
    if (!img.alt) {
      img.alt = 'Imagen de JC3Design';
    }
  });
  
  // Optimizar enlaces
  const links = document.querySelectorAll('a[href="#"]');
  links.forEach(link => {
    link.setAttribute('aria-label', link.textContent + ' (enlace)');
  });
  
  // Optimizar formularios
  const forms = document.querySelectorAll('form');
  forms.forEach(form => {
    if (!form.getAttribute('aria-label')) {
      form.setAttribute('aria-label', 'Formulario de contacto');
    }
  });
}

// Optimización de rendimiento en tiempo real
function implementRealTimeOptimizations() {
  // Monitorear rendimiento
  if ('performance' in window) {
    const observer = new PerformanceObserver((list) => {
      list.getEntries().forEach((entry) => {
        if (entry.entryType === 'navigation') {
          console.log('Tiempo de carga:', entry.loadEventEnd - entry.loadEventStart);
        }
      });
    });
    
    observer.observe({ entryTypes: ['navigation'] });
  }
  
  // Optimizar basado en uso de memoria
  if ('memory' in performance) {
    setInterval(() => {
      const memory = performance.memory;
      if (memory.usedJSHeapSize > memory.jsHeapSizeLimit * 0.8) {
        console.warn('Uso de memoria alto, optimizando...');
        // Implementar limpieza de memoria
      }
    }, 30000);
  }
}

// Exportar funciones para uso global
window.ImageOptimizer = {
  implementLazyLoading,
  optimizeProductImages,
  optimizeBackgroundImages,
  optimizeDynamicImages,
  implementPerformanceOptimizations,
  optimizeVideos,
  optimizeForConnection,
  optimizeSEOAndAccessibility,
  implementRealTimeOptimizations,
  buildPath,
  getBasePath,
  testPaths
};

// Inicializar optimizaciones adicionales
document.addEventListener('DOMContentLoaded', function() {
  optimizeSEOAndAccessibility();
  implementRealTimeOptimizations();
});
