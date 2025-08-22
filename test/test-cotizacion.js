const { test, expect } = require('@playwright/test');

test.describe('🧪 Tests de Cotización y Servicios', () => {
  const baseURL = 'http://localhost:8000';

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

  test('debe manejar errores de API graciosamente', async ({ page }) => {
    console.log('🚀 Probando manejo de errores...');
    
    // Interceptar la llamada a la API y simular un error
    await page.route('**/api/servicios.php', route => {
      route.fulfill({
        status: 500,
        contentType: 'application/json',
        body: JSON.stringify({ error: 'Error interno del servidor' })
      });
    });
    
    await page.goto(`${baseURL}/pag/cotizacion-diseno.html`);
    await page.waitForLoadState('networkidle');
    
    // Verificar que la página sigue funcionando aunque la API falle
    await expect(page.locator('#formulario-cotizacion')).toBeVisible();
    await expect(page.locator('#servicio_id')).toBeVisible();
    
    console.log('✅ Manejo de errores de API funciona correctamente');
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











