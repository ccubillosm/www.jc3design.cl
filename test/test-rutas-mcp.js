const { test, expect } = require('@playwright/test');

// Test específico para verificar el problema de rutas
test.describe('🔍 Test Específico de Rutas - MCP Playwright', () => {
  const baseURL = 'http://localhost:8000';
  
  test.beforeEach(async ({ page }) => {
    // Configurar viewport
    await page.setViewportSize({ width: 1280, height: 720 });
    
    // Interceptar todos los requests para debug
    page.on('request', request => {
      console.log(`🌐 Request: ${request.method()} ${request.url()}`);
    });
    
    // Interceptar responses fallidas
    page.on('response', response => {
      if (response.status() >= 400) {
        console.log(`❌ Response Error: ${response.status()} ${response.url()}`);
      }
    });
  });

  test('🔧 Verificar carga de optimizacion.js desde la raíz', async ({ page }) => {
    console.log('🚀 Iniciando test desde la raíz...');
    
    await page.goto(baseURL);
    
    // Esperar a que la página cargue completamente
    await page.waitForLoadState('networkidle');
    
    // Verificar que el script se carga
    const scriptLoaded = await page.evaluate(() => {
      return typeof window.ImageOptimizer !== 'undefined';
    });
    
    console.log('📦 Script cargado:', scriptLoaded);
    expect(scriptLoaded).toBe(true);
    
    // Verificar funciones disponibles
    const functions = await page.evaluate(() => {
      if (window.ImageOptimizer) {
        return Object.keys(window.ImageOptimizer);
      }
      return [];
    });
    
    console.log('🔧 Funciones disponibles:', functions);
    expect(functions.length).toBeGreaterThan(0);
    
    // Verificar ruta base
    const basePath = await page.evaluate(() => {
      return window.BASE_PATH || 'no definido';
    });
    
    console.log('📍 Ruta base detectada:', basePath);
    expect(basePath).toBe('./');
  });

  test('🔧 Verificar carga de optimizacion.js desde pag/', async ({ page }) => {
    console.log('🚀 Iniciando test desde pag/...');
    
    await page.goto(`${baseURL}/pag/productos.html`);
    
    // Esperar a que la página cargue completamente
    await page.waitForLoadState('networkidle');
    
    // Verificar que el script se carga
    const scriptLoaded = await page.evaluate(() => {
      return typeof window.ImageOptimizer !== 'undefined';
    });
    
    console.log('📦 Script cargado desde pag/:', scriptLoaded);
    expect(scriptLoaded).toBe(true);
    
    // Verificar ruta base
    const basePath = await page.evaluate(() => {
      return window.BASE_PATH || 'no definido';
    });
    
    console.log('📍 Ruta base detectada desde pag/:', basePath);
    expect(basePath).toBe('../');
  });

  test('🔗 Verificar construcción de rutas desde diferentes ubicaciones', async ({ page }) => {
    // Test desde la raíz
    console.log('🔍 Probando rutas desde la raíz...');
    await page.goto(baseURL);
    await page.waitForLoadState('networkidle');
    
    const rootPaths = await page.evaluate(() => {
      if (window.ImageOptimizer && window.ImageOptimizer.buildPath) {
        return {
          logo: window.ImageOptimizer.buildPath('images/logo.png'),
          css: window.ImageOptimizer.buildPath('css/style.css'),
          js: window.ImageOptimizer.buildPath('js/script.js')
        };
      }
      return null;
    });
    
    console.log('📍 Rutas desde raíz:', rootPaths);
    expect(rootPaths).toEqual({
      logo: './images/logo.png',
      css: './css/style.css',
      js: './js/script.js'
    });
    
    // Test desde pag/
    console.log('🔍 Probando rutas desde pag/...');
    await page.goto(`${baseURL}/pag/productos.html`);
    await page.waitForLoadState('networkidle');
    
    const pagPaths = await page.evaluate(() => {
      if (window.ImageOptimizer && window.ImageOptimizer.buildPath) {
        return {
          logo: window.ImageOptimizer.buildPath('images/logo.png'),
          css: window.ImageOptimizer.buildPath('css/style.css'),
          js: window.ImageOptimizer.buildPath('js/script.js')
        };
      }
      return null;
    });
    
    console.log('📍 Rutas desde pag/:', pagPaths);
    expect(pagPaths).toEqual({
      logo: '../images/logo.png',
      css: '../css/style.css',
      js: '../js/script.js'
    });
  });

  test('📊 Verificar URLs absolutas generadas', async ({ page }) => {
    console.log('🔍 Probando generación de URLs absolutas...');
    
    await page.goto(`${baseURL}/pag/productos.html`);
    await page.waitForLoadState('networkidle');
    
    // Ejecutar función de prueba
    const testResult = await page.evaluate(() => {
      if (window.ImageOptimizer && window.ImageOptimizer.testPaths) {
        // Capturar console.log para ver las URLs
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
    
    console.log('📝 Logs de testPaths:', testResult);
    
    // Verificar que se ejecutó la función
    expect(testResult.length).toBeGreaterThan(0);
  });

  test('🚨 Verificar manejo de errores 404', async ({ page }) => {
    console.log('🔍 Probando manejo de errores 404...');
    
    await page.goto(`${baseURL}/pag/productos.html`);
    
    // Interceptar requests fallidos
    const failedRequests = [];
    page.on('response', response => {
      if (response.status() === 404) {
        failedRequests.push(response.url());
      }
    });
    
    await page.waitForLoadState('networkidle');
    
    // Filtrar solo requests de recursos importantes
    const importantFailures = failedRequests.filter(url => 
      url.includes('/images/') || url.includes('/css/') || url.includes('/js/')
    );
    
    console.log('❌ Recursos no encontrados (404):', importantFailures);
    
    // La página debe seguir funcionando a pesar de los 404s
    await expect(page.locator('body')).toBeVisible();
    
    // Verificar que no hay errores fatales de JavaScript
    const fatalErrors = [];
    page.on('pageerror', error => {
      fatalErrors.push(error.message);
    });
    
    if (fatalErrors.length > 0) {
      console.log('💥 Errores fatales de JavaScript:', fatalErrors);
    }
    
    // No debe haber errores fatales
    expect(fatalErrors.length).toBe(0);
  });

  test('⚡ Verificar rendimiento de carga', async ({ page }) => {
    console.log('🔍 Probando rendimiento de carga...');
    
    const startTime = Date.now();
    
    await page.goto(`${baseURL}/pag/productos.html`);
    await page.waitForLoadState('networkidle');
    
    const loadTime = Date.now() - startTime;
    console.log(`⏱️ Tiempo de carga: ${loadTime}ms`);
    
    // Debe cargar en menos de 10 segundos
    expect(loadTime).toBeLessThan(10000);
    
    // Contar recursos cargados
    const resourceCount = await page.evaluate(() => {
      return performance.getEntriesByType('resource').length;
    });
    
    console.log(`📊 Recursos cargados: ${resourceCount}`);
    
    // No debe haber demasiados recursos
    expect(resourceCount).toBeLessThan(100);
  });

  test('🔍 Debug completo de la página productos.html', async ({ page }) => {
    console.log('🔍 Iniciando debug completo...');
    
    await page.goto(`${baseURL}/pag/productos.html`);
    
    // Capturar toda la información de debug
    const debugInfo = await page.evaluate(() => {
      const info = {
        url: window.location.href,
        pathname: window.location.pathname,
        basePath: window.BASE_PATH || 'no definido',
        imageOptimizer: typeof window.ImageOptimizer,
        functions: [],
        consoleErrors: [],
        networkErrors: []
      };
      
      if (window.ImageOptimizer) {
        info.functions = Object.keys(window.ImageOptimizer);
      }
      
      return info;
    });
    
    console.log('📊 Información de debug:', debugInfo);
    
    // Verificar que tenemos la información básica
    expect(debugInfo.url).toContain('/pag/productos.html');
    expect(debugInfo.pathname).toContain('/pag/');
    expect(debugInfo.basePath).toBe('../');
    expect(debugInfo.imageOptimizer).toBe('object');
    expect(debugInfo.functions.length).toBeGreaterThan(0);
  });
});
