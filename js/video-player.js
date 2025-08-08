// Video Player Optimizado para JC3Design
// Funcionalidades: Controles personalizados, accesibilidad, lazy loading

class VideoPlayer {
  constructor(videoId, overlayId, playPauseBtnId, muteBtnId) {
    this.video = document.getElementById(videoId);
    this.overlay = document.getElementById(overlayId);
    this.playPauseBtn = document.getElementById(playPauseBtnId);
    this.muteBtn = document.getElementById(muteBtnId);
    this.isPlaying = false;
    this.isMuted = false;
    
    this.init();
  }

  init() {
    if (!this.video) {
      console.warn('Video element not found');
      return;
    }

    // Configurar eventos del video
    this.setupVideoEvents();
    
    // Configurar controles
    this.setupControls();
    
    // Configurar accesibilidad
    this.setupAccessibility();
    
    // Lazy loading del video
    this.setupLazyLoading();
  }

  setupVideoEvents() {
    // Eventos del video
    this.video.addEventListener('loadedmetadata', () => {
      console.log('Video metadata loaded');
    });

    this.video.addEventListener('play', () => {
      this.isPlaying = true;
      this.updatePlayPauseButton();
      this.hideOverlay();
    });

    this.video.addEventListener('pause', () => {
      this.isPlaying = false;
      this.updatePlayPauseButton();
      this.showOverlay();
    });

    this.video.addEventListener('ended', () => {
      this.isPlaying = false;
      this.updatePlayPauseButton();
      this.showOverlay();
    });

    this.video.addEventListener('volumechange', () => {
      this.updateMuteButton();
    });

    // Manejar errores
    this.video.addEventListener('error', (e) => {
      console.error('Video error:', e);
      this.handleVideoError();
    });
  }

  setupControls() {
    // Botón play/pause
    if (this.playPauseBtn) {
      this.playPauseBtn.addEventListener('click', () => {
        this.togglePlayPause();
      });
    }

    // Botón mute/unmute
    if (this.muteBtn) {
      this.muteBtn.addEventListener('click', () => {
        this.toggleMute();
      });
    }

    // Overlay click para reproducir
    if (this.overlay) {
      this.overlay.addEventListener('click', () => {
        this.play();
      });
    }

    // Controles de teclado
    document.addEventListener('keydown', (e) => {
      if (document.activeElement === this.video || 
          this.video.contains(document.activeElement)) {
        this.handleKeyboardControls(e);
      }
    });
  }

  setupAccessibility() {
    // Agregar atributos ARIA
    if (this.video) {
      this.video.setAttribute('aria-label', 'Video promocional de JC3Design');
      this.video.setAttribute('aria-describedby', 'video-description');
    }

    // Crear descripción del video
    const videoDescription = document.createElement('div');
    videoDescription.id = 'video-description';
    videoDescription.className = 'sr-only';
    videoDescription.textContent = 'Video que muestra el proceso de diseño y fabricación de muebles en JC3Design';
    this.video.parentNode.appendChild(videoDescription);

    // Agregar roles y estados a los controles
    if (this.playPauseBtn) {
      this.playPauseBtn.setAttribute('aria-label', 'Reproducir video');
      this.playPauseBtn.setAttribute('aria-pressed', 'false');
    }

    if (this.muteBtn) {
      this.muteBtn.setAttribute('aria-label', 'Silenciar video');
      this.muteBtn.setAttribute('aria-pressed', 'false');
    }
  }

