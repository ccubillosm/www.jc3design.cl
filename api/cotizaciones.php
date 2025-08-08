<?php
/**
 * API para manejo de cotizaciones
 * Endpoints:
 * GET /api/cotizaciones.php - Listar cotizaciones
 * GET /api/cotizaciones.php?id=X - Obtener cotización específica
 * POST /api/cotizaciones.php - Crear cotización
 * PUT /api/cotizaciones.php - Actualizar cotización (requiere autenticación)
 * DELETE /api/cotizaciones.php?id=X - Eliminar cotización (requiere autenticación)
 */

// Headers CORS para permitir peticiones desde el frontend
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json; charset=utf-8');

// Manejar preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../database/config.php';

try {
    $db = getDB();
    $method = $_SERVER['REQUEST_METHOD'];
    
    switch ($method) {
        case 'GET':
            handleGet($db);
            break;
        case 'POST':
            handlePost($db);
            break;
        case 'PUT':
            handlePut($db);
            break;
        case 'DELETE':
            handleDelete($db);
            break;
        default:
            jsonResponse(['error' => 'Método no permitido'], 405);
    }
} catch (Exception $e) {
    jsonResponse(['error' => $e->getMessage()], 500);
}

/**
 * Manejar peticiones GET
 */
function handleGet($db) {
    $id = $_GET['id'] ?? null;
    $estado = $_GET['estado'] ?? null;
    $tipo = $_GET['tipo'] ?? null;
    $page = (int)($_GET['page'] ?? 1);
    $limit = (int)($_GET['limit'] ?? 20);
    $offset = ($page - 1) * $limit;
    
    // Obtener cotización específica
    if ($id) {
        $sql = "SELECT c.*, 
                       p.nombre as producto_nombre, p.codigo as producto_codigo,
                       s.nombre as servicio_nombre, s.slug as servicio_slug
                FROM cotizaciones c 
                LEFT JOIN productos p ON c.producto_id = p.id 
                LEFT JOIN servicios s ON c.servicio_id = s.id
                WHERE c.id = ?";
        $cotizacion = $db->fetchOne($sql, [$id]);
        
        if (!$cotizacion) {
            jsonResponse(['error' => 'Cotización no encontrada'], 404);
        }
        
        jsonResponse($cotizacion);
    }
    
    // Construir consulta base
    $sql = "SELECT c.*, 
                   p.nombre as producto_nombre, p.codigo as producto_codigo,
                   s.nombre as servicio_nombre, s.slug as servicio_slug
            FROM cotizaciones c 
            LEFT JOIN productos p ON c.producto_id = p.id 
            LEFT JOIN servicios s ON c.servicio_id = s.id";
    $params = [];
    $conditions = [];
    
    // Filtrar por estado
    if ($estado) {
        $conditions[] = "c.estado = ?";
        $params[] = $estado;
    }
    
    // Filtrar por tipo
    if ($tipo) {
        $conditions[] = "c.tipo_cotizacion = ?";
        $params[] = $tipo;
    }
    
    // Agregar condiciones
    if (!empty($conditions)) {
        $sql .= " WHERE " . implode(" AND ", $conditions);
    }
    
    // Agregar orden y límites
    $sql .= " ORDER BY c.created_at DESC LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;
    
    $cotizaciones = $db->fetchAll($sql, $params);
    
    // Obtener total para paginación
    $countSql = "SELECT COUNT(*) as total FROM cotizaciones";
    if (!empty($conditions)) {
        $countSql .= " WHERE " . implode(" AND ", $conditions);
        $total = $db->fetchOne($countSql, array_slice($params, 0, -2))['total'];
    } else {
        $total = $db->fetchOne($countSql)['total'];
    }
    
    jsonResponse([
        'cotizaciones' => $cotizaciones,
        'paginacion' => [
            'pagina_actual' => $page,
            'total_paginas' => ceil($total / $limit),
            'total_registros' => $total,
            'registros_por_pagina' => $limit
        ]
    ]);
}

/**
 * Manejar peticiones POST (crear cotización)
 */
