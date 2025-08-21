<?php
require_once '../database/config.php';

// Verificar autenticación
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isAuthenticated() || !isAdmin()) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$db = getDB();

// Configurar headers para JSON
header('Content-Type: application/json');

// Obtener método HTTP
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'DELETE':
        // Eliminar usuario
        $id = $_GET['id'] ?? null;
        
        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'ID de usuario requerido']);
            exit;
        }
        
        // Verificar que no se elimine a sí mismo
        if ($id == $_SESSION['user_id']) {
            http_response_code(400);
            echo json_encode(['error' => 'No puedes eliminar tu propia cuenta']);
            exit;
        }
        
        try {
            // Obtener información del usuario antes de eliminar
            $usuario = $db->fetchOne("SELECT * FROM usuarios WHERE id = ?", [$id]);
            
            if (!$usuario) {
                http_response_code(404);
                echo json_encode(['error' => 'Usuario no encontrado']);
                exit;
            }
            
            // Eliminar usuario
            $db->query("DELETE FROM usuarios WHERE id = ?", [$id]);
            
            // Registrar en logs
            logActivity($_SESSION['user_id'], 'eliminar', 'usuarios', $id, $usuario, null);
            
            echo json_encode(['success' => 'Usuario eliminado correctamente']);
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Error al eliminar usuario: ' . $e->getMessage()]);
        }
        break;
        
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Método no permitido']);
        break;
}
?>