  setupLazyLoading() {
    // Intersection Observer para lazy loading
    if ('IntersectionObserver' in window) {
      const videoObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
          if (entry.isIntersecting) {
            this.loadVideo();
            videoObserver.unobserve(entry.target);
          }
        });
      }, {
        rootMargin: '50px'
      });

      videoObserver.observe(this.video);
    } else {
      // Fallback para navegadores sin IntersectionObserver
      this.loadVideo();
    }
  }

  loadVideo() {
    // Precargar video solo cuando sea necesario
    if (this.video.readyState === 0) {
      this.video.preload = 'metadata';
    }
  }

  togglePlayPause() {
    if (this.isPlaying) {
      this.pause();
    } else {
      this.play();
    }
  }

  play() {
    try {
      const playPromise = this.video.play();
      
      if (playPromise !== undefined) {
        playPromise.then(() => {
          console.log('Video started playing');
        }).catch(error => {
          console.error('Error playing video:', error);
          this.handlePlayError();
        });
      }
    } catch (error) {
      console.error('Error in play method:', error);
    }
  }

  pause() {
    this.video.pause();
  }

  toggleMute() {
    this.isMuted = !this.isMuted;
    this.video.muted = this.isMuted;
    this.updateMuteButton();
  }

  updatePlayPauseButton() {
    if (this.playPauseBtn) {
      const icon = this.playPauseBtn.querySelector('i');
      if (icon) {
        if (this.isPlaying) {
          icon.className = 'fas fa-pause';
          this.playPauseBtn.setAttribute('aria-label', 'Pausar video');
          this.playPauseBtn.setAttribute('aria-pressed', 'true');
        } else {
          icon.className = 'fas fa-play';
          this.playPauseBtn.setAttribute('aria-label', 'Reproducir video');
          this.playPauseBtn.setAttribute('aria-pressed', 'false');
        }
      }
    }
  }

  updateMuteButton() {
    if (this.muteBtn) {
      const icon = this.muteBtn.querySelector('i');
      if (icon) {
        if (this.video.muted) {
          icon.className = 'fas fa-volume-mute';
          this.muteBtn.setAttribute('aria-label', 'Activar sonido');
          this.muteBtn.setAttribute('aria-pressed', 'true');
        } else {
          icon.className = 'fas fa-volume-up';
          this.muteBtn.setAttribute('aria-label', 'Silenciar video');
          this.muteBtn.setAttribute('aria-pressed', 'false');
        }
      }
    }
  }

  showOverlay() {
    if (this.overlay) {
      this.overlay.style.display = 'flex';
    }
  }

  hideOverlay() {
    if (this.overlay) {
      this.overlay.style.display = 'none';
    }
  }

  handleKeyboardControls(e) {
    switch(e.code) {
      case 'Space':
        e.preventDefault();
        this.togglePlayPause();
        break;
      case 'KeyM':
        e.preventDefault();
        this.toggleMute();
        break;
      case 'ArrowLeft':
        e.preventDefault();
        this.video.currentTime = Math.max(0, this.video.currentTime - 10);
        break;
      case 'ArrowRight':
        e.preventDefault();
        this.video.currentTime = Math.min(this.video.duration, this.video.currentTime + 10);
        break;
    }
  }

  handleVideoError() {
    // Mostrar mensaje de error amigable
    const errorMessage = document.createElement('div');
    errorMessage.className = 'video-error';
    errorMessage.innerHTML = `
      <div class="alert alert-warning" role="alert">
        <i class="fas fa-exclamation-triangle"></i>
        No se pudo cargar el video. Por favor, intenta más tarde.
      </div>
    `;
    
    if (this.overlay) {
      this.overlay.innerHTML = '';
      this.overlay.appendChild(errorMessage);
    }
  }

  handlePlayError() {
    // Manejar errores de reproducción (ej: autoplay bloqueado)
    console.log('Autoplay blocked or video error');
    this.showOverlay();
  }

  // Métodos públicos para control externo
  getCurrentTime() {
    return this.video.currentTime;
  }

  getDuration() {
    return this.video.duration;
  }

  seekTo(time) {
    this.video.currentTime = time;
  }

  setVolume(volume) {
    this.video.volume = Math.max(0, Math.min(1, volume));
  }
}

// Inicializar reproductor cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
  // Crear instancia del reproductor
  const videoPlayer = new VideoPlayer(
    'promoVideo',
    'videoOverlay',
    'playPauseBtn',
    'muteBtn'
  );

  // Exponer para uso global si es necesario
  window.JC3VideoPlayer = videoPlayer;

  console.log('🎬 Video player inicializado');
});

// Optimizaciones adicionales
document.addEventListener('DOMContentLoaded', function() {
  // Preload de videos críticos
  const videoPreload = document.createElement('link');
  videoPreload.rel = 'preload';
  videoPreload.as = 'video';
  videoPreload.href = 'videos/proceso-jc3design.mp4';
  document.head.appendChild(videoPreload);

  // Detectar conexión lenta
  if ('connection' in navigator) {
    const connection = navigator.connection;
    if (connection.effectiveType === 'slow-2g' || connection.effectiveType === '2g') {
      console.log('Conexión lenta detectada, optimizando video');
      // Reducir calidad de video para conexiones lentas
      const videos = document.querySelectorAll('video');
      videos.forEach(video => {
        video.preload = 'none';
      });
    }
  }
});