function handlePost($db) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Validar datos requeridos
    $required = ['nombre_cliente', 'email_cliente', 'tipo_cotizacion'];
    $errors = validateData($data, $required);
    
    // Validar que tenga producto_id o servicio_id según el tipo
    if ($data['tipo_cotizacion'] === 'producto' && empty($data['producto_id'])) {
        $errors[] = 'producto_id es requerido para cotizaciones de productos';
    }
    if ($data['tipo_cotizacion'] === 'servicio' && empty($data['servicio_id'])) {
        $errors[] = 'servicio_id es requerido para cotizaciones de servicios';
    }
    
    if (!empty($errors)) {
        jsonResponse(['error' => 'Datos inválidos', 'errors' => $errors], 400);
    }
    
    // Sanitizar datos
    $cotizacion = [
        'tipo_cotizacion' => sanitizeInput($data['tipo_cotizacion']),
        'nombre_cliente' => sanitizeInput($data['nombre_cliente']),
        'email_cliente' => sanitizeInput($data['email_cliente']),
        'telefono_cliente' => sanitizeInput($data['telefono_cliente'] ?? ''),
        'mensaje' => sanitizeInput($data['mensaje'] ?? ''),
        'estado' => 'solicitada'
    ];
    
    // Agregar ID específico según el tipo
    if ($data['tipo_cotizacion'] === 'producto') {
        $cotizacion['producto_id'] = (int)$data['producto_id'];
        $cotizacion['servicio_id'] = null;
    } else {
        $cotizacion['servicio_id'] = (int)$data['servicio_id'];
        $cotizacion['producto_id'] = null;
        
        // Campos adicionales para servicios
        $cotizacion['detalles_proyecto'] = sanitizeInput($data['detalles_proyecto'] ?? '');
        $cotizacion['presupuesto_estimado'] = sanitizeInput($data['presupuesto_estimado'] ?? '');
        $cotizacion['fecha_requerida'] = !empty($data['fecha_requerida']) ? $data['fecha_requerida'] : null;
        $cotizacion['datos_especificos'] = isset($data['datos_especificos']) ? $data['datos_especificos'] : null;
    }
    
    try {
        $id = $db->insert('cotizaciones', $cotizacion);
        
        // Registrar actividad
        logActivity(1, 'create', 'cotizaciones', $id, null, $cotizacion);
        
        jsonResponse([
            'success' => true,
            'message' => 'Cotización creada exitosamente',
            'id' => $id
        ], 201);
        
    } catch (Exception $e) {
        jsonResponse(['error' => 'Error al crear cotización: ' . $e->getMessage()], 500);
    }
}

/**
 * Manejar peticiones PUT (actualizar cotización)
 */
function handlePut($db) {
    // Verificar autenticación para modificaciones
    if (!isAuthenticated() || !isAdmin()) {
        jsonResponse(['error' => 'No autorizado'], 401);
    }
    
    $data = json_decode(file_get_contents('php://input'), true);
    $id = $data['id'] ?? null;
    
    if (!$id) {
        jsonResponse(['error' => 'ID de cotización requerido'], 400);
    }
    
    // Obtener cotización actual
    $cotizacionActual = $db->fetchOne("SELECT * FROM cotizaciones WHERE id = ?", [$id]);
    if (!$cotizacionActual) {
        jsonResponse(['error' => 'Cotización no encontrada'], 404);
    }
    
    // Preparar datos para actualización
    $updateData = [];
    $allowedFields = ['estado', 'precio_cotizado', 'notas_admin'];
    
    foreach ($allowedFields as $field) {
        if (isset($data[$field])) {
            $updateData[$field] = sanitizeInput($data[$field]);
        }
    }
    
    // Actualizar fechas según el estado
    if (isset($data['estado'])) {
        switch ($data['estado']) {
            case 'enviada':
                $updateData['fecha_envio'] = date('Y-m-d H:i:s');
                break;
            case 'vendida':
                $updateData['fecha_venta'] = date('Y-m-d H:i:s');
                break;
        }
    }
    
    if (empty($updateData)) {
        jsonResponse(['error' => 'No hay datos para actualizar'], 400);
    }
    
    try {
        $db->update('cotizaciones', $updateData, 'id = ?', [$id]);
        
        // Registrar actividad
        logActivity($_SESSION['user_id'], 'update', 'cotizaciones', $id, $cotizacionActual, $updateData);
        
        jsonResponse([
            'success' => true,
            'message' => 'Cotización actualizada exitosamente'
        ]);
        
    } catch (Exception $e) {
        jsonResponse(['error' => 'Error al actualizar cotización: ' . $e->getMessage()], 500);
    }
}

/**
 * Manejar peticiones DELETE
 */
function handleDelete($db) {
    // Verificar autenticación
    if (!isAuthenticated() || !isAdmin()) {
        jsonResponse(['error' => 'No autorizado'], 401);
    }
    
    $id = $_GET['id'] ?? null;
    
    if (!$id) {
        jsonResponse(['error' => 'ID de cotización requerido'], 400);
    }
    
    try {
        $db->delete('cotizaciones', 'id = ?', [$id]);
        
        // Registrar actividad
        logActivity($_SESSION['user_id'], 'delete', 'cotizaciones', $id);
        
        jsonResponse([
            'success' => true,
            'message' => 'Cotización eliminada exitosamente'
        ]);
        
    } catch (Exception $e) {
        jsonResponse(['error' => 'Error al eliminar cotización: ' . $e->getMessage()], 500);
    }
}
?>
