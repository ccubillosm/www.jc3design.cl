# 🌙 Funcionalidad de Modo Oscuro - JC3Design

## Descripción

Se ha implementado una funcionalidad completa de modo oscuro para el sitio web de JC3Design. Esta funcionalidad permite a los usuarios cambiar entre el modo claro y oscuro, con persistencia de la preferencia del usuario y detección automática de la preferencia del sistema.

## Características Implementadas

### ✅ Funcionalidades Principales

1. **Botón de Cambio de Tema**
   - Botón flotante fijo en la esquina superior derecha
   - Iconos dinámicos (luna para modo claro, sol para modo oscuro)
   - Animaciones suaves al cambiar de tema
   - Totalmente accesible con ARIA labels

2. **Persistencia de Preferencia**
   - Guarda la preferencia del usuario en localStorage
   - Mantiene el tema seleccionado entre sesiones
   - Respeta la preferencia del sistema si no hay tema guardado

3. **Detección Automática del Sistema**
   - Detecta automáticamente la preferencia del sistema operativo
   - Se adapta a cambios en tiempo real en la configuración del sistema
   - Solo aplica cambios automáticos si no hay preferencia guardada

4. **Transiciones Suaves**
   - Animaciones de 0.3s para todos los cambios de color
   - Transiciones suaves para background, color de texto y bordes
   - Efecto de animación en el botón de cambio

### 🎨 Elementos Estilizados

