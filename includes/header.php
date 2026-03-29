<?php
// Carga el archivo config.php (conexión y funciones)
require_once __DIR__ . '/config.php';

// Verifica que el usuario esté logueado
// Si no está logueado lo redirige al login
requireAuth();

// Obtiene el nombre del usuario desde la sesión
// Si no existe, muestra "Usuario"
$nombre_usuario = $_SESSION['nombre'] ?? 'Usuario';

// Obtiene el rol del usuario
$rol_usuario = $_SESSION['rol'] ?? 'Usuario';

// Obtiene el nivel del usuario (1=admin, 2=docente, 3=estudiante)
$nivel = $_SESSION['nivel'] ?? 3;
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <!-- Permite usar caracteres especiales como tildes -->
    <meta charset="UTF-8">

    <!-- Hace que la página sea responsive (se adapte a celular) -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Título de la pestaña del navegador -->
    <title>Centro Educativo La Florida</title>

    <!-- Conecta el archivo de estilos CSS -->
    <link rel="stylesheet" href="/proyecto/style.css">
</head>

<body>

    <!-- ENCABEZADO PRINCIPAL -->
    <header class="header">

        <div class="header-top">

            <!-- LOGO Y NOMBRE -->
            <div class="header-brand">

                <!-- Icono -->
                <div class="header-logo">🏫</div>

                <!-- Título y descripción -->
                <div class="header-title">
                    <h1>CENTRO EDUCATIVO LA FLORIDA</h1>
                    <p>Sistema de Información para la Administración de Notas con Metodología Escuela Nueva</p>
                </div>

            </div>

            <!-- MENÚ DE NAVEGACIÓN -->
            <nav class="navbar" style="margin-right: 350px;">

                <!-- Botón para ir al inicio -->
                <a href="/proyecto/dashboard.php" class="nav-link">Inicio</a>

            </nav>

            <!-- INFORMACIÓN DEL USUARIO -->
            <div class="header-user">

                <!-- Muestra el nombre del usuario -->
                <div class="user-name">
                    <?= htmlspecialchars($nombre_usuario) ?>
                </div>

                <!-- Muestra el rol del usuario -->
                <div class="user-role">
                    <?= htmlspecialchars($rol_usuario) ?>
                </div>

                <!-- Botón para cerrar sesión -->
                <a href="/proyecto/logout.php" class="salir">
                    🔒 Salir seguro
                </a>

            </div>

        </div>

    </header>

    <!-- CONTENIDO PRINCIPAL DE LA PÁGINA -->
    <main class="main-content">