<?php
$pageTitle = 'Inicio';
require_once '../proyecto/includes/config.php';
requireAuth();

// Estadísticas rápidas
// Crea un array vacío donde se guardarán los conteos
$stats = [];

// Cuenta cuántos estudiantes hay en la base de datos
// Busca en personas los que tienen funcion='Estudiante'
// O los que están en la tabla estudiantes
$r = $conn->query("SELECT COUNT(*) as total FROM personas 
                   WHERE funcion = 'Estudiante' 
                   OR identificacion IN (SELECT identificacion FROM estudiantes)");

// Guarda el total en $stats['estudiantes'], si falla la consulta pone 0
$stats['estudiantes'] = $r ? $r->fetch_assoc()['total'] : 0;

// Cuenta cuántos docentes hay en la tabla docentes
$r = $conn->query("SELECT COUNT(*) as total FROM docentes");
$stats['docentes'] = $r ? $r->fetch_assoc()['total'] : 0;

// Cuenta cuántos grupos existen en la institución
$r = $conn->query("SELECT COUNT(*) as total FROM grupo");
$stats['grupos'] = $r ? $r->fetch_assoc()['total'] : 0;

// Cuenta cuántas sedes tiene la institución
$r = $conn->query("SELECT COUNT(*) as total FROM sedes");
$stats['sedes'] = $r ? $r->fetch_assoc()['total'] : 0;

// Obtiene el año actual del servidor (ej: 2026)
$anio_actual = date('Y');

include 'includes/header.php';
?>

<div class="page-header">
    <div>
        <div class="page-title">Panel de Inicio</div>
        <div class="page-subtitle">Bienvenido al Sistema de Notas — <?= $anio_actual ?></div>
    </div>
</div>

<?php
// Verifica si en la URL viene el parámetro 'error'
// Ejemplo de URL: dashboard.php?error=sin_permiso
if (isset($_GET['error'])): ?>

    <!-- Muestra alerta roja cuando el usuario no tiene permisos -->
    <div class="alert alert-error">⚠️ No tiene permisos para acceder.</div>

<?php endif; ?>


<?php
// Verifica si en la URL viene el parámetro 'msg'
// Ejemplo de URL: dashboard.php?msg=Guardado correctamente
if (isset($_GET['msg'])): ?>

    <!-- Muestra alerta verde con el mensaje de éxito -->
    <!-- htmlspecialchars() convierte caracteres especiales para evitar inyección de código -->
    <div class="alert alert-success">✅ <?= htmlspecialchars($_GET['msg']) ?></div>

<?php endif; ?>

<!-- Stats -->
<!-- Contenedor principal del grid de estadísticas -->
<!-- CSS lo organiza en columnas automáticamente -->
<div class="stats-grid">

    <!-- Tarjeta 1: Total de estudiantes -->
    <div class="stat-card">
        <!-- Ícono decorativo de la tarjeta -->
        <div class="stat-icon">👨‍🎓</div>
        <!-- Muestra el número total de estudiantes traído de la base de datos -->
        <div class="stat-number"><?= $stats['estudiantes'] ?></div>
        <!-- Etiqueta descriptiva de la tarjeta -->
        <div class="stat-label">Estudiantes Registrados</div>
    </div>

    <!-- Tarjeta 2: Total de docentes -->
    <div class="stat-card">
        <!-- Ícono decorativo de la tarjeta -->
        <div class="stat-icon">👩‍🏫</div>
        <!-- Muestra el número total de docentes traído de la base de datos -->
        <div class="stat-number"><?= $stats['docentes'] ?></div>
        <!-- Etiqueta descriptiva de la tarjeta -->
        <div class="stat-label">Docentes</div>
    </div>

    <!-- Tarjeta 3: Total de grupos -->
    <div class="stat-card">
        <!-- Ícono decorativo de la tarjeta -->
        <div class="stat-icon">👥</div>
        <!-- Muestra el número total de grupos traído de la base de datos -->
        <div class="stat-number"><?= $stats['grupos'] ?></div>
        <!-- Etiqueta descriptiva de la tarjeta -->
        <div class="stat-label">Grupos</div>
    </div>

    <!-- Tarjeta 4: Total de sedes -->
    <div class="stat-card">
        <!-- Ícono decorativo de la tarjeta -->
        <div class="stat-icon">🏫</div>
        <!-- Muestra el número total de sedes traído de la base de datos -->
        <div class="stat-number"><?= $stats['sedes'] ?></div>
        <!-- Etiqueta descriptiva de la tarjeta -->
        <div class="stat-label">Sedes</div>
    </div>

</div><!-- Cierre del grid de estadísticas -->

<!-- Accesos rápidos según nivel -->
<!-- Tarjeta de accesos rápidos -->
<div class="card">
    <div class="card-header">⚡ Accesos Rápidos</div>
    <div class="card-body">

        <!-- Grid que organiza los botones automáticamente según el espacio disponible -->
        <!-- Cada botón tiene mínimo 200px de ancho y se ajusta al espacio restante -->
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 12px;">

            <!-- BOTONES PARA ADMIN (nivel 1) Y DOCENTE (nivel 2) -->
            <!-- <= 2 significa que aplica para nivel 1 y nivel 2 -->
            <?php if ($_SESSION['nivel'] <= 2): ?>

                <!-- Botón para ir al módulo de Logros e Indicadores -->
                <a href="./logros.php" class="btn btn-outline"
                    style="justify-content:center; padding:20px; flex-direction:column; gap:8px;">
                    <span style="font-size:24px">🏆</span>
                    <span>Logros e Indicadores</span>
                </a>

                <!-- Botón para listar los grupos de estudiantes -->
                <a href="../proyecto/pages/listar_grupos.php" class="btn btn-outline"
                    style="justify-content:center; padding:20px; flex-direction:column; gap:8px;">
                    <span style="font-size:24px">👥</span>
                    <span>Listar Grupos</span>
                </a>

                <!-- Botón para ir a calificar estudiantes (color dorado por ser acción principal) -->
                <a href="../proyecto/pages/calificar.php" class="btn btn-secondary"
                    style="justify-content:center; padding:20px; flex-direction:column; gap:8px;">
                    <span style="font-size:24px">✏️</span>
                    <span>Calificar Estudiantes</span>
                </a>

            <?php endif; ?>
            <!-- Fin botones Admin y Docente -->

            <!-- BOTONES EXCLUSIVOS DEL ADMINISTRADOR (nivel 1) -->
            <!-- == 1 significa que solo aplica para el administrador -->
            <?php if ($_SESSION['nivel'] == 1): ?>

                <!-- Botón para registrar nuevas personas en el sistema -->
                <a href="../proyecto/pages/personas.php" class="btn btn-outline"
                    style="justify-content:center; padding:20px; flex-direction:column; gap:8px;">
                    <span style="font-size:24px">👤</span>
                    <span>Registrar Personas</span>
                </a>

                <!-- Botón para gestionar matrículas de estudiantes -->
                <a href="../proyecto/pages/matricula.php" class="btn btn-outline"
                    style="justify-content:center; padding:20px; flex-direction:column; gap:8px;">
                    <span style="font-size:24px">📋</span>
                    <span>Matrícula</span>
                </a>

                <!-- Botón para administrar sedes, grupos y asignaturas -->
                <a href="institucion.php" class="btn btn-outline"
                    style="justify-content:center; padding:20px; flex-direction:column; gap:8px;">
                    <span style="font-size:24px">🏫</span>
                    <span>Institución</span>
                </a>

                <!-- Botón para gestionar usuarios del sistema -->
                <a href="../proyecto/pages/usuarios.php" class="btn btn-outline"
                    style="justify-content:center; padding:20px; flex-direction:column; gap:8px;">
                    <span style="font-size:24px">🔑</span>
                    <span>Usuarios</span>
                </a>

                <!-- Botón para asignar materias a los docentes -->
                <a href="../proyecto/pages/asignar_materias.php" class="btn btn-outline"
                    style="justify-content:center; padding:20px; flex-direction:column; gap:8px;">
                    <span style="font-size:24px">📚</span>
                    <span>Asignar Materias</span>
                </a>

                <!-- Botón para promover grupos completos al siguiente grado -->
                <a href="../proyecto/pages/promocion.php" class="btn btn-outline"
                    style="justify-content:center; padding:20px; flex-direction:column; gap:8px;">
                    <span style="font-size:24px">🎓</span>
                    <span>Promoción</span>
                </a>

            <?php endif; ?>
            <!-- Fin botones exclusivos del Administrador -->

            <a href="../proyecto/pages/notas_estudiante.php" class="btn btn-outline" style="justify-content:center; padding:20px; flex-direction:column; gap:8px;">
                <span style="font-size:24px">📊</span>
                <span>Ver Notas</span>
            </a>

        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>