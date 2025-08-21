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
        // Eliminar backup
        $filename = $_GET['filename'] ?? null;
        
        if (!$filename) {
            http_response_code(400);
            echo json_encode(['error' => 'Nombre de archivo requerido']);
            exit;
        }
        
        // Validar que el archivo sea un backup válido
        if (!preg_match('/^jc3design_backup_\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2}\.sql$/', $filename)) {
            http_response_code(400);
            echo json_encode(['error' => 'Nombre de archivo inválido']);
            exit;
        }
        
        $filepath = '../backups/' . $filename;
        
        if (!file_exists($filepath)) {
            http_response_code(404);
            echo json_encode(['error' => 'Archivo de backup no encontrado']);
            exit;
        }
        
        try {
            // Eliminar archivo
            if (unlink($filepath)) {
                // Registrar en logs
                logActivity($_SESSION['user_id'], 'eliminar_backup', 'sistema', null, null, ['archivo' => $filename]);
                
                echo json_encode(['success' => 'Backup eliminado correctamente']);
            } else {
                throw new Exception('No se pudo eliminar el archivo');
            }
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Error al eliminar backup: ' . $e->getMessage()]);
        }
        break;
        
    case 'GET':
        // Listar backups
        $backup_dir = '../backups/';
        $backups = [];
        
        if (is_dir($backup_dir)) {
            $files = glob($backup_dir . '*.sql');
            foreach ($files as $file) {
                $backups[] = [
                    'filename' => basename($file),
                    'size' => filesize($file),
                    'modified' => filemtime($file),
                    'path' => $file
                ];
            }
            
            // Ordenar por fecha de modificación (más reciente primero)
            usort($backups, function($a, $b) {
                return $b['modified'] - $a['modified'];
            });
        }
        
        echo json_encode($backups);
        break;
        
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Método no permitido']);
        break;
}
?>
