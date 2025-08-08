<?php
/**
 * API para manejo de productos
 * Endpoints:
 * GET /api/productos.php - Listar productos
 * GET /api/productos.php?id=X - Obtener producto específico
 * GET /api/productos.php?categoria=X - Filtrar por categoría
 * POST /api/productos.php - Crear producto (requiere autenticación)
 * PUT /api/productos.php - Actualizar producto (requiere autenticación)
 * DELETE /api/productos.php?id=X - Eliminar producto (requiere autenticación)
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
    $categoria = $_GET['categoria'] ?? null;
    $slug = $_GET['slug'] ?? null;
    $destacado = $_GET['destacado'] ?? null;
    $page = (int)($_GET['page'] ?? 1);
    $limit = (int)($_GET['limit'] ?? ITEMS_PER_PAGE);
    $offset = ($page - 1) * $limit;
    
    // Obtener producto específico
    if ($id) {
        $sql = "SELECT p.*, c.nombre as categoria_nombre, c.slug as categoria_slug 
                FROM productos p 
                JOIN categorias c ON p.categoria_id = c.id 
                WHERE p.id = ? AND p.activo = 1";
        $producto = $db->fetchOne($sql, [$id]);
        
        if (!$producto) {
            jsonResponse(['error' => 'Producto no encontrado'], 404);
        }
        
        // Obtener imágenes adicionales
        $sql_imagenes = "SELECT * FROM producto_imagenes WHERE producto_id = ? ORDER BY orden, principal DESC";
        $imagenes = $db->fetchAll($sql_imagenes, [$id]);
        $producto['imagenes'] = $imagenes;
        
        // Obtener especificaciones
        $sql_especs = "SELECT * FROM producto_especificaciones WHERE producto_id = ? ORDER BY orden";
        $especificaciones = $db->fetchAll($sql_especs, [$id]);
        $producto['especificaciones'] = $especificaciones;
        
        jsonResponse($producto);
    }
    
    // Construir consulta base
    $sql = "SELECT p.*, c.nombre as categoria_nombre, c.slug as categoria_slug 
            FROM productos p 
            JOIN categorias c ON p.categoria_id = c.id 
            WHERE p.activo = 1";
    $params = [];
    
    // Filtrar por categoría (ID o slug)
    if ($categoria) {
        if (is_numeric($categoria)) {
            $sql .= " AND p.categoria_id = ?";
            $params[] = $categoria;
        } else {
            $sql .= " AND c.slug = ?";
            $params[] = $categoria;
        }
    }
    
    // Filtrar por slug de categoría
    if ($slug) {
        $sql .= " AND c.slug = ?";
        $params[] = $slug;
    }
    
    // Filtrar por destacado
    if ($destacado !== null) {
        $sql .= " AND p.destacado = ?";
        $params[] = $destacado ? 1 : 0;
    }
    
    // Ordenar y paginar
    $sql .= " ORDER BY p.destacado DESC, p.orden ASC, p.nombre ASC";
    $sql .= " LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;
    
    $productos = $db->fetchAll($sql, $params);
    
    // Obtener total de registros para paginación
    $count_sql = "SELECT COUNT(*) as total FROM productos p 
                   JOIN categorias c ON p.categoria_id = c.id 
                   WHERE p.activo = 1";
    $count_params = [];
    
    if ($categoria) {
        if (is_numeric($categoria)) {
            $count_sql .= " AND p.categoria_id = ?";
            $count_params[] = $categoria;
        } else {
            $count_sql .= " AND c.slug = ?";
            $count_params[] = $categoria;
        }
    }
    
    if ($slug) {
        $count_sql .= " AND c.slug = ?";
        $count_params[] = $slug;
    }
    
    if ($destacado !== null) {
        $count_sql .= " AND p.destacado = ?";
        $count_params[] = $destacado ? 1 : 0;
    }
    
    $total = $db->fetchOne($count_sql, $count_params)['total'];
    
    jsonResponse([
        'productos' => $productos,
        'paginacion' => [
            'pagina_actual' => $page,
            'total_paginas' => ceil($total / $limit),
            'total_registros' => $total,
            'registros_por_pagina' => $limit
        ]
    ]);
}

/**
 * Manejar peticiones POST (crear producto)
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
        'nombre' => 'required|max:255',
        'categoria_id' => 'required|numeric',
        'codigo' => 'required|max:50'
    ];
    
    $errors = validateData($data, $rules);
    if (!empty($errors)) {
        jsonResponse(['error' => 'Datos inválidos', 'errores' => $errors], 400);
    }
    
    // Verificar que la categoría existe
    $categoria = $db->fetchOne("SELECT id FROM categorias WHERE id = ? AND activo = 1", [$data['categoria_id']]);
    if (!$categoria) {
        jsonResponse(['error' => 'Categoría no encontrada'], 400);
    }
    
    // Verificar que el código no esté duplicado
    $existe = $db->fetchOne("SELECT id FROM productos WHERE codigo = ?", [$data['codigo']]);
    if ($existe) {
        jsonResponse(['error' => 'El código del producto ya existe'], 400);
    }
    
    // Preparar datos para inserción
    $producto_data = [
        'categoria_id' => $data['categoria_id'],
        'codigo' => $data['codigo'],
        'nombre' => sanitizeInput($data['nombre']),
        'descripcion' => sanitizeInput($data['descripcion'] ?? ''),
        'precio' => $data['precio'] ?? null,
        'precio_mostrar' => sanitizeInput($data['precio_mostrar'] ?? 'Consultar precio vía contacto'),
        'dimensiones' => sanitizeInput($data['dimensiones'] ?? ''),
        'material' => sanitizeInput($data['material'] ?? ''),
        'peso' => sanitizeInput($data['peso'] ?? ''),
        'uso' => sanitizeInput($data['uso'] ?? ''),
        'otras_caracteristicas' => sanitizeInput($data['otras_caracteristicas'] ?? ''),
        'observaciones' => sanitizeInput($data['observaciones'] ?? ''),
        'garantia' => sanitizeInput($data['garantia'] ?? ''),
        'imagen' => sanitizeInput($data['imagen'] ?? ''),
        'imagen_alt' => sanitizeInput($data['imagen_alt'] ?? ''),
        'stock' => (int)($data['stock'] ?? 0),
        'destacado' => (bool)($data['destacado'] ?? false),
        'orden' => (int)($data['orden'] ?? 0),
        'activo' => true
    ];
    
    try {
        $producto_id = $db->insert('productos', $producto_data);
        
        // Registrar log
        logActivity($_SESSION['user_id'], 'crear', 'productos', $producto_id, null, $producto_data);
        
        jsonResponse([
            'mensaje' => 'Producto creado exitosamente',
            'id' => $producto_id
        ], 201);
        
    } catch (Exception $e) {
        jsonResponse(['error' => 'Error al crear el producto: ' . $e->getMessage()], 500);
    }
}

/**
 * Manejar peticiones PUT (actualizar producto)
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
        jsonResponse(['error' => 'ID del producto requerido'], 400);
    }
    
    // Verificar que el producto existe
    $producto_existente = $db->fetchOne("SELECT * FROM productos WHERE id = ?", [$id]);
    if (!$producto_existente) {
        jsonResponse(['error' => 'Producto no encontrado'], 404);
    }
    
    // Validar datos
    $rules = [
        'nombre' => 'required|max:255',
        'categoria_id' => 'required|numeric'
    ];
    
    $errors = validateData($data, $rules);
    if (!empty($errors)) {
        jsonResponse(['error' => 'Datos inválidos', 'errores' => $errors], 400);
    }
    
    // Verificar que la categoría existe
    $categoria = $db->fetchOne("SELECT id FROM categorias WHERE id = ? AND activo = 1", [$data['categoria_id']]);
    if (!$categoria) {
        jsonResponse(['error' => 'Categoría no encontrada'], 400);
    }
    
    // Verificar que el código no esté duplicado (excepto para este producto)
    if (isset($data['codigo'])) {
        $existe = $db->fetchOne("SELECT id FROM productos WHERE codigo = ? AND id != ?", [$data['codigo'], $id]);
        if ($existe) {
            jsonResponse(['error' => 'El código del producto ya existe'], 400);
        }
    }
    
    // Preparar datos para actualización
    $producto_data = [
        'categoria_id' => $data['categoria_id'],
        'nombre' => sanitizeInput($data['nombre']),
        'descripcion' => sanitizeInput($data['descripcion'] ?? ''),
        'precio' => $data['precio'] ?? null,
        'precio_mostrar' => sanitizeInput($data['precio_mostrar'] ?? 'Consultar precio vía contacto'),
        'dimensiones' => sanitizeInput($data['dimensiones'] ?? ''),
        'material' => sanitizeInput($data['material'] ?? ''),
        'peso' => sanitizeInput($data['peso'] ?? ''),
        'uso' => sanitizeInput($data['uso'] ?? ''),
        'otras_caracteristicas' => sanitizeInput($data['otras_caracteristicas'] ?? ''),
        'observaciones' => sanitizeInput($data['observaciones'] ?? ''),
        'garantia' => sanitizeInput($data['garantia'] ?? ''),
        'imagen' => sanitizeInput($data['imagen'] ?? ''),
        'imagen_alt' => sanitizeInput($data['imagen_alt'] ?? ''),
        'stock' => (int)($data['stock'] ?? 0),
        'destacado' => (bool)($data['destacado'] ?? false),
        'orden' => (int)($data['orden'] ?? 0)
    ];
    
    // Agregar código si se proporciona
    if (isset($data['codigo'])) {
        $producto_data['codigo'] = sanitizeInput($data['codigo']);
    }
    
    try {
        $rows_affected = $db->update('productos', $producto_data, 'id = ?', [$id]);
        
        // Registrar log
        logActivity($_SESSION['user_id'], 'actualizar', 'productos', $id, $producto_existente, $producto_data);
        
        jsonResponse([
            'mensaje' => 'Producto actualizado exitosamente',
            'filas_afectadas' => $rows_affected
        ]);
        
    } catch (Exception $e) {
        jsonResponse(['error' => 'Error al actualizar el producto: ' . $e->getMessage()], 500);
    }
}

/**
 * Manejar peticiones DELETE (eliminar producto)
 */
function handleDelete($db) {
    if (!isAuthenticated() || !isAdmin()) {
        jsonResponse(['error' => 'Acceso denegado'], 403);
    }
    
    $id = $_GET['id'] ?? null;
    if (!$id) {
        jsonResponse(['error' => 'ID del producto requerido'], 400);
    }
    
    // Verificar que el producto existe
    $producto = $db->fetchOne("SELECT * FROM productos WHERE id = ?", [$id]);
    if (!$producto) {
        jsonResponse(['error' => 'Producto no encontrado'], 404);
    }
    
    try {
        // Soft delete (marcar como inactivo en lugar de eliminar)
        $rows_affected = $db->update('productos', ['activo' => 0], 'id = ?', [$id]);
        
        // Registrar log
        logActivity($_SESSION['user_id'], 'eliminar', 'productos', $id, $producto, null);
        
        jsonResponse([
            'mensaje' => 'Producto eliminado exitosamente',
            'filas_afectadas' => $rows_affected
        ]);
        
    } catch (Exception $e) {
        jsonResponse(['error' => 'Error al eliminar el producto: ' . $e->getMessage()], 500);
    }
}
?>
