<?php
/**
 * Script de Generación de Sitemap para JC3Design
 * 
 * Este script genera un archivo sitemap.xml en el directorio raíz del proyecto.
 * Puede ser ejecutado manualmente o programado con cron.
 * 
 * Ejemplo de cron para ejecutarlo diariamente a las 4:00 AM:
 * 0 4 * * * /usr/bin/php /ruta/a/tu/proyecto/scripts/generar_sitemap.php
 */

// Forzar la visualización de errores para depuración desde CLI
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../database/config.php';

// --- CONFIGURACIÓN ---
// ¡IMPORTANTE! Asegúrate de que esta sea la URL principal de tu sitio en producción.
$baseURL = rtrim(APP_URL, '/'); 

// --- LÓGICA DEL SCRIPT ---
try {
    $db = getDB();

    // Iniciar el documento XML
    $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"></urlset>');

    // Función para añadir una URL al sitemap
    function addUrl($xml, $loc, $lastmod = null, $changefreq = 'monthly', $priority = '0.7') {
        $url = $xml->addChild('url');
        $url->addChild('loc', $loc);
        if ($lastmod) {
            $url->addChild('lastmod', date('c', strtotime($lastmod)));
        }
        $url->addChild('changefreq', $changefreq);
        $url->addChild('priority', $priority);
    }

    // 1. Páginas estáticas principales
    addUrl($xml, $baseURL . '/', date('c'), 'daily', '1.0');
    addUrl($xml, $baseURL . '/pag/productos.html', date('c'), 'daily', '0.9');
    addUrl($xml, $baseURL . '/pag/nosotros.html', date('c', filemtime(__DIR__ . '/../pag/nosotros.html')), 'monthly', '0.8');
    addUrl($xml, $baseURL . '/pag/contacto.html', date('c', filemtime(__DIR__ . '/../pag/contacto.html')), 'monthly', '0.8');

    // 2. Páginas de categorías de productos
    $categorias = $db->fetchAll("SELECT slug, updated_at FROM categorias WHERE activo = 1");
    foreach ($categorias as $categoria) {
        // Asumiendo que las categorías se muestran en la página de productos
        // Se puede mejorar si cada categoría tiene su propia página
        addUrl($xml, $baseURL . '/pag/productos.html?categoria=' . urlencode($categoria['slug']), $categoria['updated_at'], 'weekly', '0.8');
    }

    // 3. Páginas de productos individuales
    $productos = $db->fetchAll("SELECT id, updated_at FROM productos WHERE activo = 1");
    foreach ($productos as $producto) {
        addUrl($xml, $baseURL . '/pag/producto.html?id=' . $producto['id'], $producto['updated_at'], 'weekly', '0.9');
    }

    // Guardar el archivo sitemap.xml en el directorio raíz
    $sitemapPath = __DIR__ . '/../sitemap.xml';
    
    // Formatear la salida para que sea legible
    $dom = new DOMDocument('1.0');
    $dom->preserveWhiteSpace = false;
    $dom->formatOutput = true;
    $dom->loadXML($xml->asXML());
    
    if ($dom->save($sitemapPath)) {
        $message = "Sitemap generado exitosamente en: " . realpath($sitemapPath);
    } else {
        throw new Exception("No se pudo guardar el archivo sitemap.xml.");
    }

    // Mostrar mensaje si se ejecuta desde la consola
    if (php_sapi_name() === 'cli') {
        echo $message . PHP_EOL;
    }

} catch (Exception $e) {
    $errorMessage = "ERROR al generar el sitemap: " . $e->getMessage();
    error_log($errorMessage);
    if (php_sapi_name() === 'cli') {
        echo $errorMessage . PHP_EOL;
    }
    exit(1);
}
?>
