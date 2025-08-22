// Archivo de prueba mínimo
console.log('🚀 Archivo de prueba mínimo cargado');

// Función simple de prueba
function testFunction() {
    console.log('✅ Función de prueba ejecutada');
    return 'Funciona correctamente';
}

// Exportar al objeto global
window.TestMinimal = {
    testFunction: testFunction,
    message: 'Archivo de prueba cargado correctamente'
};

console.log('📦 Objeto TestMinimal exportado:', window.TestMinimal);
