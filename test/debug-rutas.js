const { test, expect } = require('@playwright/test');

test.describe('🔍 Debug Específico de Rutas', () => {
  test('debe mostrar información detallada del problema de rutas', async ({ page }) => {
    console.log('🚀 Iniciando debug de rutas...');
    
    // Test desde la raíz
    console.log('\n📍 === TEST DESDE LA RAÍZ ===');
    await page.goto('http://localhost:8000');
    await page.waitForLoadState('networkidle');
    
    const rootInfo = await page.evaluate(() => {
      return {
        url: window.location.href,
        pathname: window.location.pathname,
        basePath: window.BASE_PATH || 'NO DEFINIDO',
        imageOptimizer: typeof window.ImageOptimizer,
        functions: window.ImageOptimizer ? Object.keys(window.ImageOptimizer) : []
      };
    });
    
    console.log('📊 Información desde raíz:', rootInfo);
    
    // Test desde pag/
    console.log('\n📍 === TEST DESDE PAG/ ===');
    await page.goto('http://localhost:8000/pag/productos.html');
    await page.waitForLoadState('networkidle');
    
    const pagInfo = await page.evaluate(() => {
      return {
        url: window.location.href,
        pathname: window.location.pathname,
        basePath: window.BASE_PATH || 'NO DEFINIDO',
        imageOptimizer: typeof window.ImageOptimizer,
        functions: window.ImageOptimizer ? Object.keys(window.ImageOptimizer) : []
      };
    });
    
    console.log('📊 Información desde pag/:', pagInfo);
    
    // Verificar construcción de rutas
    if (pagInfo.imageOptimizer === 'object') {
      console.log('\n🔗 === VERIFICANDO CONSTRUCCIÓN DE RUTAS ===');
      
      const routeTest = await page.evaluate(() => {
        if (window.ImageOptimizer && window.ImageOptimizer.buildPath) {
          return {
            logo: window.ImageOptimizer.buildPath('images/logo.png'),
            css: window.ImageOptimizer.buildPath('css/style.css'),
            js: window.ImageOptimizer.buildPath('js/script.js')
          };
        }
        return null;
      });
      
      console.log('🔗 Rutas construidas:', routeTest);
      
      // Verificar URLs absolutas
      const absoluteUrls = await page.evaluate(() => {
        if (window.ImageOptimizer && window.ImageOptimizer.testPaths) {
          // Capturar console.log
          const originalLog = console.log;
          const logs = [];
          console.log = (...args) => {
            logs.push(args.join(' '));
            originalLog.apply(console, args);
          };
          
          // Ejecutar función de prueba
          window.ImageOptimizer.testPaths();
          
          // Restaurar console.log
          console.log = originalLog;
          
          return logs;
        }
        return [];
      });
      
      console.log('📝 Logs de testPaths:', absoluteUrls);
    }
    
    // Verificar si hay errores en consola
    const consoleErrors = [];
    page.on('console', msg => {
      if (msg.type() === 'error') {
        consoleErrors.push(msg.text());
      }
    });
    
    // Esperar un poco más para capturar errores
    await page.waitForTimeout(2000);
    
    if (consoleErrors.length > 0) {
      console.log('\n❌ === ERRORES EN CONSOLA ===');
      consoleErrors.forEach(error => console.log('❌', error));
    }
    
    // Verificar requests fallidos
    const failedRequests = [];
    page.on('response', response => {
      if (response.status() === 404) {
        failedRequests.push(response.url());
      }
    });
    
    // Hacer un refresh para capturar más requests
    await page.reload();
    await page.waitForLoadState('networkidle');
    
    if (failedRequests.length > 0) {
      console.log('\n❌ === REQUESTS FALLIDOS (404) ===');
      failedRequests.forEach(url => console.log('❌', url));
    }
    
    console.log('\n✅ Debug completado');
  });
});
