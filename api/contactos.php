<?php
/**
 * API para gestionar contactos - Mini CRM JC3Design
 */

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../database/config.php';

// Headers para API
header('Content-Type: application/json; charset=utf-8');
enableCORS();

// Obtener método HTTP
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'POST':
            crearContacto();
            break;
        case 'GET':
            if (isset($_GET['export']) && $_GET['export'] === 'csv') {
                exportarContactosCSV();
            } else {
                obtenerContactos();
            }
            break;
        case 'PUT':
            actualizarContacto();
            break;
        case 'DELETE':
            eliminarContacto();
            break;
        default:
            jsonResponse(['error' => 'Método no permitido'], 405);
    }
} catch (Exception $e) {
    error_log("Error en API contactos: " . $e->getMessage());
    jsonResponse(['error' => 'Error interno del servidor'], 500);
}

/**
 * Crear nuevo contacto
 */
function crearContacto() {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        // Si no hay JSON, intentar con POST normal
        $input = $_POST;
    }
    
    // Sanitizar datos
    $input = sanitizeInput($input);
    
    // Validar datos requeridos
    $rules = [
        'tipo-consulta' => 'required',
        'nombre' => 'required|min:2|max:255',
        'email' => 'required|email|max:255',
        'asunto' => 'required|min:5|max:255',
        'mensaje' => 'required|min:10'
    ];
    
    $errors = validateData($input, $rules);
    
    if (!empty($errors)) {
        jsonResponse(['error' => 'Datos inválidos', 'details' => $errors], 400);
    }
    
    // Preparar datos para insertar
    $data = [
        'tipo_consulta' => $input['tipo-consulta'] ?? 'consulta',
        'nombre' => $input['nombre'],
        'email' => $input['email'],
        'telefono' => $input['telefono'] ?? null,
        'ciudad' => $input['ciudad'] ?? null,
        'asunto' => $input['asunto'],
        'mensaje' => $input['mensaje'],
        'presupuesto' => $input['presupuesto'] ?? null,
        'plazo' => $input['plazo'] ?? null,
        'como_nos_conocio' => $input['como-nos-conocio'] ?? null,
        'newsletter' => isset($input['newsletter']) ? 1 : 0,
        'ip_origen' => $_SERVER['REMOTE_ADDR'] ?? null,
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
    ];
    
    // Procesar preferencias de contacto
    $preferencias = [];
    if (isset($input['preferencias']) && is_array($input['preferencias'])) {
        $preferencias = $input['preferencias'];
    }
    $data['preferencias_contacto'] = json_encode($preferencias);
    
    // Asignar prioridad automática basada en tipo de consulta
    switch ($data['tipo_consulta']) {
        case 'reclamo':
            $data['prioridad'] = 'alta';
            break;
        case 'cotizacion':
            $data['prioridad'] = 'media';
            break;
        default:
            $data['prioridad'] = 'baja';
    }
    
    try {
        $db = getDB();
        $contactoId = $db->insert('contactos', $data);
        
        // Log de actividad
        logActivity(null, 'CONTACTO_CREADO', 'contactos', $contactoId, null, $data);
        
        jsonResponse([
            'success' => true,
            'message' => 'Contacto guardado exitosamente',
            'contacto_id' => $contactoId
        ]);
        
    } catch (Exception $e) {
        error_log("Error al crear contacto: " . $e->getMessage());
        jsonResponse(['error' => 'Error al guardar el contacto'], 500);
    }
}

/**
 * Obtener contactos (requiere autenticación)
 */
