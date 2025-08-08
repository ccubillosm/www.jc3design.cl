<?php
/**
 * Script de QA automatizada para JC3Design
 * Verifica todas las funcionalidades principales del sitio
 */

require_once __DIR__ . '/../database/config.php';

echo "=== QA TEST JC3DESIGN ===\n";
echo "Fecha: " . date('Y-m-d H:i:s') . "\n\n";

$tests = [];
$errors = [];

// Test 1: Conexión a la base de datos
echo "1. Probando conexión a la base de datos...\n";
try {
    $db = getDB();
    $result = $db->fetchOne("SELECT 1 as test");
    if ($result && $result['test'] == 1) {
        echo "✓ Conexión a la base de datos exitosa\n";
        $tests[] = "Conexión a la base de datos";
    } else {
        echo "✗ Error en conexión a la base de datos\n";
        $errors[] = "Conexión a la base de datos";
    }
} catch (Exception $e) {
    echo "✗ Error en conexión a la base de datos: " . $e->getMessage() . "\n";
    $errors[] = "Conexión a la base de datos";
}

// Test 2: Verificar tablas principales
echo "\n2. Verificando tablas principales...\n";
$tables = ['productos', 'categorias', 'contactos', 'cotizaciones', 'servicios', 'usuarios'];
foreach ($tables as $table) {
    try {
        $result = $db->fetchOne("SELECT COUNT(*) as count FROM $table");
        if ($result) {
            echo "✓ Tabla $table: " . $result['count'] . " registros\n";
            $tests[] = "Tabla $table";
        } else {
            echo "✗ Error en tabla $table\n";
            $errors[] = "Tabla $table";
        }
    } catch (Exception $e) {
        echo "✗ Error en tabla $table: " . $e->getMessage() . "\n";
        $errors[] = "Tabla $table";
    }
}

// Test 3: Verificar APIs
echo "\n3. Probando APIs...\n";

// API Productos
try {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "http://localhost:8000/api/productos.php");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode == 200 && $response) {
        $data = json_decode($response, true);
        if ($data && isset($data['productos'])) {
            echo "✓ API Productos: " . count($data['productos']) . " productos\n";
            $tests[] = "API Productos";
        } else {
            echo "✗ Error en API Productos\n";
            $errors[] = "API Productos";
        }
    } else {
        echo "✗ Error en API Productos (HTTP $httpCode)\n";
        $errors[] = "API Productos";
    }
} catch (Exception $e) {
    echo "✗ Error en API Productos: " . $e->getMessage() . "\n";
    $errors[] = "API Productos";
}

// API Servicios
try {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "http://localhost:8000/api/servicios.php");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode == 200 && $response) {
        $data = json_decode($response, true);
        if ($data && isset($data['servicios'])) {
            echo "✓ API Servicios: " . count($data['servicios']) . " servicios\n";
            $tests[] = "API Servicios";
        } else {
            echo "✗ Error en API Servicios\n";
            $errors[] = "API Servicios";
        }
    } else {
        echo "✗ Error en API Servicios (HTTP $httpCode)\n";
        $errors[] = "API Servicios";
    }
} catch (Exception $e) {
    echo "✗ Error en API Servicios: " . $e->getMessage() . "\n";
    $errors[] = "API Servicios";
}

// Test 4: Probar CRM (Contactos)
echo "\n4. Probando CRM (Contactos)...\n";
try {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "http://localhost:8000/api/contactos.php");
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'tipo-consulta' => 'consulta',
        'nombre' => 'Test QA Automatizado',
        'email' => 'test@qa.com',
        'asunto' => 'Prueba QA Automatizada',
        'mensaje' => 'Este es un mensaje de prueba automatizada para verificar el funcionamiento del CRM'
    ]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode == 200 && $response) {
        $data = json_decode($response, true);
        if ($data && isset($data['success']) && $data['success']) {
            echo "✓ CRM Contactos: Contacto creado exitosamente (ID: " . $data['contacto_id'] . ")\n";
            $tests[] = "CRM Contactos";
        } else {
            echo "✗ Error en CRM Contactos\n";
            $errors[] = "CRM Contactos";
        }
    } else {
        echo "✗ Error en CRM Contactos (HTTP $httpCode)\n";
        $errors[] = "CRM Contactos";
    }
} catch (Exception $e) {
    echo "✗ Error en CRM Contactos: " . $e->getMessage() . "\n";
    $errors[] = "CRM Contactos";
}

