<?php
$pageTitle = 'Asignar Materias a Docentes';
require_once '../includes/config.php';
requireAuth([1]);

$msg = '';
$error = '';
$anio_actual = date('Y');

// GUARDAR asignación
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'asignar') {
    $docente = (int)$_POST['idDocente'];
    $sede = (int)$_POST['idSede'];
    $grupo = (int)$_POST['idGrupo'];
    $grado = (int)$_POST['idGrado'];
    $anio = (int)$_POST['anio'];
    $materias_sel = $_POST['materias'] ?? [];

    $insertados = 0;
    $duplicados = 0;

    foreach ($materias_sel as $materia) {
        $materia = (int)$materia;
        // Verificar si ya hay otro docente con esa asignación
        $check = $conn->query("SELECT idDocente FROM horario WHERE idMateria=$materia AND idgrupo=$grupo AND año=$anio AND idDocente != $docente");
        if ($check->num_rows > 0) {
            $duplicados++;
            continue;
        }

        $check2 = $conn->query("SELECT idDocente FROM horario WHERE idDocente=$docente AND idMateria=$materia AND idgrupo=$grupo AND año=$anio");
        if ($check2->num_rows > 0) {
            $duplicados++;
            continue;
        }

        if ($conn->query("INSERT INTO horario (idDocente, idMateria, idgrupo, año, idgrado, idsede)
                          VALUES ($docente, $materia, $grupo, $anio, $grado, $sede)")) {
            $insertados++;
        }
    }

    if ($insertados > 0) $msg = "$insertados materia(s) asignada(s) correctamente.";
    if ($duplicados > 0) $msg .= ($msg ? ' ' : '') . "$duplicados ya existían o tienen conflicto.";
}

// ELIMINAR asignación
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'eliminar') {
    $docente = (int)$_POST['idDocente'];
    $materia = (int)$_POST['idMateria'];
    $grupo = (int)$_POST['idGrupo'];
    $anio = (int)$_POST['anio'];

    if ($conn->query("DELETE FROM horario WHERE idDocente=$docente AND idMateria=$materia AND idgrupo=$grupo AND año=$anio")) {
        $msg = "Asignación eliminada.";
    } else {
        $error = "Error al eliminar.";
    }
}

