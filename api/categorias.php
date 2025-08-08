<?php
/**
 * API para manejo de categorías
 * Endpoints:
 * GET /api/categorias.php - Listar categorías
 * GET /api/categorias.php?id=X - Obtener categoría específica
 * GET /api/categorias.php?slug=X - Obtener categoría por slug
 * POST /api/categorias.php - Crear categoría (requiere autenticación)
 * PUT /api/categorias.php - Actualizar categoría (requiere autenticación)
 * DELETE /api/categorias.php?id=X - Eliminar categoría (requiere autenticación)
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
    $activo = $_GET['activo'] ?? null;
    
    // Obtener categoría específica
    if ($id) {
        $sql = "SELECT * FROM categorias WHERE id = ?";
        $categoria = $db->fetchOne($sql, [$id]);
        
        if (!$categoria) {
            jsonResponse(['error' => 'Categoría no encontrada'], 404);
        }
        
        // Obtener productos de esta categoría
        $sql_productos = "SELECT COUNT(*) as total FROM productos WHERE categoria_id = ? AND activo = 1";
        $total_productos = $db->fetchOne($sql_productos, [$id])['total'];
        $categoria['total_productos'] = $total_productos;
        
        jsonResponse($categoria);
    }
    
    // Obtener categoría por slug
    if ($slug) {
        $sql = "SELECT * FROM categorias WHERE slug = ?";
        $categoria = $db->fetchOne($sql, [$slug]);
        
        if (!$categoria) {
            jsonResponse(['error' => 'Categoría no encontrada'], 404);
        }
        
        // Obtener productos de esta categoría
        $sql_productos = "SELECT COUNT(*) as total FROM productos WHERE categoria_id = ? AND activo = 1";
        $total_productos = $db->fetchOne($sql_productos, [$categoria['id']])['total'];
        $categoria['total_productos'] = $total_productos;
        
        jsonResponse($categoria);
    }
    
    // Listar todas las categorías
    $sql = "SELECT * FROM categorias";
    $params = [];
    
    // Filtrar por estado activo
    if ($activo !== null) {
        $sql .= " WHERE activo = ?";
        $params[] = $activo ? 1 : 0;
    }
    
    $sql .= " ORDER BY orden ASC, nombre ASC";
    
    $categorias = $db->fetchAll($sql, $params);
    
    // Agregar conteo de productos a cada categoría
    foreach ($categorias as &$categoria) {
        $sql_productos = "SELECT COUNT(*) as total FROM productos WHERE categoria_id = ? AND activo = 1";
        $total_productos = $db->fetchOne($sql_productos, [$categoria['id']])['total'];
        $categoria['total_productos'] = $total_productos;
    }
    
    jsonResponse($categorias);
}

/**
 * Manejar peticiones POST (crear categoría)
 */
function handlePost($db) {
    if (!isAuthenticated() || !isAdmin()) {
        jsonResponse(['error' => 'Acceso denegado'], 403);
    }
    
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data) {
        $data = $_POST;
    }
    
    // Validar datos requeridos
    $rules = [
        'nombre' => 'required|max:100',
        'slug' => 'required|max:100'
    ];
    
    $errors = validateData($data, $rules);
    if (!empty($errors)) {
        jsonResponse(['error' => 'Datos inválidos', 'errores' => $errors], 400);
    }
    
    // Verificar que el slug no esté duplicado
    $existe = $db->fetchOne("SELECT id FROM categorias WHERE slug = ?", [$data['slug']]);
    if ($existe) {
        jsonResponse(['error' => 'El slug de la categoría ya existe'], 400);
    }
    
    // Preparar datos para inserción
    $categoria_data = [
        'nombre' => sanitizeInput($data['nombre']),
        'slug' => sanitizeInput($data['slug']),
        'descripcion' => sanitizeInput($data['descripcion'] ?? ''),
        'imagen' => sanitizeInput($data['imagen'] ?? ''),
        'activo' => (bool)($data['activo'] ?? true),
        'orden' => (int)($data['orden'] ?? 0)
    ];
    
    try {
        $categoria_id = $db->insert('categorias', $categoria_data);
        
        // Registrar log
        logActivity($_SESSION['user_id'], 'crear', 'categorias', $categoria_id, null, $categoria_data);
        
        jsonResponse([
            'mensaje' => 'Categoría creada exitosamente',
            'id' => $categoria_id
        ], 201);
        
    } catch (Exception $e) {
        jsonResponse(['error' => 'Error al crear la categoría: ' . $e->getMessage()], 500);
    }
}

