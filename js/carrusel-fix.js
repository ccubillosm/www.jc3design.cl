// Solución definitiva para el carrusel - JC3Design
console.log("🎠 Iniciando solución del carrusel...");

// Función para verificar si las dependencias están listas
function checkDependencies() {
  if (typeof $ === 'undefined') {
    console.log("⏳ Esperando jQuery...");
    return false;
  }
  if (typeof $.fn.carousel === 'undefined') {
    console.log("⏳ Esperando Bootstrap...");
    return false;
  }
  return true;
}

// Función para verificar que las imágenes existan
function verifyImages() {
  const images = document.querySelectorAll('#heroCarousel img');
  console.log(`📸 Verificando ${images.length} imágenes del carrusel:`);
  
  images.forEach((img, index) => {
    console.log(`📸 Imagen ${index + 1}: ${img.src}`);
    
    // Verificar que la imagen cargue
    img.onload = function() {
      console.log(`✅ Imagen ${index + 1} cargada correctamente`);
    };
    
    img.onerror = function() {
      console.error(`❌ Error cargando imagen ${index + 1}: ${img.src}`);
    };
  });
}

// Función principal para inicializar el carrusel
function initCarousel() {
  if (!checkDependencies()) {
    return false;
  }

  const $carousel = $('#heroCarousel');
  if ($carousel.length === 0) {
    console.error("❌ Carrusel no encontrado");
    return false;
  }

  try {
    // Destruir cualquier instancia previa
    $carousel.carousel('dispose');
    
    // Inicializar carrusel con configuración optimizada
    $carousel.carousel({
      interval: 5000,
      wrap: true,
      keyboard: true,
      pause: 'hover'
    });
    
    console.log("✅ Carrusel inicializado correctamente");
    
    // Verificar y restaurar imágenes si es necesario
    verifyImages();
    forceReloadCarouselImages();
    
    // Forzar el primer ciclo después de un momento
    setTimeout(() => {
      $carousel.carousel('cycle');
      console.log("🔄 Carrusel activado");
    }, 1000);
    
    return true;
  } catch (error) {
    console.error("❌ Error inicializando carrusel:", error);
    return false;
  }
}

// Función para forzar recarga de imágenes si es necesario
function forceReloadCarouselImages() {
  const carouselImages = document.querySelectorAll('#heroCarousel img');
  console.log("🔄 Forzando recarga de imágenes del carrusel...");
  
  carouselImages.forEach((img, index) => {
    let originalSrc = img.src;
    
    // Si la imagen tiene un placeholder SVG, restaurar la URL original
    if (originalSrc.includes('data:image/svg+xml')) {
      const dataSrc = img.getAttribute('data-src');
      if (dataSrc) {
        originalSrc = dataSrc;
        console.log(`📸 Restaurando imagen ${index + 1} desde data-src:`, originalSrc);
      } else {
        // URLs esperadas del carrusel
        const expectedSrcs = [
          'images/carr_1.jpg',
          'images/carr_2.jpg', 
          'images/carr_3.jpg'
        ];
        if (expectedSrcs[index]) {
          originalSrc = expectedSrcs[index];
          console.log(`📸 Restaurando imagen ${index + 1} con URL esperada:`, originalSrc);
        }
      }
    }
    
    // Marcar como cargada para evitar lazy loading
    img.classList.add('loaded');
    img.classList.remove('lazy-image');
    
    // Forzar recarga
    img.src = '';
    setTimeout(() => {
      img.src = originalSrc;
      console.log(`🔄 Recargando imagen ${index + 1}:`, originalSrc);
    }, 100 * index);
  });
}

// Ejecutar cuando el DOM esté listo
$(document).ready(function() {
  console.log("📄 DOM listo - Iniciando carrusel...");
  
  // Intentar inicializar inmediatamente
  if (!initCarousel()) {
    // Si falla, intentar varias veces con intervalos
    let attempts = 0;
    const maxAttempts = 10;
    
    const interval = setInterval(function() {
      attempts++;
      console.log(`⏳ Intento ${attempts}/${maxAttempts} de inicializar carrusel...`);
      
      if (initCarousel() || attempts >= maxAttempts) {
        clearInterval(interval);
        if (attempts >= maxAttempts) {
          console.error("❌ No se pudo inicializar el carrusel después de varios intentos");
          console.log("🔄 Intentando recarga de imágenes como último recurso...");
          forceReloadCarouselImages();
        }
      }
    }, 500);
  }
});

// Función adicional para debugging completo
function debugCarousel() {
  console.log("🔍 === DIAGNÓSTICO COMPLETO DEL CARRUSEL ===");
  
  const carousel = document.getElementById('heroCarousel');
  console.log("🎠 Carrusel encontrado:", carousel !== null);
  
  if (carousel) {
    const images = carousel.querySelectorAll('img');
    console.log(`📸 Total de imágenes: ${images.length}`);
    
    images.forEach((img, index) => {
      console.log(`📸 Imagen ${index + 1}:`);
      console.log(`   - SRC: ${img.src}`);
      console.log(`   - Data-SRC: ${img.getAttribute('data-src') || 'No tiene'}`);
      console.log(`   - Clases: ${img.className}`);
      console.log(`   - Loaded: ${img.complete && img.naturalWidth > 0}`);
      console.log(`   - Es placeholder: ${img.src.includes('data:image/svg+xml')}`);
    });
    
    // Verificar Bootstrap
    console.log("🅱️ Bootstrap disponible:", typeof $.fn.carousel !== 'undefined');
    
    // Estado del carrusel
    const $carousel = $('#heroCarousel');
    console.log("🎠 Carrusel jQuery encontrado:", $carousel.length > 0);
  }
  
  console.log("🔍 === FIN DEL DIAGNÓSTICO ===");
}

// Hacer funciones disponibles globalmente para debugging
window.initCarousel = initCarousel;
window.forceReloadCarouselImages = forceReloadCarouselImages;
window.verifyImages = verifyImages;
window.debugCarousel = debugCarousel;

// Debugging: mostrar estado cuando se carga la página
console.log("🎠 Script del carrusel cargado - Funciones disponibles:");
console.log("- initCarousel() - Reinicializar carrusel");
console.log("- forceReloadCarouselImages() - Recargar imágenes");
console.log("- verifyImages() - Verificar imágenes");
console.log("- debugCarousel() - Diagnóstico completo");
