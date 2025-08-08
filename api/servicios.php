<?php
/**
 * API para manejo de servicios
 * Endpoints:
 * GET /api/servicios.php - Listar servicios
 * GET /api/servicios.php?id=X - Obtener servicio específico
 * GET /api/servicios.php?slug=X - Obtener servicio por slug
 * POST /api/servicios.php - Crear servicio (requiere autenticación)
 * PUT /api/servicios.php - Actualizar servicio (requiere autenticación)
 * DELETE /api/servicios.php?id=X - Eliminar servicio (requiere autenticación)
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
    $slug = $_GET['slug'] ?? null;
    $destacado = $_GET['destacado'] ?? null;
    $activo = $_GET['activo'] ?? '1';
    $page = (int)($_GET['page'] ?? 1);
    $limit = (int)($_GET['limit'] ?? 20);
    $offset = ($page - 1) * $limit;
    
    // Obtener servicio específico por ID
    if ($id) {
        $sql = "SELECT * FROM servicios WHERE id = ? AND activo = 1";
        $servicio = $db->fetchOne($sql, [$id]);
        
        if (!$servicio) {
            jsonResponse(['error' => 'Servicio no encontrado'], 404);
        }
        
        jsonResponse($servicio);
    }
    
    // Obtener servicio específico por slug
    if ($slug) {
        $sql = "SELECT * FROM servicios WHERE slug = ? AND activo = 1";
        $servicio = $db->fetchOne($sql, [$slug]);
        
        if (!$servicio) {
            jsonResponse(['error' => 'Servicio no encontrado'], 404);
        }
        
        jsonResponse($servicio);
    }
    
    // Construir consulta base
    $sql = "SELECT * FROM servicios";
    $params = [];
    $conditions = [];
    
    // Filtrar por activo
    if ($activo !== null) {
        $conditions[] = "activo = ?";
        $params[] = (int)$activo;
    }
    
    // Filtrar por destacado
    if ($destacado !== null) {
        $conditions[] = "destacado = ?";
        $params[] = (int)$destacado;
    }
    
    // Agregar condiciones
    if (!empty($conditions)) {
        $sql .= " WHERE " . implode(" AND ", $conditions);
    }
    
    // Agregar orden y límites
    $sql .= " ORDER BY orden ASC, nombre ASC LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;
    
    $servicios = $db->fetchAll($sql, $params);
    
    // Obtener total para paginación
    $countSql = "SELECT COUNT(*) as total FROM servicios";
    if (!empty($conditions)) {
        $countSql .= " WHERE " . implode(" AND ", $conditions);
        $total = $db->fetchOne($countSql, array_slice($params, 0, -2))['total'];
    } else {
        $total = $db->fetchOne($countSql)['total'];
    }
    
    jsonResponse([
        'servicios' => $servicios,
        'paginacion' => [
            'pagina_actual' => $page,
            'total_paginas' => ceil($total / $limit),
            'total_registros' => $total,
            'registros_por_pagina' => $limit
        ]
    ]);
}

/**
 * Manejar peticiones POST (crear servicio)
 */
