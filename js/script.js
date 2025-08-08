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
  