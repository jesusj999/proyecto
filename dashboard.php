<?php
$pageTitle = 'Inicio';
require_once '../proyecto/includes/config.php';
requireAuth();

// Estadísticas rápidas
$stats = [];
$r = $conn->query("SELECT COUNT(*) as total FROM personas WHERE funcion = 'Estudiante' OR identificacion IN (SELECT identificacion FROM estudiantes)");
$stats['estudiantes'] = $r ? $r->fetch_assoc()['total'] : 0;

$r = $conn->query("SELECT COUNT(*) as total FROM docentes");
$stats['docentes'] = $r ? $r->fetch_assoc()['total'] : 0;

$r = $conn->query("SELECT COUNT(*) as total FROM grupo");
$stats['grupos'] = $r ? $r->fetch_assoc()['total'] : 0;

$r = $conn->query("SELECT COUNT(*) as total FROM sedes");
$stats['sedes'] = $r ? $r->fetch_assoc()['total'] : 0;

$anio_actual = date('Y');

include 'includes/header.php';
?>

<div class="page-header">
    <div>
        <div class="page-title">Panel de Inicio</div>
        <div class="page-subtitle">Bienvenido al Sistema de Administración de Notas — <?= $anio_actual ?></div>
    </div>
</div>

<?php if (isset($_GET['error'])): ?>
<div class="alert alert-error">⚠️ No tiene permisos para acceder a esa sección.</div>
<?php endif; ?>

<?php if (isset($_GET['msg'])): ?>
<div class="alert alert-success">✅ <?= htmlspecialchars($_GET['msg']) ?></div>
<?php endif; ?>

<!-- Stats -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon">👨‍🎓</div>
        <div class="stat-number"><?= $stats['estudiantes'] ?></div>
        <div class="stat-label">Estudiantes Registrados</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon">👩‍🏫</div>
        <div class="stat-number"><?= $stats['docentes'] ?></div>
        <div class="stat-label">Docentes</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon">👥</div>
        <div class="stat-number"><?= $stats['grupos'] ?></div>
        <div class="stat-label">Grupos</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon">🏫</div>
        <div class="stat-number"><?= $stats['sedes'] ?></div>
        <div class="stat-label">Sedes</div>
    </div>
</div>

<!-- Accesos rápidos según nivel -->
<div class="card">
    <div class="card-header">⚡ Accesos Rápidos</div>
    <div class="card-body">
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 12px;">

            <?php if ($_SESSION['nivel'] <= 2): ?>
            <a href="./logros.php" class="btn btn-outline" style="justify-content:center; padding:20px; flex-direction:column; gap:8px;">
                <span style="font-size:24px">🏆</span>
                <span>Logros e Indicadores</span>
            </a>
            <a href="../proyecto/pages/listar_grupos.php" class="btn btn-outline" style="justify-content:center; padding:20px; flex-direction:column; gap:8px;">
                <span style="font-size:24px">👥</span>
                <span>Listar Grupos</span>
            </a>
            <a href="../proyecto/pages/calificar.php" class="btn btn-secondary" style="justify-content:center; padding:20px; flex-direction:column; gap:8px;">
                <span style="font-size:24px">✏️</span>
                <span>Calificar Estudiantes</span>
            </a>
            <?php endif; ?>

            <?php if ($_SESSION['nivel'] == 1): ?>
            <a href="../proyecto/pages/personas.php" class="btn btn-outline" style="justify-content:center; padding:20px; flex-direction:column; gap:8px;">
                <span style="font-size:24px">👤</span>
                <span>Registrar Personas</span>
            </a>
            <a href="../proyecto/pages/matricula.php" class="btn btn-outline" style="justify-content:center; padding:20px; flex-direction:column; gap:8px;">
                <span style="font-size:24px">📋</span>
                <span>Matrícula</span>
            </a>
            <a href="institucion.php" class="btn btn-outline" style="justify-content:center; padding:20px; flex-direction:column; gap:8px;">
                <span style="font-size:24px">🏫</span>
                <span>Institución</span>
            </a>
            <a href="../proyecto/pages/usuarios.php" class="btn btn-outline" style="justify-content:center; padding:20px; flex-direction:column; gap:8px;">
                <span style="font-size:24px">🔑</span>
                <span>Usuarios</span>
            </a>
            <a href="../proyecto/pages/asignar_materias.php" class="btn btn-outline" style="justify-content:center; padding:20px; flex-direction:column; gap:8px;">
                <span style="font-size:24px">📚</span>
                <span>Asignar Materias</span>
            </a>
            <a href="../proyecto/pages/promocion.php" class="btn btn-outline" style="justify-content:center; padding:20px; flex-direction:column; gap:8px;">
                <span style="font-size:24px">🎓</span>
                <span>Promoción</span>
            </a>
            <?php endif; ?>

            <a href="../proyecto/pages/notas_estudiante.php" class="btn btn-outline" style="justify-content:center; padding:20px; flex-direction:column; gap:8px;">
                <span style="font-size:24px">📊</span>
                <span>Ver Notas</span>
            </a>

        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