// Test 5: Probar Cotizaciones
echo "\n5. Probando Cotizaciones...\n";
try {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "http://localhost:8000/api/cotizaciones.php");
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'tipo_cotizacion' => 'servicio',
        'servicio_id' => 1,
        'nombre_cliente' => 'Cliente QA Automatizado',
        'email_cliente' => 'cliente@qa.com',
        'mensaje' => 'Cotización de prueba automatizada',
        'detalles_proyecto' => 'Proyecto de prueba para QA automatizada'
    ]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if (($httpCode == 200 || $httpCode == 201) && $response) {
        $data = json_decode($response, true);
        if ($data && isset($data['success']) && $data['success']) {
            echo "✓ Cotizaciones: Cotización creada exitosamente (ID: " . $data['id'] . ")\n";
            $tests[] = "Cotizaciones";
        } else {
            echo "✗ Error en Cotizaciones\n";
            $errors[] = "Cotizaciones";
        }
    } else {
        echo "✗ Error en Cotizaciones (HTTP $httpCode)\n";
        $errors[] = "Cotizaciones";
    }
} catch (Exception $e) {
    echo "✗ Error en Cotizaciones: " . $e->getMessage() . "\n";
    $errors[] = "Cotizaciones";
}

// Test 6: Verificar archivos principales
echo "\n6. Verificando archivos principales...\n";
$files = [
    __DIR__ . '/../index.html',
    __DIR__ . '/../pag/contacto.html',
    __DIR__ . '/../pag/cotizacion-diseno.html',
    __DIR__ . '/../admin/login.php',
    __DIR__ . '/../admin/index.php',
    __DIR__ . '/../database/config.php',
    __DIR__ . '/../api/productos.php',
    __DIR__ . '/../api/contactos.php',
    __DIR__ . '/../api/cotizaciones.php',
    __DIR__ . '/../js/contacto.js',
    __DIR__ . '/../js/cotizacionServicios.js',
    __DIR__ . '/../css/style.css'
];

foreach ($files as $file) {
    if (file_exists($file)) {
        echo "✓ Archivo $file existe\n";
        $tests[] = "Archivo $file";
    } else {
        echo "✗ Archivo $file no existe\n";
        $errors[] = "Archivo $file";
    }
}

// Test 7: Verificar directorios
echo "\n7. Verificando directorios...\n";
$dirs = [
    __DIR__ . '/../images',
    __DIR__ . '/../css',
    __DIR__ . '/../js',
    __DIR__ . '/../admin',
    __DIR__ . '/../api',
    __DIR__ . '/../database',
    __DIR__ . '/../pag',
    __DIR__ . '/../logs',
    __DIR__ . '/../uploads'
];

foreach ($dirs as $dir) {
    if (is_dir($dir)) {
        echo "✓ Directorio $dir existe\n";
        $tests[] = "Directorio $dir";
    } else {
        echo "✗ Directorio $dir no existe\n";
        $errors[] = "Directorio $dir";
    }
}

// Resumen final
echo "\n=== RESUMEN ===\n";
echo "Tests exitosos: " . count($tests) . "\n";
echo "Errores encontrados: " . count($errors) . "\n\n";

if (count($errors) > 0) {
    echo "Errores encontrados:\n";
    foreach ($errors as $error) {
        echo "- $error\n";
    }
    echo "\n";
} else {
    echo "¡Todos los tests pasaron exitosamente!\n";
}

echo "=== FIN DEL QA TEST ===\n";
?>
