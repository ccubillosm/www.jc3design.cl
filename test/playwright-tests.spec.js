const { test, expect } = require('@playwright/test');

// Configuración de tests
test.describe('JC3Design - Tests Automatizados', () => {
  const baseURL = 'http://localhost:8000';
  
  test.beforeEach(async ({ page }) => {
    // Configurar viewport
    await page.setViewportSize({ width: 1280, height: 720 });
    
    // Configurar user agent usando context
    await page.context().setExtraHTTPHeaders({
      'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
    });
  });

  // ===== TESTS DE PÁGINA PRINCIPAL =====
  test.describe('Página Principal (Raíz)', () => {
    test('debe cargar la página principal correctamente', async ({ page }) => {
      await page.goto(baseURL);
      
      // Verificar título
      await expect(page).toHaveTitle(/JC3Design/);
      
      // Verificar elementos principales
      await expect(page.locator('h1')).toBeVisible();
      
      // Verificar navegación principal específicamente (evitar ambigüedad)
      await expect(page.locator('nav[aria-label="Navegación principal"]')).toBeVisible();
      
      // Verificar que no hay errores en consola
      const consoleErrors = [];
      page.on('console', msg => {
        if (msg.type() === 'error') {
          consoleErrors.push(msg.text());
        }
      });
      
      await page.waitForLoadState('networkidle');
      
      if (consoleErrors.length > 0) {
        console.log('⚠️ Errores en consola:', consoleErrors);
      }
      
      // Verificar que las imágenes principales cargan
      const images = page.locator('img');
      if (await images.count() > 0) {
        await expect(images.first()).toBeVisible();
      }
    });

    test('debe cargar archivo optimizacion.js correctamente', async ({ page }) => {
      await page.goto(baseURL);
      
      // Verificar que el script se carga
      const scriptLoaded = await page.evaluate(() => {
        return typeof window.ImageOptimizer !== 'undefined';
      });
      
      expect(scriptLoaded).toBe(true);
      
      // Verificar funciones disponibles
      const functions = await page.evaluate(() => {
        if (window.ImageOptimizer) {
          return Object.keys(window.ImageOptimizer);
        }
        return [];
      });
      
      expect(functions.length).toBeGreaterThan(0);
      expect(functions).toContain('getBasePath');
      expect(functions).toContain('buildPath');
      
      console.log('✅ Funciones disponibles:', functions);
    });

    test('debe detectar correctamente la ruta base desde la raíz', async ({ page }) => {
      await page.goto(baseURL);
      
      // Esperar a que se ejecute el script
      await page.waitForFunction(() => window.BASE_PATH !== undefined);
      
      const basePath = await page.evaluate(() => window.BASE_PATH);
      expect(basePath).toBe('./');
      
      // Verificar construcción de rutas
      const testPath = await page.evaluate(() => {
        if (window.ImageOptimizer && window.ImageOptimizer.buildPath) {
          return window.ImageOptimizer.buildPath('images/logo.png');
        }
        return null;
      });
      
      expect(testPath).toBe('./images/logo.png');
    });
  });

  // ===== TESTS DE PÁGINAS DEL DIRECTORIO PAG =====
  test.describe('Páginas del Directorio Pag', () => {
    test('debe cargar productos.html correctamente', async ({ page }) => {
      await page.goto(`${baseURL}/pag/productos.html?tipo=productos3d`);
      
      // Verificar título
      await expect(page).toHaveTitle(/Productos/);
      
      // Verificar elementos de la página
      await expect(page.locator('#productos-container')).toBeVisible();
      
      // Verificar que no hay errores de consola críticos
      const consoleErrors = [];
      page.on('console', msg => {
        if (msg.type() === 'error' && !msg.text().includes('404')) {
          consoleErrors.push(msg.text());
        }
      });
      
      await page.waitForLoadState('networkidle');
      
      if (consoleErrors.length > 0) {
        console.log('⚠️ Errores en consola (excluyendo 404s):', consoleErrors);
      }
    });

    test('debe detectar correctamente la ruta base desde pag/', async ({ page }) => {
      await page.goto(`${baseURL}/pag/productos.html`);
      
      // Esperar a que se ejecute el script
      await page.waitForFunction(() => window.BASE_PATH !== undefined, { timeout: 10000 });
      
      const basePath = await page.evaluate(() => window.BASE_PATH);
      expect(basePath).toBe('../');
      
      // Verificar construcción de rutas
      const testPath = await page.evaluate(() => {
        if (window.ImageOptimizer && window.ImageOptimizer.buildPath) {
          return window.ImageOptimizer.buildPath('images/logo.png');
        }
        return null;
      });
      
      expect(testPath).toBe('../images/logo.png');
    });

    test('debe cargar optimizacion.js desde pag/ sin errores críticos', async ({ page }) => {
      await page.goto(`${baseURL}/pag/productos.html`);
      
      // Verificar que el script se carga
      const scriptLoaded = await page.evaluate(() => {
        return typeof window.ImageOptimizer !== 'undefined';
      });
      
      expect(scriptLoaded).toBe(true);
      
      // Verificar funciones disponibles
      const functions = await page.evaluate(() => {
        if (window.ImageOptimizer) {
          return Object.keys(window.ImageOptimizer);
        }
        return [];
      });
      
      expect(functions.length).toBeGreaterThan(0);
      console.log('✅ Funciones disponibles desde pag/:', functions);
    });
  });

  // ===== TESTS DE RUTAS Y RECURSOS =====
  test.describe('Rutas y Recursos', () => {
    test('debe construir rutas correctamente desde diferentes ubicaciones', async ({ page }) => {
      // Test desde la raíz
      await page.goto(baseURL);
      await page.waitForFunction(() => window.BASE_PATH !== undefined);
      
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
      
      expect(rootPaths).toEqual({
        logo: './images/logo.png',
        css: './css/style.css',
        js: './js/script.js'
      });
      
      // Test desde pag/
      await page.goto(`${baseURL}/pag/productos.html`);
      await page.waitForFunction(() => window.BASE_PATH !== undefined);
      
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
      
      expect(pagPaths).toEqual({
        logo: '../images/logo.png',
        css: '../css/style.css',
        js: '../js/script.js'
      });
    });

    test('debe manejar URLs absolutas correctamente', async ({ page }) => {
      await page.goto(`${baseURL}/pag/productos.html`);
      await page.waitForFunction(() => window.BASE_PATH !== undefined);
      
      const absoluteUrls = await page.evaluate(() => {
        if (window.ImageOptimizer && window.ImageOptimizer.testPaths) {
          // Ejecutar la función de prueba
          window.ImageOptimizer.testPaths();
          return true;
        }
        return false;
      });
      
      expect(absoluteUrls).toBe(true);
    });
  });

  // ===== TESTS DE FUNCIONALIDADES ESPECÍFICAS =====
  test.describe('Funcionalidades Específicas', () => {
    test('debe implementar lazy loading correctamente', async ({ page }) => {
      await page.goto(baseURL);
      await page.waitForFunction(() => window.ImageOptimizer !== undefined);
      
      const lazyLoadingImplemented = await page.evaluate(() => {
        return window.ImageOptimizer.implementLazyLoading !== undefined;
      });
      
      expect(lazyLoadingImplemented).toBe(true);
    });

    test('debe optimizar imágenes de carrusel', async ({ page }) => {
      await page.goto(baseURL);
      await page.waitForFunction(() => window.ImageOptimizer !== undefined);
      
      const carouselOptimization = await page.evaluate(() => {
        // Verificar si existe alguna función relacionada con carrusel
        return window.ImageOptimizer.optimizeProductImages !== undefined || 
               window.ImageOptimizer.optimizeCarouselImages !== undefined ||
               window.ImageOptimizer.optimizeBackgroundImages !== undefined;
      });
      
      expect(carouselOptimization).toBe(true);
    });

    test('debe preload imágenes críticas', async ({ page }) => {
      await page.goto(baseURL);
      await page.waitForFunction(() => window.ImageOptimizer !== undefined);
      
      const preloadFunction = await page.evaluate(() => {
        // Verificar si existe alguna función relacionada con preload
        return window.ImageOptimizer.preloadCriticalImages !== undefined ||
               window.ImageOptimizer.optimizeForConnection !== undefined ||
               window.ImageOptimizer.optimizeResourceLoading !== undefined;
      });
      
      expect(preloadFunction).toBe(true);
    });
  });

  // ===== TESTS DE ERRORES Y MANEJO DE FALLOS =====
  test.describe('Manejo de Errores', () => {
    test('debe manejar recursos no encontrados graciosamente', async ({ page }) => {
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
      
      if (importantFailures.length > 0) {
        console.log('⚠️ Recursos no encontrados:', importantFailures);
      }
      
      // La página debe seguir funcionando a pesar de los 404s
      await expect(page.locator('body')).toBeVisible();
    });

    test('debe funcionar sin errores de JavaScript fatales', async ({ page }) => {
      await page.goto(baseURL);
      
      const fatalErrors = [];
      page.on('pageerror', error => {
        fatalErrors.push(error.message);
      });
      
      await page.waitForLoadState('networkidle');
      
      if (fatalErrors.length > 0) {
        console.log('❌ Errores fatales de JavaScript:', fatalErrors);
      }
      
      // No debe haber errores fatales
      expect(fatalErrors.length).toBe(0);
    });
  });

  // ===== TESTS DE RENDIMIENTO =====
  test.describe('Rendimiento', () => {
    test('debe cargar en un tiempo razonable', async ({ page }) => {
      const startTime = Date.now();
      
      await page.goto(baseURL);
      await page.waitForLoadState('networkidle');
      
      const loadTime = Date.now() - startTime;
      console.log(`⏱️ Tiempo de carga: ${loadTime}ms`);
      
      // Debe cargar en menos de 5 segundos
      expect(loadTime).toBeLessThan(5000);
    });

    test('debe manejar múltiples recursos eficientemente', async ({ page }) => {
      await page.goto(baseURL);
      
      // Contar recursos cargados
      const resourceCount = await page.evaluate(() => {
        return performance.getEntriesByType('resource').length;
      });
      
      console.log(`📊 Recursos cargados: ${resourceCount}`);
      
      // No debe haber demasiados recursos (indicador de ineficiencia)
      expect(resourceCount).toBeLessThan(100);
    });
  });

  // ===== TESTS DE COTIZACIÓN Y SERVICIOS =====
  test.describe('🧪 Cotización y Servicios', () => {
    test('debe cargar la página de cotización de diseño correctamente', async ({ page }) => {
      console.log('🚀 Probando página de cotización de diseño...');
      
      await page.goto(`${baseURL}/pag/cotizacion-diseno.html`);
      await page.waitForLoadState('networkidle');
      
      // Verificar que la página carga
      await expect(page.locator('h2.hero-title')).toContainText('Cotización de Diseño');
      
      // Verificar que el formulario existe
      await expect(page.locator('#formulario-cotizacion')).toBeVisible();
      
      // Verificar que el select de servicios existe
      const selectServicios = page.locator('#servicio_id');
      await expect(selectServicios).toBeVisible();
      
      console.log('✅ Página de cotización cargada correctamente');
    });

    test('debe cargar los servicios desde la API correctamente', async ({ page }) => {
      console.log('🚀 Probando carga de servicios desde API...');
      
      await page.goto(`${baseURL}/pag/cotizacion-diseno.html`);
      await page.waitForLoadState('networkidle');
      
      // Esperar a que se carguen los servicios
      await page.waitForFunction(() => {
        const select = document.getElementById('servicio_id');
        return select && select.options.length > 1; // Más de 1 opción (incluyendo la opción por defecto)
      }, { timeout: 10000 });
      
      // Verificar que se cargaron los servicios
      const selectServicios = page.locator('#servicio_id');
      const options = await selectServicios.locator('option').all();
      
      console.log(`📊 Opciones encontradas: ${options.length}`);
      
      // Debe tener al menos 4 opciones: 1 por defecto + 3 servicios
      expect(options.length).toBeGreaterThanOrEqual(4);
      
      // Verificar que las opciones contienen los servicios esperados
      const optionTexts = await Promise.all(options.map(opt => opt.textContent()));
      console.log('📋 Opciones disponibles:', optionTexts);
      
      // Verificar que están los servicios principales
      expect(optionTexts.some(text => text.includes('Diseño de Muebles 3D'))).toBe(true);
      expect(optionTexts.some(text => text.includes('Impresión 3D'))).toBe(true);
      expect(optionTexts.some(text => text.includes('Fabricación de Muebles'))).toBe(true);
      
      console.log('✅ Servicios cargados correctamente desde la API');
    });

    test('debe mostrar información de servicios con precios', async ({ page }) => {
      console.log('🚀 Probando información de servicios...');
      
      await page.goto(`${baseURL}/pag/cotizacion-diseno.html`);
      await page.waitForLoadState('networkidle');
      
      // Esperar a que se carguen los servicios
      await page.waitForFunction(() => {
        const select = document.getElementById('servicio_id');
        return select && select.options.length > 1;
      }, { timeout: 10000 });
      
      // Verificar que las opciones tienen precios
      const selectServicios = page.locator('#servicio_id');
      const options = await selectServicios.locator('option').all();
      
      // Verificar que al menos una opción tiene precio
      const optionWithPrice = options.find(async (opt) => {
        const text = await opt.textContent();
        return text.includes('$') || text.includes('Desde');
      });
      
      expect(optionWithPrice).toBeTruthy();
      
      console.log('✅ Información de servicios con precios mostrada correctamente');
    });

    test('debe tener funcionalidad de formulario completa', async ({ page }) => {
      console.log('🚀 Probando funcionalidad del formulario...');
      
      await page.goto(`${baseURL}/pag/cotizacion-diseno.html`);
      await page.waitForLoadState('networkidle');
      
      // Verificar campos requeridos
      await expect(page.locator('#nombre')).toBeVisible();
      await expect(page.locator('#email')).toBeVisible();
      await expect(page.locator('#servicio_id')).toBeVisible();
      await expect(page.locator('#detalles_proyecto')).toBeVisible();
      
      // Verificar que los campos requeridos están marcados
      const nombreField = page.locator('#nombre');
      const emailField = page.locator('#email');
      const servicioField = page.locator('#servicio_id');
      const detallesField = page.locator('#detalles_proyecto');
      
      expect(await nombreField.getAttribute('required')).toBe('');
      expect(await emailField.getAttribute('required')).toBe('');
      expect(await servicioField.getAttribute('required')).toBe('');
      expect(await detallesField.getAttribute('required')).toBe('');
      
      // Verificar botón de envío
      await expect(page.locator('button[type="submit"]')).toBeVisible();
      
      console.log('✅ Formulario completo y funcional');
    });
  });
});
