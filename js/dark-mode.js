// Funcionalidad de modo oscuro
class DarkModeManager {
  constructor() {
    this.themeToggle = null;
    this.currentTheme = 'light';
    this.init();
  }

  init() {
    // Crear el botón de cambio de tema
    this.createThemeToggle();
    
    // Cargar tema guardado o usar preferencia del sistema
    this.loadTheme();
    
    // Aplicar tema inicial
    this.applyTheme();
  }

  createThemeToggle() {
    // Crear el botón de cambio de tema
    this.themeToggle = document.createElement('button');
    this.themeToggle.className = 'theme-toggle';
    this.themeToggle.setAttribute('aria-label', 'Cambiar modo oscuro');
    this.themeToggle.setAttribute('title', 'Cambiar modo oscuro');
    
    // Agregar iconos
    this.themeToggle.innerHTML = `
      <i class="fas fa-moon" aria-hidden="true"></i>
      <i class="fas fa-sun" aria-hidden="true"></i>
    `;
    
    // Agregar evento click
    this.themeToggle.addEventListener('click', () => this.toggleTheme());
    
    // Agregar al DOM
    document.body.appendChild(this.themeToggle);
  }

  loadTheme() {
    // Intentar cargar tema guardado en localStorage
    const savedTheme = localStorage.getItem('theme');
    
    if (savedTheme) {
      this.currentTheme = savedTheme;
    } else {
      // Si no hay tema guardado, usar preferencia del sistema
      this.currentTheme = this.getSystemPreference();
    }
  }

  getSystemPreference() {
    // Detectar preferencia del sistema
    if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
      return 'dark';
    }
    return 'light';
  }

  applyTheme() {
    // Aplicar tema al documento
    document.documentElement.setAttribute('data-theme', this.currentTheme);
    
    // Actualizar icono del botón
    this.updateToggleIcon();
    
    // Guardar en localStorage
    localStorage.setItem('theme', this.currentTheme);
    
    // Emitir evento personalizado para otros scripts
    document.dispatchEvent(new CustomEvent('themeChanged', {
      detail: { theme: this.currentTheme }
    }));
  }

  toggleTheme() {
    // Cambiar entre temas
    this.currentTheme = this.currentTheme === 'light' ? 'dark' : 'light';
    this.applyTheme();
    
    // Agregar animación al botón
    this.themeToggle.style.transform = 'scale(0.9)';
    setTimeout(() => {
      this.themeToggle.style.transform = '';
    }, 150);
  }

  updateToggleIcon() {
    // Los iconos se muestran/ocultan automáticamente con CSS
    // basándose en el atributo data-theme
  }

  // Método público para obtener el tema actual
  getCurrentTheme() {
    return this.currentTheme;
  }

  // Método público para establecer tema específico
  setTheme(theme) {
    if (theme === 'light' || theme === 'dark') {
      this.currentTheme = theme;
      this.applyTheme();
    }
  }
}

// Escuchar cambios en la preferencia del sistema
function watchSystemTheme() {
  if (window.matchMedia) {
    const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
    
    mediaQuery.addEventListener('change', (e) => {
      // Solo cambiar si no hay tema guardado en localStorage
      if (!localStorage.getItem('theme')) {
        const newTheme = e.matches ? 'dark' : 'light';
        if (window.darkModeManager) {
          window.darkModeManager.setTheme(newTheme);
        }
      }
    });
  }
}

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', () => {
  // Crear instancia global
  window.darkModeManager = new DarkModeManager();
  
  // Escuchar cambios del sistema
  watchSystemTheme();
  
  console.log('🌙 Modo oscuro inicializado');
});

// Función para obtener el tema actual desde otros scripts
function getCurrentTheme() {
  return window.darkModeManager ? window.darkModeManager.getCurrentTheme() : 'light';
}

// Función para cambiar tema desde otros scripts
function setTheme(theme) {
  if (window.darkModeManager) {
    window.darkModeManager.setTheme(theme);
  }
}

// Exportar funciones para uso global
window.getCurrentTheme = getCurrentTheme;
window.setTheme = setTheme;
