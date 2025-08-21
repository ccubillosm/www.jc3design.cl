<?php
require_once '../database/config.php';

// Verificar autenticación
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isAuthenticated() || !isAdmin()) {
    header('Location: login.php');
    exit;
}

$db = getDB();
$error = '';
$success = '';

// Obtener ID del usuario
$id = $_GET['id'] ?? null;
if (!$id) {
    header('Location: usuarios.php');
    exit;
}

// Obtener datos del usuario
$usuario = $db->fetchOne("SELECT * FROM usuarios WHERE id = ?", [$id]);
if (!$usuario) {
    header('Location: usuarios.php');
    exit;
}

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $nombre = trim($_POST['nombre'] ?? '');
    $apellido = trim($_POST['apellido'] ?? '');
    $rol = $_POST['rol'] ?? 'editor';
    $activo = isset($_POST['activo']) ? 1 : 0;
    $nueva_password = trim($_POST['nueva_password'] ?? '');
    
    // Validaciones
    if (empty($username)) {
        $error = 'El username es obligatorio';
    } elseif (empty($email)) {
        $error = 'El email es obligatorio';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'El email no es válido';
    } else {
        try {
            // Verificar si el username ya existe (excluyendo el usuario actual)
            $usernameExists = $db->fetchOne("SELECT id FROM usuarios WHERE username = ? AND id != ?", [$username, $id]);
            if ($usernameExists) {
                $error = 'El username ya existe en otro usuario';
            } else {
                // Verificar si el email ya existe (excluyendo el usuario actual)
                $emailExists = $db->fetchOne("SELECT id FROM usuarios WHERE email = ? AND id != ?", [$email, $id]);
                if ($emailExists) {
                    $error = 'El email ya existe en otro usuario';
                } else {
                    // Preparar datos para actualizar
                    $updateData = [
                        'username' => $username,
                        'email' => $email,
                        'nombre' => $nombre,
                        'apellido' => $apellido,
                        'rol' => $rol,
                        'activo' => $activo
                    ];
                    
                    // Si se proporcionó una nueva contraseña, hashearla
                    if (!empty($nueva_password)) {
                        if (strlen($nueva_password) < 6) {
                            $error = 'La contraseña debe tener al menos 6 caracteres';
                        } else {
                            $updateData['password'] = password_hash($nueva_password, PASSWORD_BCRYPT, ['cost' => 12]);
                        }
                    }
                    
                    if (empty($error)) {
                        // Actualizar usuario
                        $sql = "UPDATE usuarios SET username = ?, email = ?, nombre = ?, apellido = ?, 
                                rol = ?, activo = ?, updated_at = CURRENT_TIMESTAMP";
                        $params = [$username, $email, $nombre, $apellido, $rol, $activo];
                        
                        // Agregar contraseña si se cambió
                        if (isset($updateData['password'])) {
                            $sql .= ", password = ?";
                            $params[] = $updateData['password'];
                        }
                        
                        $sql .= " WHERE id = ?";
                        $params[] = $id;
                        
                        $db->query($sql, $params);
                        
                        // Registrar en logs
                        logActivity($_SESSION['user_id'], 'actualizar', 'usuarios', $id, $usuario, $_POST);
                        
                        $success = 'Usuario actualizado correctamente';
                        
                        // Recargar datos
                        $usuario = $db->fetchOne("SELECT * FROM usuarios WHERE id = ?", [$id]);
                    }
                }
            }
        } catch (Exception $e) {
            $error = 'Error al actualizar el usuario: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Usuario - Panel de Administración</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.4.1/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../css/dark-mode.css">
    <style>
        .admin-sidebar {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .admin-content {
            min-height: 100vh;
            background-color: #f8f9fa;
        }
        .nav-link {
            color: rgba(255,255,255,0.8);
            transition: all 0.3s ease;
        }
        .nav-link:hover {
            color: white;
            background-color: rgba(255,255,255,0.1);
        }
        .nav-link.active {
            color: white;
            background-color: rgba(255,255,255,0.2);
        }
    </style>
</head>

<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 px-0">
                <div class="admin-sidebar p-3">
                    <div class="text-center mb-4">
                        <h4 class="text-white">JC3Design</h4>
                        <p class="text-white-50 small">Panel de Administración</p>
                    </div>
                    
                    <nav class="nav flex-column">
                        <a class="nav-link" href="index.php">
                            <i class="fas fa-tachometer-alt mr-2"></i>Dashboard
                        </a>
                        <a class="nav-link" href="productos.php">
                            <i class="fas fa-box mr-2"></i>Productos
                        </a>
                        <a class="nav-link" href="categorias.php">
                            <i class="fas fa-tags mr-2"></i>Categorías
                        </a>
                        <a class="nav-link" href="cotizaciones.php">
                            <i class="fas fa-file-invoice mr-2"></i>Cotizaciones
                        </a>
                        <a class="nav-link" href="contactos.php">
                            <i class="fas fa-address-book mr-2"></i>Contactos CRM
                        </a>
                        <a class="nav-link active" href="usuarios.php">
                            <i class="fas fa-users mr-2"></i>Usuarios
                        </a>
                        <a class="nav-link" href="logs.php">
                            <i class="fas fa-history mr-2"></i>Logs
                        </a>
                        <hr class="bg-white">
                        <a class="nav-link" href="../index.html" target="_blank">
                            <i class="fas fa-external-link-alt mr-2"></i>Ver Sitio
                        </a>
                        <a class="nav-link" href="logout.php">
                            <i class="fas fa-sign-out-alt mr-2"></i>Cerrar Sesión
                        </a>
                    </nav>
                </div>
            </div>
            
            <!-- Contenido Principal -->
            <div class="col-md-9 col-lg-10">
                <div class="admin-content p-4">
                    <!-- Header -->
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h2 class="mb-0">Editar Usuario</h2>
                            <p class="text-muted mb-0">Modifica los datos del usuario</p>
                        </div>
                        <a href="usuarios.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left mr-2"></i>Volver a Usuarios
                        </a>
                    </div>
                    
                    <!-- Alertas -->
                    <?php if ($error): ?>
                        <div class="alert alert-danger" role="alert">
                            <i class="fas fa-exclamation-triangle mr-2"></i><?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success" role="alert">
                            <i class="fas fa-check-circle mr-2"></i><?php echo htmlspecialchars($success); ?>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Formulario -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-edit mr-2"></i>Datos del Usuario
                            </h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="username">Username *</label>
                                            <input type="text" class="form-control" id="username" name="username" 
                                                   value="<?php echo htmlspecialchars($usuario['username']); ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="email">Email *</label>
                                            <input type="email" class="form-control" id="email" name="email" 
                                                   value="<?php echo htmlspecialchars($usuario['email']); ?>" required>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="nombre">Nombre</label>
                                            <input type="text" class="form-control" id="nombre" name="nombre" 
                                                   value="<?php echo htmlspecialchars($usuario['nombre'] ?? ''); ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="apellido">Apellido</label>
                                            <input type="text" class="form-control" id="apellido" name="apellido" 
                                                   value="<?php echo htmlspecialchars($usuario['apellido'] ?? ''); ?>">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="rol">Rol</label>
                                            <select class="form-control" id="rol" name="rol">
                                                <option value="editor" <?php echo $usuario['rol'] === 'editor' ? 'selected' : ''; ?>>Editor</option>
                                                <option value="admin" <?php echo $usuario['rol'] === 'admin' ? 'selected' : ''; ?>>Administrador</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <div class="custom-control custom-switch mt-4">
                                                <input type="checkbox" class="custom-control-input" id="activo" name="activo" 
                                                       <?php echo $usuario['activo'] ? 'checked' : ''; ?>>
                                                <label class="custom-control-label" for="activo">Usuario Activo</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <hr>
                                
                                <div class="form-group">
                                    <label for="nueva_password">Nueva Contraseña</label>
                                    <input type="password" class="form-control" id="nueva_password" name="nueva_password" 
                                           placeholder="Dejar en blanco para mantener la actual">
                                    <small class="form-text text-muted">Mínimo 6 caracteres. Solo llenar si quieres cambiar la contraseña.</small>
                                </div>
                                
                                <div class="form-group">
                                    <label>Información del Sistema</label>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <small class="text-muted">
                                                <strong>Creado:</strong> <?php echo date('d/m/Y H:i', strtotime($usuario['created_at'])); ?>
                                            </small>
                                        </div>
                                        <div class="col-md-6">
                                            <small class="text-muted">
                                                <strong>Última modificación:</strong> <?php echo date('d/m/Y H:i', strtotime($usuario['updated_at'])); ?>
                                            </small>
                                        </div>
                                    </div>
                                </div>
                                
                                <hr>
                                
                                <div class="d-flex justify-content-between">
                                    <a href="usuarios.php" class="btn btn-secondary">
                                        <i class="fas fa-times mr-2"></i>Cancelar
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save mr-2"></i>Guardar Cambios
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.4.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.4.1/dist/js/bootstrap.min.js"></script>
    <script src="../js/dark-mode.js"></script>
</body>
</html>
