<?php
// Deshabilitar salida de errores para CSV limpio
error_reporting(0);
ini_set('display_errors', 0);

session_start();
require_once '../database/config.php';

// Verificar autenticación y permisos de administrador
if (!isAuthenticated() || !isAdmin()) {
    header('Location: login.php');
    exit;
}

// Obtener parámetros de fecha
$fecha_inicio = $_GET['fecha_inicio'] ?? date('Y-m-01'); // Primer día del mes actual
$fecha_fin = $_GET['fecha_fin'] ?? date('Y-m-d'); // Hoy

try {
    $db = getDB();
    
    // Consulta para obtener todas las cotizaciones en el rango de fechas
    $query = "
        SELECT 
            c.id,
            c.nombre_cliente,
            c.email_cliente,
            c.telefono_cliente,
            COALESCE(c.mensaje, c.detalles_proyecto, 'Sin descripcion') as descripcion,
            c.precio_cotizado,
            c.estado,
            c.fecha_solicitud,
            c.updated_at,
            CASE 
                WHEN c.tipo_cotizacion = 'producto' THEN COALESCE(p.nombre, 'Producto')
                WHEN c.tipo_cotizacion = 'servicio' THEN COALESCE(s.nombre, 'Servicio')
                ELSE 'General'
            END as tipo_item,
            COALESCE(cat.nombre, 'Sin categoria') as categoria
        FROM cotizaciones c
        LEFT JOIN productos p ON c.producto_id = p.id
        LEFT JOIN servicios s ON c.servicio_id = s.id
        LEFT JOIN categorias cat ON COALESCE(p.categoria_id, 0) = cat.id
        WHERE DATE(c.fecha_solicitud) BETWEEN ? AND ?
        ORDER BY c.fecha_solicitud DESC
    ";
    
    $cotizaciones = $db->fetchAll($query, [$fecha_inicio, $fecha_fin]);
    
    // Calcular estadísticas
    $total_cotizaciones = count($cotizaciones);
    $total_valor = 0;
    $por_estado = [];
    
    foreach ($cotizaciones as $cot) {
        $estado = $cot['estado'];
        if (!isset($por_estado[$estado])) {
            $por_estado[$estado] = ['count' => 0, 'valor' => 0];
        }
        $por_estado[$estado]['count']++;
        if ($cot['precio_cotizado'] && $cot['precio_cotizado'] > 0) {
            $total_valor += $cot['precio_cotizado'];
            $por_estado[$estado]['valor'] += $cot['precio_cotizado'];
        }
    }
    
    // Configurar headers para descarga CSV
    $filename = "reporte_cotizaciones_" . date('Y-m-d_H-i-s') . ".csv";
    
    // Limpiar cualquier output anterior
    if (ob_get_level()) {
        ob_end_clean();
    }
    
    // Headers para descarga
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
    header('Pragma: no-cache');
    
    // Crear archivo CSV
    $output = fopen('php://output', 'w');
    
    // BOM para UTF-8 (para que Excel reconozca caracteres especiales)
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Encabezados del CSV
    fputcsv($output, [
        'ID Cotizacion',
        'Cliente',
        'Email',
        'Telefono',
        'Tipo de Item',
        'Categoria',
        'Descripcion',
        'Precio Cotizado',
        'Estado',
        'Fecha Solicitud',
        'Ultima Actualizacion'
    ], ';', '"', '\\');
    
    // Datos de las cotizaciones
    foreach ($cotizaciones as $cot) {
        // Limpiar descripción de HTML y caracteres especiales
        $descripcion_limpia = strip_tags($cot['descripcion']);
        $descripcion_limpia = str_replace(["\r", "\n", "\t"], ' ', $descripcion_limpia);
        $descripcion_limpia = preg_replace('/\s+/', ' ', $descripcion_limpia);
        
        // Formatear precio
        $precio_formateado = '';
        if ($cot['precio_cotizado'] && $cot['precio_cotizado'] > 0) {
            $precio_formateado = number_format($cot['precio_cotizado'], 0, ',', '.');
        } else {
            $precio_formateado = 'Sin precio';
        }
        
        // Formatear estado
        $estado_formateado = '';
        switch ($cot['estado']) {
            case 'vendida':
                $estado_formateado = 'Vendida';
                break;
            case 'enviada':
                $estado_formateado = 'Enviada';
                break;
            case 'solicitada':
                $estado_formateado = 'Solicitada';
                break;
            default:
                $estado_formateado = ucfirst($cot['estado']);
        }
        
        // Formatear fechas
        $fecha_solicitud = '';
        if ($cot['fecha_solicitud']) {
            $fecha_solicitud = date('d/m/Y H:i', strtotime($cot['fecha_solicitud']));
        }
        
        $fecha_actualizacion = '';
        if ($cot['updated_at']) {
            $fecha_actualizacion = date('d/m/Y H:i', strtotime($cot['updated_at']));
        } else {
            $fecha_actualizacion = 'Sin actualizacion';
        }
        
        fputcsv($output, [
            $cot['id'],
            $cot['nombre_cliente'],
            $cot['email_cliente'],
            $cot['telefono_cliente'] ?: 'Sin telefono',
            $cot['tipo_item'],
            $cot['categoria'],
            $descripcion_limpia,
            $precio_formateado,
            $estado_formateado,
            $fecha_solicitud,
            $fecha_actualizacion
        ], ';', '"', '\\');
    }
    
    // Agregar línea en blanco
    fputcsv($output, [''], ';', '"', '\\');
    
    // Agregar resumen
    fputcsv($output, ['RESUMEN DEL REPORTE'], ';', '"', '\\');
    fputcsv($output, ['Periodo', $fecha_inicio . ' al ' . $fecha_fin], ';', '"', '\\');
    fputcsv($output, ['Total Cotizaciones', $total_cotizaciones], ';', '"', '\\');
    fputcsv($output, ['Valor Total', '$' . number_format($total_valor, 0, ',', '.')], ';', '"', '\\');
    fputcsv($output, [''], ';', '"', '\\');
    
    // Desglose por estado
    fputcsv($output, ['DESGLOSE POR ESTADO'], ';', '"', '\\');
    foreach ($por_estado as $estado => $datos) {
        $estado_nombre = ucfirst($estado);
        fputcsv($output, [
            $estado_nombre,
            $datos['count'] . ' cotizaciones',
            '$' . number_format($datos['valor'], 0, ',', '.')
        ], ';', '"', '\\');
    }
    
    fclose($output);
    exit;
    
} catch (Exception $e) {
    // Si hay error, redirigir de vuelta al dashboard con mensaje
    error_log("Error generando reporte: " . $e->getMessage());
    header('Location: dashboard.php?error=reporte');
    exit;
}
?>