function handlePost($db) {
    // Verificar autenticación
    if (!isAuthenticated() || !isAdmin()) {
        jsonResponse(['error' => 'No autorizado'], 401);
    }
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Validar datos requeridos
    $required = ['nombre', 'slug', 'descripcion'];
    $errors = validateData($data, $required);
    
    if (!empty($errors)) {
        jsonResponse(['error' => 'Datos inválidos', 'errors' => $errors], 400);
    }
    
    // Sanitizar datos
    $servicio = [
        'nombre' => sanitizeInput($data['nombre']),
        'slug' => sanitizeInput($data['slug']),
        'descripcion' => sanitizeInput($data['descripcion']),
        'descripcion_corta' => sanitizeInput($data['descripcion_corta'] ?? ''),
        'precio_base' => $data['precio_base'] ?? null,
        'precio_mostrar' => sanitizeInput($data['precio_mostrar'] ?? ''),
        'tiempo_estimado' => sanitizeInput($data['tiempo_estimado'] ?? ''),
        'incluye' => sanitizeInput($data['incluye'] ?? ''),
        'no_incluye' => sanitizeInput($data['no_incluye'] ?? ''),
        'imagen' => sanitizeInput($data['imagen'] ?? ''),
        'activo' => isset($data['activo']) ? (int)$data['activo'] : 1,
        'destacado' => isset($data['destacado']) ? (int)$data['destacado'] : 0,
        'orden' => (int)($data['orden'] ?? 0)
    ];
    
    try {
        $id = $db->insert('servicios', $servicio);
        
        // Registrar actividad
        logActivity($_SESSION['user_id'], 'create', 'servicios', $id, null, $servicio);
        
        jsonResponse([
            'success' => true,
            'message' => 'Servicio creado exitosamente',
            'id' => $id
        ], 201);
        
    } catch (Exception $e) {
        jsonResponse(['error' => 'Error al crear servicio: ' . $e->getMessage()], 500);
    }
}

/**
 * Manejar peticiones PUT (actualizar servicio)
 */
function handlePut($db) {
    // Verificar autenticación
    if (!isAuthenticated() || !isAdmin()) {
        jsonResponse(['error' => 'No autorizado'], 401);
    }
    
    $data = json_decode(file_get_contents('php://input'), true);
    $id = $data['id'] ?? null;
    
    if (!$id) {
        jsonResponse(['error' => 'ID de servicio requerido'], 400);
    }
    
    // Obtener servicio actual
    $servicioActual = $db->fetchOne("SELECT * FROM servicios WHERE id = ?", [$id]);
    if (!$servicioActual) {
        jsonResponse(['error' => 'Servicio no encontrado'], 404);
    }
    
    // Preparar datos para actualización
    $updateData = [];
    $allowedFields = ['nombre', 'slug', 'descripcion', 'descripcion_corta', 'precio_base', 'precio_mostrar', 'tiempo_estimado', 'incluye', 'no_incluye', 'imagen', 'activo', 'destacado', 'orden'];
    
    foreach ($allowedFields as $field) {
        if (isset($data[$field])) {
            if (in_array($field, ['activo', 'destacado', 'orden', 'precio_base'])) {
                $updateData[$field] = (int)$data[$field];
            } else {
                $updateData[$field] = sanitizeInput($data[$field]);
            }
        }
    }
    
    if (empty($updateData)) {
        jsonResponse(['error' => 'No hay datos para actualizar'], 400);
    }
    
    try {
        $db->update('servicios', $updateData, 'id = ?', [$id]);
        
        // Registrar actividad
        logActivity($_SESSION['user_id'], 'update', 'servicios', $id, $servicioActual, $updateData);
        
        jsonResponse([
            'success' => true,
            'message' => 'Servicio actualizado exitosamente'
        ]);
        
    } catch (Exception $e) {
        jsonResponse(['error' => 'Error al actualizar servicio: ' . $e->getMessage()], 500);
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
        jsonResponse(['error' => 'ID de servicio requerido'], 400);
    }
    
    // Verificar si hay cotizaciones asociadas
    $cotizaciones = $db->fetchOne("SELECT COUNT(*) as total FROM cotizaciones WHERE servicio_id = ?", [$id])['total'];
    if ($cotizaciones > 0) {
        jsonResponse(['error' => 'No se puede eliminar un servicio que tiene cotizaciones asociadas'], 400);
    }
    
    try {
        // Soft delete - marcar como inactivo
        $db->update('servicios', ['activo' => 0], 'id = ?', [$id]);
        
        // Registrar actividad
        logActivity($_SESSION['user_id'], 'delete', 'servicios', $id);
        
        jsonResponse([
            'success' => true,
            'message' => 'Servicio eliminado exitosamente'
        ]);
        
    } catch (Exception $e) {
        jsonResponse(['error' => 'Error al eliminar servicio: ' . $e->getMessage()], 500);
    }
}
?>
