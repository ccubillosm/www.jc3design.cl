<?php
/**
 * Script de prueba automatizada para el sistema de múltiples imágenes
 */

echo "=== TEST SISTEMA DE MÚLTIPLES IMÁGENES ===\n";
echo "Fecha: " . date('Y-m-d H:i:s') . "\n\n";

$tests = [];
$errors = [];

// Test 1: Verificar base de datos
echo "1. Verificando base de datos...\n";
try {
    $pdo = new PDO('mysql:host=localhost;dbname=jc3design_db', 'root', '');
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM producto_imagenes");
    $total = $stmt->fetch()['total'];
    
    if ($total > 0) {
        echo "✓ Base de datos: $total imágenes encontradas\n";
        $tests[] = "Base de datos";
    } else {
        echo "✗ Base de datos: No hay imágenes\n";
        $errors[] = "Base de datos";
    }
} catch (Exception $e) {
    echo "✗ Error en base de datos: " . $e->getMessage() . "\n";
    $errors[] = "Base de datos";
}

// Test 2: Verificar API
echo "\n2. Verificando API...\n";
$api_response = file_get_contents('http://localhost:8000/api/productos.php?id=1');
if ($api_response) {
    $data = json_decode($api_response, true);
    if (isset($data['imagenes']) && is_array($data['imagenes'])) {
        $count = count($data['imagenes']);
        echo "✓ API: $count imágenes retornadas para producto ID 1\n";
        $tests[] = "API";
    } else {
        echo "✗ API: No se encontraron imágenes en la respuesta\n";
        $errors[] = "API";
    }
} else {
    echo "✗ API: No se pudo conectar\n";
    $errors[] = "API";
}

// Test 3: Verificar archivos JavaScript
echo "\n3. Verificando archivos JavaScript...\n";
$js_files = [
    'js/productoDetalle.js',
    'js/productos.js'
];

foreach ($js_files as $file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        if (strpos($content, 'gallery') !== false || strpos($content, 'galería') !== false) {
            echo "✓ JavaScript: $file contiene código de galería\n";
            $tests[] = "JavaScript $file";
        } else {
            echo "✗ JavaScript: $file no contiene código de galería\n";
            $errors[] = "JavaScript $file";
        }
    } else {
        echo "✗ JavaScript: $file no existe\n";
        $errors[] = "JavaScript $file";
    }
}

// Test 4: Verificar archivos CSS
echo "\n4. Verificando archivos CSS...\n";
$css_files = [
    'css/style.css'
];

foreach ($css_files as $file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        if (strpos($content, '.gallery') !== false || strpos($content, '.product-gallery') !== false) {
            echo "✓ CSS: $file contiene estilos de galería\n";
            $tests[] = "CSS $file";
        } else {
            echo "✗ CSS: $file no contiene estilos de galería\n";
            $errors[] = "CSS $file";
        }
    } else {
        echo "✗ CSS: $file no existe\n";
        $errors[] = "CSS $file";
    }
}

// Test 5: Verificar páginas de administración
echo "\n5. Verificando páginas de administración...\n";
$admin_files = [
    'admin/imagenes.php',
    'admin/agregar_imagenes_ejemplo.php'
];

foreach ($admin_files as $file) {
    if (file_exists($file)) {
        echo "✓ Administración: $file existe\n";
        $tests[] = "Administración $file";
    } else {
        echo "✗ Administración: $file no existe\n";
        $errors[] = "Administración $file";
    }
}

// Test 6: Verificar páginas frontend
echo "\n6. Verificando páginas frontend...\n";
$frontend_files = [
    'pag/producto.html',
    'pag/productos.html'
];

foreach ($frontend_files as $file) {
    if (file_exists($file)) {
        echo "✓ Frontend: $file existe\n";
        $tests[] = "Frontend $file";
    } else {
        echo "✗ Frontend: $file no existe\n";
        $errors[] = "Frontend $file";
    }
}

// Test 7: Verificar imágenes de ejemplo
echo "\n7. Verificando imágenes de ejemplo...\n";
$image_files = [
    'images/p13w_jc3d.jpg',
    'images/mueble_1.jpg',
    'images/logo.png'
];

foreach ($image_files as $file) {
    if (file_exists($file)) {
        echo "✓ Imagen: $file existe\n";
        $tests[] = "Imagen $file";
    } else {
        echo "✗ Imagen: $file no existe\n";
        $errors[] = "Imagen $file";
    }
}

// Resumen
echo "\n=== RESUMEN ===\n";
echo "Tests exitosos: " . count($tests) . "\n";
echo "Errores: " . count($errors) . "\n";

if (count($errors) == 0) {
    echo "\n🎉 ¡TODOS LOS TESTS PASARON EXITOSAMENTE!\n";
    echo "El sistema de múltiples imágenes está funcionando correctamente.\n";
} else {
    echo "\n⚠️  Se encontraron algunos errores:\n";
    foreach ($errors as $error) {
        echo "- $error\n";
    }
}

echo "\n=== INSTRUCCIONES PARA PROBAR MANUALMENTE ===\n";
echo "1. Panel de administración: http://localhost:8000/admin/imagenes.php\n";
echo "2. Página de producto: http://localhost:8000/pag/producto.html?id=1\n";
echo "3. Agregar imágenes de ejemplo: http://localhost:8000/admin/agregar_imagenes_ejemplo.php\n";
echo "4. API de productos: http://localhost:8000/api/productos.php?id=1\n";
