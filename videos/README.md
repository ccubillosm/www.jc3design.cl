# Directorio de Videos - JC3Design

## 📹 Información del Directorio

Este directorio contiene los archivos de video utilizados en el sitio web de JC3Design.

## 🎬 Videos Implementados

### Video Principal: Proceso JC3Design
- **Archivo**: `proceso-jc3design.mp4`
- **Formato alternativo**: `proceso-jc3design.webm`
- **Descripción**: Video promocional que muestra el proceso de diseño y fabricación de muebles
- **Duración**: 2-3 minutos
- **Calidad**: 1080p (HD)

## 📋 Especificaciones Técnicas

### Formatos Soportados
- **MP4**: Formato principal, compatible con todos los navegadores
- **WebM**: Formato alternativo para mejor compresión
- **Poster**: Imagen de vista previa (construccion.webp)

### Optimizaciones Implementadas
- **Lazy Loading**: El video solo se carga cuando es visible
- **Preload metadata**: Solo se precargan los metadatos inicialmente
- **Adaptive quality**: Se adapta según la velocidad de conexión
- **Mobile optimization**: Versiones optimizadas para dispositivos móviles

## 🛠️ Cómo Agregar Nuevos Videos

### 1. Preparar el Video
```bash
# Convertir a formato MP4
ffmpeg -i video_original.mp4 -c:v libx264 -c:a aac -b:v 2M -b:a 128k proceso-jc3design.mp4

# Convertir a formato WebM
ffmpeg -i video_original.mp4 -c:v libvpx-vp9 -c:a libopus -b:v 1.5M -b:a 128k proceso-jc3design.webm
```

### 2. Crear Poster Image
```bash
# Extraer frame como poster
ffmpeg -i proceso-jc3design.mp4 -ss 00:00:01 -vframes 1 poster.jpg
```

### 3. Agregar al HTML
```html
<video id="promoVideo" preload="metadata" poster="images/poster.jpg">
  <source src="videos/proceso-jc3design.mp4" type="video/mp4">
  <source src="videos/proceso-jc3design.webm" type="video/webm">
  Tu navegador no soporta el elemento de video.
</video>
```

## 📊 Optimizaciones de Rendimiento

### Tamaños Recomendados
- **Desktop**: 1920x1080 (1080p)
- **Tablet**: 1280x720 (720p)
- **Mobile**: 854x480 (480p)

### Compresión
- **MP4**: H.264 codec, bitrate 2Mbps
- **WebM**: VP9 codec, bitrate 1.5Mbps
- **Audio**: AAC/Opus, bitrate 128kbps

## 🎯 Mejores Prácticas

### Contenido
- **Duración**: 2-3 minutos máximo
- **Mensaje claro**: Enfoque en el proceso y beneficios
- **Call to action**: Incluir información de contacto

### Técnico
- **Optimizar tamaño**: Máximo 50MB por video
- **Formatos múltiples**: MP4 + WebM para compatibilidad
- **Poster image**: Frame representativo del video
- **Subtítulos**: Considerar agregar subtítulos para accesibilidad

## 🔧 Configuración del Reproductor

### Controles Disponibles
- **Play/Pause**: Botón principal y tecla Espacio
- **Mute/Unmute**: Botón de volumen y tecla M
- **Seek**: Teclas de flecha izquierda/derecha
- **Overlay**: Click en el overlay para reproducir

### Personalización
```javascript
// Configurar reproductor personalizado
const videoPlayer = new VideoPlayer(
  'promoVideo',      // ID del elemento video
  'videoOverlay',    // ID del overlay
  'playPauseBtn',    // ID del botón play/pause
  'muteBtn'          // ID del botón mute
);
```

## 📈 Analytics y Tracking

### Métricas a Monitorear
- **Play rate**: Porcentaje de usuarios que reproducen el video
- **Completion rate**: Porcentaje que ve el video completo
- **Engagement time**: Tiempo promedio de visualización
- **Drop-off points**: Puntos donde los usuarios dejan de ver

### Implementación
```javascript
// Tracking de eventos de video
video.addEventListener('play', () => {
  // Analytics: Video started
});

video.addEventListener('ended', () => {
  // Analytics: Video completed
});
```

## 🚀 Próximas Mejoras

### Funcionalidades Planificadas
1. **Video personalizado**: Contenido específico por usuario
2. **Analytics avanzado**: Tracking detallado de engagement
3. **Subtítulos**: Soporte para múltiples idiomas
4. **Playlist**: Múltiples videos en secuencia
5. **Social sharing**: Compartir videos en redes sociales

---

**Nota**: Los videos deben ser optimizados antes de subirse al servidor para garantizar el mejor rendimiento y experiencia de usuario.
