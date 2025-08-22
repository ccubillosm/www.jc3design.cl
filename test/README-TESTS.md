# 🧪 Tests Automatizados con Playwright - JC3Design

Este directorio contiene tests automatizados para verificar el funcionamiento correcto del sitio web JC3Design, especialmente el archivo `optimizacion.js` y su manejo de rutas.

## 📋 **Archivos de Test**

### **1. `playwright-tests.spec.js`**
- Tests completos del sitio web
- Verificación de funcionalidades principales
- Tests de rendimiento y manejo de errores

### **2. `test-rutas-mcp.js`**
- Test específico para el problema de rutas
- Debug detallado del archivo `optimizacion.js`
- Verificación de detección de rutas base

### **3. `playwright.config.js`**
- Configuración de Playwright
- Configuración de navegadores
- Configuración del servidor local

### **4. `package.json`**
- Dependencias de Node.js
- Scripts de ejecución
- Configuración del proyecto

## 🚀 **Instalación y Configuración**

### **Paso 1: Instalar Node.js**
```bash
# Verificar que Node.js esté instalado
node --version
npm --version
```

### **Paso 2: Instalar dependencias**
```bash
# Instalar Playwright
npm install

# Instalar navegadores
npx playwright install
```

### **Paso 3: Verificar servidor local**
```bash
# Asegurarse de que el servidor PHP esté corriendo
php -S localhost:8000
```

## 🧪 **Ejecutar Tests**

### **Ejecutar todos los tests**
```bash
npm test
```

### **Ejecutar tests con interfaz visual**
```bash
npm run test:ui
```

### **Ejecutar tests en modo debug**
```bash
npm run test:debug
```

### **Ejecutar tests específicos**
```bash
# Solo tests de rutas
npx playwright test test-rutas-mcp.js

# Solo tests de Chrome
npm run test:chrome

# Solo tests móviles
npm run test:mobile
```

### **Ejecutar tests con navegador visible**
```bash
npm run test:headed
```

## 📊 **Reportes**

### **Ver reporte HTML**
```bash
npm run test:report
```

### **Reportes generados**
- `test-results/` - Directorio con reportes
- `playwright-report/` - Reporte HTML interactivo
- `test-results/results.json` - Reporte en formato JSON
- `test-results/results.xml` - Reporte en formato JUnit

## 🔍 **Tests Específicos de Rutas**

### **Problema a Verificar**
El archivo `optimizacion.js` debe detectar automáticamente si se ejecuta desde:
- **Raíz del sitio** (`./`) → `http://localhost:8000/`
- **Directorio pag/** (`../`) → `http://localhost:8000/pag/`

### **Tests Incluidos**
1. **Detección de ruta base** desde diferentes ubicaciones
2. **Construcción de rutas** para recursos (CSS, JS, imágenes)
3. **Manejo de errores 404** sin fallos fatales
4. **Rendimiento de carga** y recursos
5. **Debug completo** de la funcionalidad

## 🚨 **Solución de Problemas**

### **Error: "Cannot find module '@playwright/test'"**
```bash
npm install
```

### **Error: "No browsers found"**
```bash
npx playwright install
```

### **Error: "Connection refused"**
- Verificar que el servidor PHP esté corriendo en `localhost:8000`
- Verificar que no haya firewall bloqueando el puerto

### **Tests fallan por timeouts**
- Aumentar timeouts en `playwright.config.js`
- Verificar que el servidor responda rápidamente

## 📝 **Personalización de Tests**

### **Agregar nuevos tests**
```javascript
test('Mi nuevo test', async ({ page }) => {
  await page.goto('/mi-pagina');
  await expect(page.locator('h1')).toHaveText('Mi Título');
});
```

### **Modificar configuración**
- Editar `playwright.config.js` para cambiar navegadores
- Modificar timeouts y configuraciones
- Agregar nuevos proyectos de test

### **Agregar nuevos navegadores**
```javascript
{
  name: 'Edge',
  use: { ...devices['Desktop Edge'] },
}
```

## 🔧 **Integración con CI/CD**

### **GitHub Actions**
```yaml
- name: Run Playwright tests
  run: npx playwright test
```

### **Docker**
```dockerfile
FROM mcr.microsoft.com/playwright:v1.40.0
COPY . /app
WORKDIR /app
RUN npm install
CMD ["npx", "playwright", "test"]
```

## 📈 **Monitoreo y Métricas**

### **Métricas de rendimiento**
- Tiempo de carga de páginas
- Número de recursos cargados
- Errores de red y JavaScript

### **Métricas de funcionalidad**
- Funciones disponibles en `ImageOptimizer`
- Rutas base detectadas correctamente
- Manejo de errores 404

## 🎯 **Objetivos de los Tests**

1. **Verificar que `optimizacion.js` funcione** desde cualquier ubicación
2. **Confirmar que las rutas se construyan** correctamente
3. **Asegurar que no haya errores fatales** de JavaScript
4. **Verificar el rendimiento** del sitio web
5. **Probar en múltiples navegadores** y dispositivos

## 📞 **Soporte**

Si tienes problemas con los tests:

1. **Revisa la consola** para errores específicos
2. **Verifica la configuración** del servidor local
3. **Ejecuta tests individuales** para aislar problemas
4. **Revisa los reportes** generados por Playwright

---

**¡Los tests automatizados te ayudarán a identificar y resolver problemas de rutas de manera eficiente!** 🚀
