<?php
/**
 * Configuración de la base de datos MySQL para JC3Design
 */

// Configuración de la base de datos
define('DB_HOST', 'localhost');
define('DB_NAME', 'jc3design_db');
define('DB_USER', 'root'); // Cambiar por tu usuario de MySQL
define('DB_PASS', ''); // Cambiar por tu contraseña de MySQL
define('DB_CHARSET', 'utf8mb4');

// Configuración de la aplicación
define('APP_NAME', 'JC3Design');
define('APP_URL', 'http://localhost:8000'); // Cambiar según tu configuración
define('APP_VERSION', '1.0.0');

// Configuración de seguridad
define('JWT_SECRET', 'jc3design_secret_key_2025'); // Cambiar en producción
define('SESSION_TIMEOUT', 3600); // 1 hora

// Configuración de archivos
define('UPLOAD_DIR', '../uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png', 'webp']);

// Configuración de paginación
define('ITEMS_PER_PAGE', 12);

// Configuración de logs
define('LOG_ENABLED', true);
define('LOG_FILE', '../logs/app.log');

/**
 * Clase Database para manejar la conexión a MySQL
 */
class Database {
    private static $instance = null;
    private $connection;
    
    private function __construct() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            
            $this->connection = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            error_log("Error de conexión a la base de datos: " . $e->getMessage());
            throw new Exception("Error de conexión a la base de datos");
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->connection;
    }
    
    public function query($sql, $params = []) {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            error_log("Error en consulta SQL: " . $e->getMessage());
            throw new Exception("Error en la consulta de la base de datos");
        }
    }
    
    public function fetchAll($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }
    
    public function fetchOne($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetch();
    }
    
    public function insert($table, $data) {
        $fields = array_keys($data);
        $placeholders = ':' . implode(', :', $fields);
        $fieldList = implode(', ', $fields);
        
        $sql = "INSERT INTO {$table} ({$fieldList}) VALUES ({$placeholders})";
        
        $this->query($sql, $data);
        return $this->connection->lastInsertId();
    }
    
    public function update($table, $data, $where, $whereParams = []) {
        $fields = array_keys($data);
        $setClause = implode(' = ?, ', $fields) . ' = ?';
        
        $sql = "UPDATE {$table} SET {$setClause} WHERE {$where}";
        
        $params = array_values($data);
        $params = array_merge($params, $whereParams);
        
        $stmt = $this->query($sql, $params);
        return $stmt->rowCount();
    }
    
    public function delete($table, $where, $params = []) {
        $sql = "DELETE FROM {$table} WHERE {$where}";
        $stmt = $this->query($sql, $params);
        return $stmt->rowCount();
    }
}

/**
 * Función helper para obtener la conexión
 */
function getDB() {
    return Database::getInstance();
}

/**
 * Función helper para respuestas JSON
 */
function jsonResponse($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Función helper para validar datos
 */
function validateData($data, $rules) {
    $errors = [];
    
    foreach ($rules as $field => $rule) {
        if (!isset($data[$field]) || empty($data[$field])) {
            if (strpos($rule, 'required') !== false) {
                $errors[$field] = "El campo {$field} es requerido";
            }
            continue;
        }
        
        $value = $data[$field];
        
        if (strpos($rule, 'min:') !== false) {
            preg_match('/min:(\d+)/', $rule, $matches);
            $min = $matches[1];
            if (strlen($value) < $min) {
                $errors[$field] = "El campo {$field} debe tener al menos {$min} caracteres";
            }
        }
        
        if (strpos($rule, 'max:') !== false) {
            preg_match('/max:(\d+)/', $rule, $matches);
            $max = $matches[1];
            if (strlen($value) > $max) {
                $errors[$field] = "El campo {$field} debe tener máximo {$max} caracteres";
            }
        }
        
        if (strpos($rule, 'email') !== false) {
            if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                $errors[$field] = "El campo {$field} debe ser un email válido";
            }
        }
        
        if (strpos($rule, 'numeric') !== false) {
            if (!is_numeric($value)) {
                $errors[$field] = "El campo {$field} debe ser numérico";
            }
        }
    }
    
    return $errors;
}

/**
 * Función helper para sanitizar datos
 */
function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Función helper para generar logs
 */
function logActivity($usuario_id, $accion, $tabla = null, $registro_id = null, $datos_anteriores = null, $datos_nuevos = null) {
    if (!LOG_ENABLED) return;
    
    try {
        $db = getDB();
        
        // Verificar si el usuario_id existe en la tabla usuarios
        if ($usuario_id) {
            $userExists = $db->fetchOne("SELECT id FROM usuarios WHERE id = ?", [$usuario_id]);
            if (!$userExists) {
                $usuario_id = null; // Si el usuario no existe, usar null
            }
        }
        
        $sql = "INSERT INTO logs (usuario_id, accion, tabla, registro_id, datos_anteriores, datos_nuevos, ip, user_agent) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        $params = [
            $usuario_id,
            $accion,
            $tabla,
            $registro_id,
            $datos_anteriores ? json_encode($datos_anteriores) : null,
            $datos_nuevos ? json_encode($datos_nuevos) : null,
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null
        ];
        
        $db->query($sql, $params);
    } catch (Exception $e) {
        error_log("Error al registrar log: " . $e->getMessage());
    }
}

/**
 * Función helper para verificar autenticación
 */
function isAuthenticated() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Función helper para verificar permisos de administrador
 */
function isAdmin() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

/**
 * Función helper para CORS
 */
function enableCORS() {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit;
    }
}

// Habilitar CORS para APIs
if (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], '/api/') !== false) {
    enableCORS();
}
?>
