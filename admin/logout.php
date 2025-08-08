<?php
require_once '../database/config.php';

// Iniciar sesión si no está activa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Registrar la actividad de logout si hay un usuario logueado
if (isset($_SESSION['user_id'])) {
    logActivity($_SESSION['user_id'], 'logout', 'usuarios', $_SESSION['user_id']);
}

// Destruir la sesión
session_destroy();

// Redirigir al login
header('Location: login.php');
exit;
?>
