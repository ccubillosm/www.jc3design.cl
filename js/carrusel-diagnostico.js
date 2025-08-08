// Script de diagnóstico del carrusel
console.log("🔍 DIAGNÓSTICO DEL CARRUSEL");

// Verificar jQuery
if (typeof $ === "undefined") {
  console.error("❌ jQuery no está disponible");
} else {
  console.log("✅ jQuery está disponible");
}

// Verificar Bootstrap
if (typeof $.fn.carousel === "undefined") {
  console.error("❌ Bootstrap carousel no está disponible");
} else {
  console.log("✅ Bootstrap carousel está disponible");
}

// Verificar carrusel
const carousel = document.getElementById("heroCarousel");
if (!carousel) {
  console.error("❌ El carrusel no existe");
} else {
  console.log("✅ El carrusel existe");
  
  // Verificar imágenes
  const images = carousel.querySelectorAll("img");
  console.log(`📸 Encontradas ${images.length} imágenes`);
  
  images.forEach((img, index) => {
    console.log(`📸 Imagen ${index + 1}: ${img.src}`);
  });
}

// Función para inicializar carrusel
function initCarousel() {
  if (typeof $ !== "undefined" && $.fn.carousel) {
    try {
      $("#heroCarousel").carousel({
        interval: 5000,
        wrap: true
      });
      console.log("✅ Carrusel inicializado");
      return true;
    } catch (error) {
      console.error("❌ Error inicializando carrusel:", error);
      return false;
    }
  }
  return false;
}

// Inicializar después de que todo esté listo
$(document).ready(function() {
  console.log("🎠 Documento listo, inicializando carrusel...");
  
  // Intentar inicializar inmediatamente
  if (!initCarousel()) {
    // Si falla, intentar cada 500ms
    const interval = setInterval(function() {
      if (initCarousel()) {
        clearInterval(interval);
      }
    }, 500);
    
    // Timeout después de 10 segundos
    setTimeout(function() {
      clearInterval(interval);
      console.error("❌ No se pudo inicializar el carrusel");
    }, 10000);
  }
});
