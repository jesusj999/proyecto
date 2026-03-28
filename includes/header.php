<?php
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
    <title><?= $pageTitle ?? 'Sistema de Notas' ?> — Centro Educativo La Florida</title>
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
        <div class="header-user">
            <div class="user-name"><?= htmlspecialchars($nombre_usuario) ?></div>
            <div class="user-role"><?= htmlspecialchars($rol_usuario) ?></div>
            <a href="/logout.php" class="salir">🔒 Salir seguro</a>
        </div>
    </div>
    <nav class="navbar">
        <div class="nav-item">
            <a href="/dashboard.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : '' ?>">Inicio</a>
        </div>
        <?php if ($nivel <= 2): ?>
        <div class="nav-item">
            <a href="#" class="nav-link">Registros ▾</a>
            <div class="dropdown">
                <?php if ($nivel == 1): ?>
                <a href="/pages/personas.php">👤 Personas</a>
                <a href="/pages/institucion.php">🏫 Institución</a>
                <a href="/pages/usuarios.php">🔑 Usuarios</a>
                <a href="/pages/asignar_materias.php">📚 Asignar Materias</a>
                <?php endif; ?>
                <a href="/pages/logros.php">🏆 Logros</a>
            </div>
        </div>
        <?php endif; ?>
        <?php if ($nivel == 1): ?>
        <div class="nav-item">
            <a href="#" class="nav-link">Matrícula ▾</a>
            <div class="dropdown">
                <a href="/pages/matricula.php">📋 Matrícula</a>
                <a href="/pages/promocion.php">🎓 Promoción</a>
            </div>
        </div>
        <?php endif; ?>
        <div class="nav-item">
            <a href="#" class="nav-link">Listar ▾</a>
            <div class="dropdown">
                <a href="/pages/listar_grupos.php">👥 Grupos</a>
                <a href="/pages/notas_estudiante.php">📊 Notas de Estudiante</a>
            </div>
        </div>
        <?php if ($nivel <= 2): ?>
        <div class="nav-item">
            <a href="#" class="nav-link">Calificar ▾</a>
            <div class="dropdown">
                <a href="/pages/calificar.php">✏️ Grupo</a>
            </div>
        </div>
        <?php endif; ?>
    </nav>
</header>

<main class="main-content">