// CARGAR horario actual del docente seleccionado
$docente_sel = (int)($_POST['docente_ver'] ?? $_GET['docente'] ?? 0);
$horario_docente = null;
if ($docente_sel) {
    $horario_docente = $conn->query("SELECT h.*, m.nombre as materia, g.descripcion as grado_desc,
                                    gr.nombre as grupo_nombre, s.nombre as sede_nombre
                                    FROM horario h
                                    JOIN materias m ON h.idMateria = m.idMateria
                                    JOIN grado g ON h.idgrado = g.idgrado
                                    JOIN grupo gr ON h.idgrupo = gr.idGrupo
                                    JOIN sedes s ON h.idsede = s.codigoDane
                                    WHERE h.idDocente = $docente_sel AND h.año = $anio_actual
                                    ORDER BY g.idgrado, gr.nombre, m.nombre");
}

// Datos para selects
$docentes = $conn->query("SELECT d.idDocentes, CONCAT(p.primerNombre, ' ', p.primerApellido) as nombre
                          FROM docentes d JOIN personas p ON d.idDocentes = p.identificacion
                          ORDER BY p.primerApellido");
$sedes = $conn->query("SELECT * FROM sedes ORDER BY nombre");
$materias = $conn->query("SELECT * FROM materias ORDER BY nombre");

include '../includes/header.php';
?>

<div class="page-header">
    <div class="page-title">📚 Asignar Materias a Docentes</div>
    <div class="page-subtitle">Administrar el horario académico de cada docente</div>
</div>

<?php if ($msg): ?><div class="alert alert-success">✅ <?= $msg ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-error">⚠️ <?= $error ?></div><?php endif; ?>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">

<!-- Formulario asignación -->
<div class="card">
    <div class="card-header">📋 Asignar Horario al Docente</div>
    <div class="card-body">
        <form method="POST">
            <input type="hidden" name="action" value="asignar">

            <div class="form-group mb-2">
                <label>Docente <span class="req">*</span></label>
                <select name="idDocente" id="sel_docente" required onchange="this.form.submit()">
                    <option value="">— Seleccionar Docente —</option>
                    <?php while ($d = $docentes->fetch_assoc()): ?>
                    <option value="<?= $d['idDocentes'] ?>"
                        <?= $docente_sel == $d['idDocentes'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($d['nombre']) ?>
                    </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="form-group mb-2">
                <label>Asignar Nuevas Materias</label>
                <div style="font-size:12px; color:var(--texto-suave); margin-bottom:6px">
                    Ctrl+clic para selección múltiple no consecutiva, Shift+clic para consecutiva
                </div>
                <select name="materias[]" multiple size="8" style="height:200px">
                    <?php
                    $materias->data_seek(0);
                    while ($m = $materias->fetch_assoc()):
                    ?>
                    <option value="<?= $m['idMateria'] ?>"><?= htmlspecialchars($m['nombre']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="form-grid">
                <div class="form-group">
                    <label>Sede <span class="req">*</span></label>
                    <select name="idSede" id="sel_sede" required onchange="cargarGrupos()">
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
                    <select name="idGrado" id="sel_grado_asig" required onchange="cargarGrupos()">
                        <option value="">— Seleccionar —</option>
                        <?php
                        $r_g = $conn->query("SELECT * FROM grado ORDER BY idgrado");
                        while ($g = $r_g->fetch_assoc()):
                        ?>
                        <option value="<?= $g['idgrado'] ?>"><?= htmlspecialchars($g['descripcion']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Grupo <span class="req">*</span></label>
                    <select name="idGrupo" id="sel_grupo_asig" required>
                        <option value="">— Primero seleccione sede y grado —</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Año</label>
                    <input type="number" name="anio" value="<?= $anio_actual ?>" required>
                </div>
            </div>

            <div class="btn-toolbar mt-2">
                <button type="submit" class="btn btn-primary">💾 Asignar Materias</button>
            </div>
        </form>
    </div>
</div>

<!-- Horario actual del docente -->
<div class="card">
    <div class="card-header">📊 Horario Actual del Docente</div>
    <div class="card-body" style="padding:0">
        <?php if (!$docente_sel): ?>
        <div style="padding:20px; text-align:center; color:var(--texto-suave)">
            Seleccione un docente para ver su horario
        </div>
        <?php elseif ($horario_docente && $horario_docente->num_rows > 0): ?>
        <table>
            <thead>
                <tr><th>Asignatura</th><th>Grado</th><th>Grupo</th><th>Sede</th><th></th></tr>
            </thead>
            <tbody>
                <?php while ($h = $horario_docente->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($h['materia']) ?></td>
                    <td><?= htmlspecialchars($h['grado_desc']) ?></td>
                    <td><?= htmlspecialchars($h['grupo_nombre']) ?></td>
                    <td><?= htmlspecialchars($h['sede_nombre']) ?></td>
                    <td>
                        <form method="POST" style="display:inline">
                            <input type="hidden" name="action" value="eliminar">
                            <input type="hidden" name="idDocente" value="<?= $docente_sel ?>">
                            <input type="hidden" name="idMateria" value="<?= $h['idMateria'] ?>">
                            <input type="hidden" name="idGrupo" value="<?= $h['idgrupo'] ?>">
                            <input type="hidden" name="anio" value="<?= $h['año'] ?>">
                            <button type="submit" class="btn btn-sm btn-danger"
                                    onclick="return confirm('¿Eliminar esta asignación?')">🗑️</button>
                        </form>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div style="padding:20px; text-align:center; color:var(--texto-suave)">
            Este docente no tiene materias asignadas en <?= $anio_actual ?>
        </div>
        <?php endif; ?>
    </div>
</div>

</div>

<script>
function cargarGrupos() {
    const sede = document.getElementById('sel_sede').value;
    const grado = document.getElementById('sel_grado_asig').value;
    const sel_grupo = document.getElementById('sel_grupo_asig');

    if (!sede || !grado) {
        sel_grupo.innerHTML = '<option value="">— Primero seleccione sede y grado —</option>';
        return;
    }

    fetch(`/ajax/get_grupos.php?sede=${sede}&grado=${grado}`)
        .then(r => r.json())
        .then(data => {
            sel_grupo.innerHTML = '<option value="">— Seleccionar Grupo —</option>';
            data.forEach(g => {
                sel_grupo.innerHTML += `<option value="${g.idGrupo}">${g.nombre}</option>`;
            });
        });
}
</script>

<?php include '../includes/footer.php'; ?>
