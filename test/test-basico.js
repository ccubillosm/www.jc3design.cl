const { test, expect } = require('@playwright/test');

test.describe('🔍 Test Básico de Conectividad', () => {
  test('debe poder conectarse al servidor local', async ({ page }) => {
    console.log('🚀 Iniciando test de conectividad...');
    
    try {
      // Intentar cargar la página principal
      await page.goto('http://localhost:8000');
      console.log('✅ Página cargada exitosamente');
      
      // Verificar que la página tenga contenido
      const title = await page.title();
      console.log('📄 Título de la página:', title);
      
      // Verificar que el body esté presente
      const body = await page.locator('body');
      await expect(body).toBeVisible();
      console.log('✅ Body de la página visible');
      
    } catch (error) {
      console.error('❌ Error al cargar la página:', error.message);
      throw error;
    }
  });

  test('debe poder cargar una página del directorio pag', async ({ page }) => {
    console.log('🚀 Probando carga desde directorio pag...');
    
    try {
      await page.goto('http://localhost:8000/pag/productos.html');
      console.log('✅ Página productos.html cargada');
      
      const title = await page.title();
      console.log('📄 Título de productos:', title);
      
    } catch (error) {
      console.error('❌ Error al cargar productos.html:', error.message);
      throw error;
    }
  });

  test('debe verificar que el archivo optimizacion.js existe', async ({ page }) => {
    console.log('🚀 Verificando existencia de optimizacion.js...');
    
    try {
      // Intentar cargar directamente el archivo JS
      const response = await page.goto('http://localhost:8000/js/optimizacion.js');
      console.log('📄 Status de optimizacion.js:', response.status());
      
      if (response.status() === 200) {
        console.log('✅ Archivo optimizacion.js encontrado');
        
        // Verificar contenido básico
        const content = await response.text();
        console.log('📏 Tamaño del archivo:', content.length, 'caracteres');
        console.log('🔍 Contiene "ImageOptimizer":', content.includes('ImageOptimizer'));
        
      } else {
        console.log('❌ Archivo optimizacion.js no encontrado o error:', response.status());
      }
      
    } catch (error) {
      console.error('❌ Error al verificar optimizacion.js:', error.message);
      throw error;
    }
  });
});
