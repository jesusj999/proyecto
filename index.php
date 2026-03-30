<?php
require_once 'includes/config.php';

if (session_status() === PHP_SESSION_NONE) session_start();

// Si ya está logueado
if (isset($_SESSION['usuario'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username'] ?? '');
    $password = md5($_POST['password'] ?? '');

    $sql = "SELECT u.idUsuario, u.username, u.idnivel, n.descripcion as rol,
                   CONCAT(p.primerNombre, ' ', p.primerApellido) as nombre,
                   p.identificacion
            FROM usuarios u
            JOIN nivelusuario n ON u.idnivel = n.idNivel
            LEFT JOIN personas p ON (
                CASE u.idnivel
                    WHEN 1 THEN p.identificacion = u.idUsuario
                    WHEN 2 THEN p.identificacion = u.idUsuario
                    WHEN 3 THEN p.identificacion = u.idUsuario
                END
            )
            WHERE u.username = '$username' AND u.contrasena = '$password'
            LIMIT 1";

    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $_SESSION['usuario'] = $user['username'];
        $_SESSION['usuario_id'] = $user['idUsuario'];
        $_SESSION['nivel'] = $user['idnivel'];
        $_SESSION['rol'] = $user['rol'];
        $_SESSION['nombre'] = $user['nombre'] ?? $user['username'];
        $_SESSION['identificacion'] = $user['identificacion'];

        header('Location: dashboard.php');
        exit;
    } else {
        $error = 'Usuario o contraseña incorrectos.';
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ingreso — Centro Educativo La Florida</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .login-btn {
            width: 100%;
            padding: 12px;
            font-size: 15px;
            margin-top: 8px;
        }

        .form-group {
            margin-bottom: 16px;
        }

        .form-group label {
            display: block;
            margin-bottom: 6px;
        }
    </style>
</head>

<body>
    <div class="login-page">
        <div class="login-box">
            <div class="login-logo">
                <div class="escudo">🏫</div>
                <h2>CENTRO EDUCATIVO<br>LA FLORIDA</h2>
                <p>Sistema de Información para la Administración de Notas<br>con Metodología Escuela Nueva</p>
            </div>

            <div class="login-divider"></div>
            <div class="login-title">Ingreso del Usuario</div>

            <?php
            // Verifica si la variable $error tiene algún contenido
            // $error se define al inicio del archivo como cadena vacía ''
            // Solo tendrá contenido si ocurrió algún problema
            if ($error): ?>

                <!-- Muestra una caja roja con el mensaje de error -->
                <!-- htmlspecialchars() convierte caracteres especiales como < > & 
         para evitar que se ejecute código malicioso en pantalla -->
                <div class="alert alert-error">⚠️ <?= htmlspecialchars($error) ?></div>

            <?php endif; ?>
            ```
            <!-- 
            Por ejemplo si el error es `"No se encontró estudiante con código 14189"` se mostraría así en pantalla:
            ```
            ⚠️ No se encontró estudiante con código 14189 -->

            <form method="POST">
                <!-- Contenedor del campo de usuario -->
                <div class="form-group">

                    <!-- Etiqueta visible, al hacer clic lleva el cursor al input -->
                    <label for="username">👤 Usuario</label>

                    <!-- Campo de texto para ingresar el username -->
                    <!-- required=obligatorio, autofocus=cursor automático al cargar -->
                    <!-- value mantiene el username escrito si el login falló -->
                    <input type="text" id="username" name="username" placeholder="Ingrese su username" required autofocus value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">

                </div>
                <div class="form-group">
                    <label for="password">🔒 Contraseña</label>
                    <input type="password" id="password" name="password" placeholder="Ingrese su contraseña" required>
                </div>
                <button type="submit" class="btn btn-primary login-btn">ENTRAR →</button>
            </form>

            <div style="text-align:center; margin-top:20px; font-size:11px; color:var(--texto-suave);">
                Para soporte técnico contacte al administrador del sistema
            </div>
        </div>
    </div>
</body>

</html>