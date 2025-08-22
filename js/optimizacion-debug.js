// Versión simplificada para debug - JC3Design
console.log('🚀 Cargando optimizacion-debug.js...');

// Función simple para detectar ruta base
function getBasePath() {
  const currentPath = window.location.pathname;
  console.log('🔍 Pathname detectado:', currentPath);
  
  if (currentPath.includes('/pag/')) {
    console.log('📍 Detectado directorio pag/, usando ../');
    return '../';
  } else {
    console.log('📍 Detectada raíz, usando ./');
    return './';
  }
}

// Función simple para construir rutas
function buildPath(relativePath) {
  const basePath = getBasePath();
  const fullPath = basePath + relativePath;
  console.log(`🔗 Construyendo ruta: ${relativePath} → ${fullPath}`);
  return fullPath;
}

// Función de prueba
function testPaths() {
  console.log('🧪 === PRUEBA DE RUTAS SIMPLIFICADA ===');
  console.log('Ruta base:', getBasePath());
  console.log('Logo:', buildPath('images/logo.png'));
  console.log('CSS:', buildPath('css/style.css'));
  console.log('JS:', buildPath('js/script.js'));
  console.log('========================');
}

// Establecer variables globales
window.BASE_PATH = getBasePath();
window.ImageOptimizer = {
  getBasePath: getBasePath,
  buildPath: buildPath,
  testPaths: testPaths
};

// Ejecutar prueba automáticamente
console.log('🔧 BASE_PATH establecido:', window.BASE_PATH);
console.log('🔧 ImageOptimizer disponible:', Object.keys(window.ImageOptimizer));

// Ejecutar prueba después de un delay
setTimeout(() => {
  console.log('⏰ Ejecutando prueba automática...');
  testPaths();
}, 1000);

console.log('✅ optimizacion-debug.js cargado completamente');
