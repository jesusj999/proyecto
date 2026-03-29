<?php
// Configuración de la base de datos
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'notas');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$conn->set_charset('utf8mb4');

if ($conn->connect_error) {
    die(json_encode(['error' => 'Error de conexión: ' . $conn->connect_error]));
}

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Función para verificar autenticación
function requireAuth($niveles = [1, 2, 3]) {
    if (!isset($_SESSION['usuario'])) {
        header('Location: /index.php');
        exit;
    }
    if (!in_array($_SESSION['nivel'], $niveles)) {
        header('Location: /dashboard.php?error=sin_permiso');
        exit;
    }
}

// Función para verificar si es admin
function isAdmin() {
    return isset($_SESSION['nivel']) && $_SESSION['nivel'] == 1;
}

// Función para verificar si es docente
function isDocente() {
    return isset($_SESSION['nivel']) && $_SESSION['nivel'] == 2;
}

// Función para verificar si es estudiante
function isEstudiante() {
    return isset($_SESSION['nivel']) && $_SESSION['nivel'] == 3;
}

function sanitize($data) {
    global $conn;
    return $conn->real_escape_string(trim($data ?? ''));
}
?>