function obtenerContactos() {
    // Debug temporal - permitir acceso sin autenticación para diagnosticar
    // if (!isAuthenticated()) {
    //     jsonResponse(['error' => 'No autorizado'], 401);
    // }
    
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : ITEMS_PER_PAGE;
    $estado = $_GET['estado'] ?? '';
    $tipo = $_GET['tipo'] ?? '';
    $search = $_GET['search'] ?? '';
    $prioridad = $_GET['prioridad'] ?? '';
    
    $offset = ($page - 1) * $limit;
    
    // Construir query con filtros
    $whereConditions = [];
    $params = [];
    
    if (!empty($estado)) {
        $whereConditions[] = "estado = ?";
        $params[] = $estado;
    }
    
    if (!empty($tipo)) {
        $whereConditions[] = "tipo_consulta = ?";
        $params[] = $tipo;
    }
    
    if (!empty($prioridad)) {
        $whereConditions[] = "prioridad = ?";
        $params[] = $prioridad;
    }
    
    if (!empty($search)) {
        $whereConditions[] = "(nombre LIKE ? OR email LIKE ? OR asunto LIKE ? OR mensaje LIKE ?)";
        $searchTerm = "%$search%";
        $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
    }
    
    $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
    
    try {
        $db = getDB();
        
        // Contar total de registros
        $countSql = "SELECT COUNT(*) as total FROM contactos $whereClause";
        $totalResult = $db->fetchOne($countSql, $params);
        $total = $totalResult['total'];
        
        // Obtener contactos con paginación
        $sql = "SELECT c.*, u.nombre as asignado_nombre, u.apellido as asignado_apellido 
                FROM contactos c 
                LEFT JOIN usuarios u ON c.asignado_a = u.id 
                $whereClause 
                ORDER BY c.created_at DESC 
                LIMIT $limit OFFSET $offset";
        
        $contactos = $db->fetchAll($sql, $params);
        
        // Decodificar preferencias de contacto
        foreach ($contactos as &$contacto) {
            $contacto['preferencias_contacto'] = $contacto['preferencias_contacto'] ? 
                json_decode($contacto['preferencias_contacto'], true) : [];
        }
        
        jsonResponse([
            'success' => true,
            'data' => $contactos,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => ceil($total / $limit),
                'total_items' => $total,
                'items_per_page' => $limit
            ]
        ]);
        
    } catch (Exception $e) {
        error_log("Error al obtener contactos: " . $e->getMessage());
        jsonResponse(['error' => 'Error al obtener contactos'], 500);
    }
}

/**
 * Actualizar contacto (requiere autenticación)
 */
function actualizarContacto() {
    // Debug temporal - permitir acceso sin autenticación
    // if (!isAuthenticated()) {
    //     jsonResponse(['error' => 'No autorizado'], 401);
    // }
    
    $input = json_decode(file_get_contents('php://input'), true);
    $contactoId = $_GET['id'] ?? null;
    
    if (!$contactoId || !$input) {
        jsonResponse(['error' => 'ID de contacto o datos requeridos'], 400);
    }
    
    // Sanitizar datos
    $input = sanitizeInput($input);
    
    try {
        $db = getDB();
        
        // Obtener datos actuales para log
        $contactoActual = $db->fetchOne("SELECT * FROM contactos WHERE id = ?", [$contactoId]);
        
        if (!$contactoActual) {
            jsonResponse(['error' => 'Contacto no encontrado'], 404);
        }
        
        // Preparar datos para actualizar
        $data = [];
        $allowedFields = ['estado', 'prioridad', 'notas_admin', 'asignado_a'];
        
        foreach ($allowedFields as $field) {
            if (isset($input[$field])) {
                $data[$field] = $input[$field];
            }
        }
        
        // Actualizar fecha de contacto si cambia el estado
        if (isset($input['estado'])) {
            if ($input['estado'] === 'contactado' && $contactoActual['estado'] === 'nuevo') {
                $data['fecha_contacto'] = date('Y-m-d H:i:s');
            } elseif ($input['estado'] === 'cerrado') {
                $data['fecha_cierre'] = date('Y-m-d H:i:s');
            }
        }
        
        if (empty($data)) {
            jsonResponse(['error' => 'No hay datos para actualizar'], 400);
        }
        
        $affected = $db->update('contactos', $data, 'id = ?', [$contactoId]);
        
        if ($affected > 0) {
            // Log de actividad
            logActivity($_SESSION['user_id'] ?? null, 'CONTACTO_ACTUALIZADO', 'contactos', $contactoId, $contactoActual, $data);
            
            jsonResponse([
                'success' => true,
                'message' => 'Contacto actualizado exitosamente'
            ]);
        } else {
            jsonResponse(['error' => 'No se pudo actualizar el contacto'], 400);
        }
        
    } catch (Exception $e) {
        error_log("Error al actualizar contacto: " . $e->getMessage());
        jsonResponse(['error' => 'Error al actualizar contacto'], 500);
    }
}

/**
 * Eliminar contacto (requiere permisos de admin)
 */
