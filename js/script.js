// Ocultar logo animado al hacer scroll
window.addEventListener('scroll', function () {
    const logo = document.getElementById('logoBox');
    if (window.scrollY > 20) {
      logo.style.display = 'none';
    } else {
      logo.style.display = 'flex';
    }
  });
  
  // Reiniciar animaciones de texto del carrusel
  $('#heroCarousel').on('slide.bs.carousel', function (e) {
    const captions = e.relatedTarget.querySelectorAll('.animate-text');
    captions.forEach(caption => {
      caption.classList.remove('animate-text');
      void caption.offsetWidth;
      caption.classList.add('animate-text');
    });
  });
  
  // Manejo de dropdowns en móvil
  document.addEventListener('DOMContentLoaded', function() {
    const isMobile = window.innerWidth <= 768;
    
    if (isMobile) {
      // En móvil, convertir dropdowns en enlaces directos
      const dropdownToggles = document.querySelectorAll('.nav-item.dropdown .dropdown-toggle');
      
      dropdownToggles.forEach(toggle => {
        toggle.addEventListener('click', function(e) {
          e.preventDefault();
          e.stopPropagation();
          
          // Determinar la ruta base según la página actual
          const currentPath = window.location.pathname;
          let basePath = '';
          
          if (currentPath.includes('/pag/')) {
            basePath = 'pag/';
          } else if (currentPath === '/' || currentPath.endsWith('index.html')) {
            basePath = 'pag/';
          }
          
          // Redirigir a la página principal de cada sección
          const text = this.textContent.trim();
          if (text.includes('Productos')) {
            window.location.href = basePath + 'productos.html';
          } else if (text.includes('Servicios')) {
            window.location.href = basePath + 'cotizacion-diseno.html';
          }
        });
      });
    }
  });
  