#### Modo Claro (Por defecto)
- Fondo: Blanco (#ffffff)
- Texto: Negro (#000000)
- Navbar: Transparente con blur
- Cards: Fondo blanco con sombras suaves
- Footer: Gris oscuro (#111111)
- Color de acento: Rojo (#e63946)

#### Modo Oscuro
- Fondo: Gris muy oscuro (#1a1a1a)
- Texto: Blanco (#ffffff)
- Navbar: Gris oscuro con transparencia
- Cards: Gris oscuro (#2d2d2d)
- Footer: Negro (#0a0a0a)
- Color de acento: Rojo más brillante (#ff4757)

### 📄 Páginas Completamente Compatibles

#### ✅ Páginas Principales
- **Inicio** (`index.html`) - Totalmente funcional
- **Productos** (`pag/productos.html`) - Totalmente funcional
- **Nosotros** (`pag/nosotros.html`) - Totalmente funcional
- **Contacto** (`pag/contacto.html`) - Totalmente funcional

#### ✅ Páginas de Cotización
- **Cotización Diseño** (`pag/cotizacion-diseno.html`) - Totalmente funcional
- **Cotización Muebles** (`pag/cotizacion-mueble.html`) - Totalmente funcional
- **Cotización 3D** (`pag/cotizacion-3d.html`) - Totalmente funcional

#### ✅ Elementos Específicos Estilizados

**Páginas de Cotización:**
- Hero sections con gradientes adaptativos
- Cards de servicios con fondos oscuros
- Formularios con campos estilizados
- Botones con colores adaptativos
- Mensajes de éxito/error adaptados

**Página de Contacto:**
- Cards de información de contacto
- Formulario de contacto
- Enlaces de redes sociales
- Iconos y textos adaptativos

**Página Nosotros:**
- Sección de historia
- Cards de misión y visión
- Cards de valores
- Sección de equipo
- Cards de tecnologías
- Sección CTA adaptativa

### 📱 Responsive Design
- Botón adaptativo para dispositivos móviles
- Tamaño reducido en pantallas pequeñas
- Posicionamiento optimizado para diferentes dispositivos

## Archivos Implementados

### CSS
- `css/dark-mode.css` - Estilos específicos para el modo oscuro
- Variables CSS para fácil mantenimiento
- Transiciones suaves y animaciones

### JavaScript
- `js/dark-mode.js` - Lógica de funcionalidad
- Clase `DarkModeManager` para manejo del estado
- Eventos personalizados para integración con otros scripts
- Funciones globales para uso desde otros archivos

### HTML
- Todos los archivos HTML actualizados con:
  - Enlace al CSS del modo oscuro
  - Script del modo oscuro incluido
  - Compatibilidad con la estructura existente

## Uso Técnico

### Para Desarrolladores

#### Obtener el tema actual:
```javascript
const currentTheme = getCurrentTheme(); // 'light' o 'dark'
```

#### Cambiar tema programáticamente:
```javascript
setTheme('dark'); // o 'light'
```

#### Escuchar cambios de tema:
```javascript
document.addEventListener('themeChanged', (event) => {
  console.log('Tema cambiado a:', event.detail.theme);
});
```

### Variables CSS Disponibles

```css
/* Colores principales */
--bg-primary: #ffffff / #1a1a1a
--bg-secondary: #f8f9fa / #2d2d2d
--text-primary: #000000 / #ffffff
--text-secondary: #555555 / #cccccc

/* Componentes específicos */
--navbar-bg: transparent / rgba(45, 45, 45, 0.95)
--card-bg: #ffffff / #2d2d2d
--footer-bg: #111111 / #0a0a0a
--accent-color: #e63946 / #ff4757
```

## Compatibilidad

### Navegadores Soportados
- ✅ Chrome 88+
- ✅ Firefox 87+
- ✅ Safari 14+
- ✅ Edge 88+

### Características Utilizadas
- CSS Custom Properties (Variables)
- localStorage API
- matchMedia API
- ES6 Classes
- Event Listeners

## Instalación y Configuración

### 1. Archivos Requeridos
Asegúrate de que estos archivos estén presentes:
- `css/dark-mode.css`
- `js/dark-mode.js`

### 2. Inclusión en HTML
Agrega estos enlaces en el `<head>` de cada página:
```html
<link rel="stylesheet" href="css/dark-mode.css">
```

Y este script antes del cierre de `</body>`:
```html
<script src="js/dark-mode.js"></script>
```

### 3. Verificación
El modo oscuro se inicializa automáticamente cuando el DOM está listo. Puedes verificar que funciona:
- Abriendo la consola del navegador
- Buscando el mensaje "🌙 Modo oscuro inicializado"
- Probando el botón de cambio de tema

## Personalización

### Cambiar Colores
Edita las variables CSS en `css/dark-mode.css`:

```css
[data-theme="dark"] {
  --bg-primary: #tu-color;
  --accent-color: #tu-accento;
}
```

### Cambiar Posición del Botón
Modifica las propiedades CSS del `.theme-toggle`:

```css
.theme-toggle {
  top: 20px; /* Posición vertical */
  right: 20px; /* Posición horizontal */
}
```

### Agregar Nuevos Elementos
Para estilizar nuevos elementos, usa las variables CSS:

```css
.mi-nuevo-elemento {
  background-color: var(--bg-primary);
  color: var(--text-primary);
}
```

## Troubleshooting

### Problemas Comunes

1. **El botón no aparece**
   - Verifica que `dark-mode.js` esté cargado
   - Revisa la consola para errores JavaScript

2. **Los colores no cambian**
   - Asegúrate de que `dark-mode.css` esté incluido
   - Verifica que los elementos usen variables CSS

3. **La preferencia no se guarda**
   - Verifica que localStorage esté habilitado
   - Revisa la consola para errores de permisos

### Debug
Agrega este código para debug:

```javascript
console.log('Tema actual:', getCurrentTheme());
console.log('localStorage theme:', localStorage.getItem('theme'));
```

## Futuras Mejoras

### Posibles Extensiones
- [ ] Modo automático basado en hora del día
- [ ] Más temas de color (azul, verde, etc.)
- [ ] Animaciones más elaboradas
- [ ] Integración con preferencias del sistema más avanzada

### Optimizaciones
- [ ] Lazy loading de estilos
- [ ] Compresión de CSS
- [ ] Cache de preferencias
- [ ] Métricas de uso del modo oscuro

---

**Desarrollado por:** Asistente IA  
**Fecha:** 2025  
**Versión:** 1.0.0
