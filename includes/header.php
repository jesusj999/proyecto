<?php
// auntenticacion y logueo para que cualquier persona no esntre
require_once __DIR__ . '/config.php';
requireAuth();

$nombre_usuario = $_SESSION['nombre'] ?? 'Usuario';
$rol_usuario = $_SESSION['rol'] ?? 'Usuario';
$nivel = $_SESSION['nivel'] ?? 3;
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title> — Centro Educativo La Florida</title>
    <link rel="stylesheet" href="/proyecto/style.css">
</head>

<body>

    <header class="header">
        <div class="header-top">

            <div class="header-brand">
                <div class="header-logo">🏫</div>
                <div class="header-title">
                    <h1>CENTRO EDUCATIVO LA FLORIDA</h1>
                    <p>Sistema de Información para la Administración de Notas con Metodología Escuela Nueva</p>
                </div>
            </div>

            <nav class="navbar" style="margin-right: 350px;">
                <a href="/proyecto/dashboard.php" class="nav-link">Inicio</a>
            </nav>


            <div class="header-user">
                <div class="user-name"><?= htmlspecialchars($nombre_usuario) ?></div>
                <div class="user-role"><?= htmlspecialchars($rol_usuario) ?></div>
                <a href="/proyecto/logout.php" class="salir">🔒 Salir seguro</a>
            </div>

        </div>
    </header>

    <main class="main-content">