/**
 * Manejar peticiones PUT (actualizar categoría)
 */
function handlePut($db) {
    if (!isAuthenticated() || !isAdmin()) {
        jsonResponse(['error' => 'Acceso denegado'], 403);
    }
    
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data) {
        parse_str(file_get_contents('php://input'), $data);
    }
    
    $id = $data['id'] ?? null;
    if (!$id) {
        jsonResponse(['error' => 'ID de la categoría requerido'], 400);
    }
    
    // Verificar que la categoría existe
    $categoria_existente = $db->fetchOne("SELECT * FROM categorias WHERE id = ?", [$id]);
    if (!$categoria_existente) {
        jsonResponse(['error' => 'Categoría no encontrada'], 404);
    }
    
    // Validar datos
    $rules = [
        'nombre' => 'required|max:100',
        'slug' => 'required|max:100'
    ];
    
    $errors = validateData($data, $rules);
    if (!empty($errors)) {
        jsonResponse(['error' => 'Datos inválidos', 'errores' => $errors], 400);
    }
    
    // Verificar que el slug no esté duplicado (excepto para esta categoría)
    $existe = $db->fetchOne("SELECT id FROM categorias WHERE slug = ? AND id != ?", [$data['slug'], $id]);
    if ($existe) {
        jsonResponse(['error' => 'El slug de la categoría ya existe'], 400);
    }
    
    // Preparar datos para actualización
    $categoria_data = [
        'nombre' => sanitizeInput($data['nombre']),
        'slug' => sanitizeInput($data['slug']),
        'descripcion' => sanitizeInput($data['descripcion'] ?? ''),
        'imagen' => sanitizeInput($data['imagen'] ?? ''),
        'activo' => (bool)($data['activo'] ?? true),
        'orden' => (int)($data['orden'] ?? 0)
    ];
    
    try {
        $rows_affected = $db->update('categorias', $categoria_data, 'id = ?', [$id]);
        
        // Registrar log
        logActivity($_SESSION['user_id'], 'actualizar', 'categorias', $id, $categoria_existente, $categoria_data);
        
        jsonResponse([
            'mensaje' => 'Categoría actualizada exitosamente',
            'filas_afectadas' => $rows_affected
        ]);
        
    } catch (Exception $e) {
        jsonResponse(['error' => 'Error al actualizar la categoría: ' . $e->getMessage()], 500);
    }
}

/**
 * Manejar peticiones DELETE (eliminar categoría)
 */
function handleDelete($db) {
    if (!isAuthenticated() || !isAdmin()) {
        jsonResponse(['error' => 'Acceso denegado'], 403);
    }
    
    $id = $_GET['id'] ?? null;
    if (!$id) {
        jsonResponse(['error' => 'ID de la categoría requerido'], 400);
    }
    
    // Verificar que la categoría existe
    $categoria = $db->fetchOne("SELECT * FROM categorias WHERE id = ?", [$id]);
    if (!$categoria) {
        jsonResponse(['error' => 'Categoría no encontrada'], 404);
    }
    
    // Verificar que no tenga productos asociados
    $productos = $db->fetchOne("SELECT COUNT(*) as total FROM productos WHERE categoria_id = ? AND activo = 1", [$id]);
    if ($productos['total'] > 0) {
        jsonResponse(['error' => 'No se puede eliminar la categoría porque tiene productos asociados'], 400);
    }
    
    try {
        // Soft delete (marcar como inactivo en lugar de eliminar)
        $rows_affected = $db->update('categorias', ['activo' => 0], 'id = ?', [$id]);
        
        // Registrar log
        logActivity($_SESSION['user_id'], 'eliminar', 'categorias', $id, $categoria, null);
        
        jsonResponse([
            'mensaje' => 'Categoría eliminada exitosamente',
            'filas_afectadas' => $rows_affected
        ]);
        
    } catch (Exception $e) {
        jsonResponse(['error' => 'Error al eliminar la categoría: ' . $e->getMessage()], 500);
    }
}
?>