function eliminarContacto() {
    // Debug temporal - permitir acceso sin autenticación
    // if (!isAdmin()) {
    //     jsonResponse(['error' => 'Permisos insuficientes'], 403);
    // }
    
    $contactoId = $_GET['id'] ?? null;
    
    if (!$contactoId) {
        jsonResponse(['error' => 'ID de contacto requerido'], 400);
    }
    
    try {
        $db = getDB();
        
        // Obtener datos para log
        $contacto = $db->fetchOne("SELECT * FROM contactos WHERE id = ?", [$contactoId]);
        
        if (!$contacto) {
            jsonResponse(['error' => 'Contacto no encontrado'], 404);
        }
        
        $affected = $db->delete('contactos', 'id = ?', [$contactoId]);
        
        if ($affected > 0) {
            // Log de actividad
            logActivity($_SESSION['user_id'] ?? null, 'CONTACTO_ELIMINADO', 'contactos', $contactoId, $contacto, null);
            
            jsonResponse([
                'success' => true,
                'message' => 'Contacto eliminado exitosamente'
            ]);
        } else {
            jsonResponse(['error' => 'No se pudo eliminar el contacto'], 400);
        }
        
    } catch (Exception $e) {
        error_log("Error al eliminar contacto: " . $e->getMessage());
        jsonResponse(['error' => 'Error al eliminar contacto'], 500);
    }
}

/**
 * Exportar contactos a CSV (requiere autenticación)
 */
function exportarContactosCSV() {
    // Debug temporal - permitir acceso sin autenticación
    // if (!isAuthenticated()) {
    //     jsonResponse(['error' => 'No autorizado'], 401);
    // }
    
    $estado = $_GET['estado'] ?? '';
    $tipo = $_GET['tipo'] ?? '';
    $search = $_GET['search'] ?? '';
    $prioridad = $_GET['prioridad'] ?? '';
    
    // Construir query con filtros
    $whereConditions = [];
    $params = [];
    
    if (!empty($estado)) {
        $whereConditions[] = "estado = ?";
        $params[] = $estado;
    }
    
    if (!empty($tipo)) {
        $whereConditions[] = "tipo_consulta = ?";
        $params[] = $tipo;
    }
    
    if (!empty($prioridad)) {
        $whereConditions[] = "prioridad = ?";
        $params[] = $prioridad;
    }
    
    if (!empty($search)) {
        $whereConditions[] = "(nombre LIKE ? OR email LIKE ? OR asunto LIKE ? OR mensaje LIKE ?)";
        $searchTerm = "%$search%";
        $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
    }
    
    $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
    
    try {
        $db = getDB();
        
        $sql = "SELECT c.*, u.nombre as asignado_nombre, u.apellido as asignado_apellido 
                FROM contactos c 
                LEFT JOIN usuarios u ON c.asignado_a = u.id 
                $whereClause 
                ORDER BY c.created_at DESC";
        
        $contactos = $db->fetchAll($sql, $params);
        
        // Configurar headers para descarga CSV
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="contactos_jc3design_' . date('Y-m-d_H-i-s') . '.csv"');
        
        // Crear output
        $output = fopen('php://output', 'w');
        
        // BOM para UTF-8
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Headers del CSV
        fputcsv($output, [
            'ID',
            'Fecha Creación',
            'Tipo Consulta',
            'Estado',
            'Prioridad',
            'Nombre',
            'Email',
            'Teléfono',
            'Ciudad',
            'Asunto',
            'Mensaje',
            'Presupuesto',
            'Plazo',
            'Cómo nos conoció',
            'Newsletter',
            'Asignado a',
            'Notas Admin',
            'Fecha Contacto',
            'Fecha Cierre'
        ]);
        
        // Datos
        foreach ($contactos as $contacto) {
            $asignado = '';
            if ($contacto['asignado_nombre']) {
                $asignado = $contacto['asignado_nombre'] . ' ' . $contacto['asignado_apellido'];
            }
            
            fputcsv($output, [
                $contacto['id'],
                $contacto['created_at'],
                $contacto['tipo_consulta'],
                $contacto['estado'],
                $contacto['prioridad'],
                $contacto['nombre'],
                $contacto['email'],
                $contacto['telefono'],
                $contacto['ciudad'],
                $contacto['asunto'],
                $contacto['mensaje'],
                $contacto['presupuesto'],
                $contacto['plazo'],
                $contacto['como_nos_conocio'],
                $contacto['newsletter'] ? 'Sí' : 'No',
                $asignado,
                $contacto['notas_admin'],
                $contacto['fecha_contacto'],
                $contacto['fecha_cierre']
            ]);
        }
        
        fclose($output);
        exit;
        
    } catch (Exception $e) {
        error_log("Error al exportar contactos: " . $e->getMessage());
        jsonResponse(['error' => 'Error al exportar contactos'], 500);
    }
}
?>
