<?php
$pageTitle = 'Matrícula';
require_once '../includes/config.php';
requireAuth([1]);

$msg = '';
$error = '';
$anio_actual = date('Y');

// BUSCAR estudiante
$estudiante_datos = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'buscar_est') {
    $codigo = (int)$_POST['codigo_est'];
    $r = $conn->query("SELECT p.*, e.instProcedencia
                       FROM personas p JOIN estudiantes e ON p.identificacion = e.identificacion
                       WHERE p.identificacion = $codigo");
    if ($r && $r->num_rows > 0) {
        $estudiante_datos = $r->fetch_assoc();

        // Matrícula actual
        $mat = $conn->query("SELECT m.*, g.descripcion as grado_desc, gr.nombre as grupo_nombre,
                             s.nombre as sede_nombre, ea.descripcion as estado
                             FROM matricula m
                             JOIN grado g ON m.idGrado = g.idgrado
                             JOIN grupo gr ON m.idGrupo = gr.idGrupo
                             JOIN sedes s ON m.idSede = s.codigoDane
                             JOIN estado_actual ea ON m.idEstadoActual = ea.idEstado
                             WHERE m.idEstudiante = $codigo ORDER BY m.anio DESC LIMIT 1");
        $estudiante_datos['matricula'] = $mat ? $mat->fetch_assoc() : null;
    } else {
        $error = "No se encontró estudiante con código $codigo.";
    }
}

// MATRICULAR
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'matricular') {
    $id_est = (int)$_POST['idEstudiante'];
    $anio = (int)$_POST['anio'];
    $sede = (int)$_POST['idSede'];
    $grado = (int)$_POST['idGrado'];
    $grupo = (int)$_POST['idGrupo'];
    $fecha = sanitize($_POST['fecha_matricula']);
    $inst_proc = sanitize($_POST['instProcedencia']);

    $check = $conn->query("SELECT * FROM matricula WHERE idEstudiante=$id_est AND anio=$anio");
    if ($check->num_rows > 0) {
        // Actualizar
        $conn->query("UPDATE matricula SET idSede=$sede, idGrado=$grado, idGrupo=$grupo,
                      fecha_matricula='$fecha', idEstadoActual=1, institucionProcedencia='$inst_proc'
                      WHERE idEstudiante=$id_est AND anio=$anio");
        $msg = "Matrícula actualizada.";
    } else {
        $conn->query("INSERT INTO matricula (idEstudiante, anio, idSede, idGrado, idGrupo,
                      idEstadoActual, fecha_matricula, institucionProcedencia)
                      VALUES ($id_est, $anio, $sede, $grado, $grupo, 1, '$fecha', '$inst_proc')");
        $msg = "Estudiante matriculado exitosamente en $anio.";
    }
}

// RETIRAR
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'retirar') {
    $id_est = (int)$_POST['idEstudiante'];
    $anio = (int)$_POST['anio'];
    $fecha_ret = sanitize($_POST['fecha_retiro']);
    $motivo = sanitize($_POST['motivo_retiro']);

    $conn->query("UPDATE matricula SET fecha_retiro='$fecha_ret', motivo_retiro='$motivo', idEstadoActual=2
                  WHERE idEstudiante=$id_est AND anio=$anio");
    $msg = "Estudiante retirado del sistema.";
}

$sedes = $conn->query("SELECT * FROM sedes ORDER BY nombre");
$grados = $conn->query("SELECT * FROM grado ORDER BY idgrado");

include '../includes/header.php';
?>

<div class="page-header">
    <div class="page-title">📋 Matrícula de Estudiantes</div>
    <div class="page-subtitle">Matricular estudiantes nuevos y gestionar retiros</div>
</div>

<?php if ($msg): ?><div class="alert alert-success">✅ <?= $msg ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-error">⚠️ <?= $error ?></div><?php endif; ?>

<!-- Buscar estudiante -->
<div class="card">
    <div class="card-header">🔍 Buscar Estudiante</div>
    <div class="card-body">
        <form method="POST">
            <input type="hidden" name="action" value="buscar_est">
            <div style="display:flex; gap:12px; align-items:flex-end">
                <div class="form-group" style="flex:1">
                    <label>Código del Estudiante</label>
                    <input type="number" name="codigo_est" placeholder="Ej: 14189" required
                           value="<?= htmlspecialchars($_POST['codigo_est'] ?? '') ?>">
                </div>
                <button type="submit" class="btn btn-secondary">🔍 Buscar</button>
            </div>
        </form>
    </div>
</div>

<?php if ($estudiante_datos): ?>
<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">

<!-- Datos del estudiante -->
<div class="card">
    <div class="card-header">👤 Datos del Estudiante</div>
    <div class="card-body">
        <table style="width:100%">
            <tr><td style="color:var(--texto-suave); padding:4px 0">Código:</td><td><strong><?= $estudiante_datos['identificacion'] ?></strong></td></tr>
            <tr><td style="color:var(--texto-suave); padding:4px 0">Nombre:</td><td><?= htmlspecialchars($estudiante_datos['primerNombre'] . ' ' . $estudiante_datos['primerApellido']) ?></td></tr>
            <tr><td style="color:var(--texto-suave); padding:4px 0">Documento:</td><td><?= htmlspecialchars($estudiante_datos['documento'] ?? '—') ?></td></tr>
            <?php if ($estudiante_datos['matricula']): ?>
            <tr><td colspan="2"><hr style="border-color:var(--crema-oscuro); margin:8px 0"></td></tr>
            <tr><td style="color:var(--texto-suave); padding:4px 0">Grado actual:</td><td><?= htmlspecialchars($estudiante_datos['matricula']['grado_desc']) ?></td></tr>
            <tr><td style="color:var(--texto-suave); padding:4px 0">Grupo:</td><td><?= htmlspecialchars($estudiante_datos['matricula']['grupo_nombre']) ?></td></tr>
            <tr><td style="color:var(--texto-suave); padding:4px 0">Sede:</td><td><?= htmlspecialchars($estudiante_datos['matricula']['sede_nombre']) ?></td></tr>
            <tr><td style="color:var(--texto-suave); padding:4px 0">Estado:</td>
                <td><span class="badge-estado badge-<?= strtolower($estudiante_datos['matricula']['estado']) ?>"><?= $estudiante_datos['matricula']['estado'] ?></span></td></tr>
            <?php endif; ?>
        </table>
    </div>
</div>

<!-- Formulario matricula -->
<div>
<div class="card">
    <div class="card-header">📝 Matrícula</div>
    <div class="card-body">
        <form method="POST">
            <input type="hidden" name="action" value="matricular">
            <input type="hidden" name="idEstudiante" value="<?= $estudiante_datos['identificacion'] ?>">

            <div class="form-grid">
                <div class="form-group">
                    <label>Año <span class="req">*</span></label>
                    <input type="number" name="anio" value="<?= $anio_actual ?>" required>
                </div>
                <div class="form-group">
                    <label>Sede <span class="req">*</span></label>
                    <select name="idSede" required onchange="cargarGruposMat()">
                        <option value="">— Seleccionar —</option>
                        <?php
                        $sedes->data_seek(0);
                        while ($s = $sedes->fetch_assoc()):
                        ?>
                        <option value="<?= $s['codigoDane'] ?>"><?= htmlspecialchars($s['nombre']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Grado <span class="req">*</span></label>
                    <select name="idGrado" id="sel_grado_mat" required onchange="cargarGruposMat()">
                        <option value="">— Seleccionar —</option>
                        <?php
                        $grados->data_seek(0);
                        while ($g = $grados->fetch_assoc()):
                        ?>
                        <option value="<?= $g['idgrado'] ?>"><?= htmlspecialchars($g['descripcion']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Grupo <span class="req">*</span></label>
                    <select name="idGrupo" id="sel_grupo_mat" required>
                        <option value="">— Seleccionar Sede/Grado —</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Fecha de Matrícula <span class="req">*</span></label>
                    <input type="date" name="fecha_matricula" value="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="form-group">
                    <label>Institución de Procedencia</label>
                    <input type="text" name="instProcedencia" value="<?= htmlspecialchars($estudiante_datos['instProcedencia'] ?? '') ?>">
                </div>
            </div>

            <div class="btn-toolbar mt-2">
                <button class="btn btn-primary">💾 Matricular</button>
            </div>
        </form>
    </div>
</div>

<!-- Retirar -->
<?php if ($estudiante_datos['matricula']): ?>
<div class="card mt-2">
    <div class="card-header" style="background: linear-gradient(to right, #922b21, #c0392b)">⛔ Retiro del Estudiante</div>
    <div class="card-body">
        <form method="POST">
            <input type="hidden" name="action" value="retirar">
            <input type="hidden" name="idEstudiante" value="<?= $estudiante_datos['identificacion'] ?>">
            <input type="hidden" name="anio" value="<?= $anio_actual ?>">
            <div class="form-grid">
                <div class="form-group">
                    <label>Fecha de Retiro</label>
                    <input type="date" name="fecha_retiro" value="<?= date('Y-m-d') ?>">
                </div>
                <div class="form-group">
                    <label>Motivo</label>
                    <select name="motivo_retiro">
                        <option value="Traslado">Traslado</option>
                        <option value="Deserción">Deserción</option>
                        <option value="Otro">Otro</option>
                    </select>
                </div>
            </div>
            <button class="btn btn-danger mt-1" onclick="return confirm('¿Confirmar retiro del estudiante?')">⛔ Retirar Estudiante</button>
        </form>
    </div>
</div>
<?php endif; ?>
</div>

</div><!-- grid -->
<?php endif; ?>

<script src="../js/cargar_grupos.js"></script>

<?php include '../includes/footer.php'; ?>
