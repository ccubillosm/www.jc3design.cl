// Script simple para el carrusel
document.addEventListener("DOMContentLoaded", function() {
  console.log("🎠 Iniciando carrusel simple...");
  
  // Esperar a que jQuery esté disponible
  function initCarousel() {
    if (typeof $ !== 'undefined' && $.fn.carousel) {
      console.log("✅ jQuery y Bootstrap disponibles");
      
      // Inicializar el carrusel
      $('#heroCarousel').carousel({
        interval: 5000,
        wrap: true
      });
      
      console.log("🎠 Carrusel inicializado");
      
      // Verificar imágenes
      $('.carousel-item img').each(function(index) {
        console.log(`📸 Imagen ${index + 1}: ${$(this).attr('src')}`);
      });
      
      return true;
    } else {
      console.log("⏳ Esperando jQuery y Bootstrap...");
      return false;
    }
  }
  
  // Intentar inicializar inmediatamente
  if (!initCarousel()) {
    // Si no está listo, intentar cada 100ms
    const interval = setInterval(function() {
      if (initCarousel()) {
        clearInterval(interval);
      }
    }, 100);
    
    // Timeout después de 10 segundos
    setTimeout(function() {
      clearInterval(interval);
      console.error("❌ No se pudo inicializar el carrusel");
    }, 10000);
  }
});
