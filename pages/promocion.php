<?php
$pageTitle = 'Promoción de Estudiantes';
require_once '../includes/config.php';
requireAuth([1]);

$msg = '';
$error = '';
$anio_actual = date('Y');
$estudiantes_grupo = null;

// Buscar estudiantes del grupo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'buscar') {
    $grado = (int)$_POST['idGrado'];
    $grupo = (int)$_POST['idGrupo'];
    $anio = (int)$_POST['anio'];

    $estudiantes_grupo = $conn->query("SELECT m.idEstudiante, p.primerNombre, p.primerApellido, p.segundoApellido
                                       FROM matricula m
                                       JOIN personas p ON m.idEstudiante = p.identificacion
                                       WHERE m.idGrado = $grado AND m.idGrupo = $grupo
                                       AND m.anio = $anio AND m.idEstadoActual = 1
                                       ORDER BY p.primerApellido, p.primerNombre");
    if (!$estudiantes_grupo || $estudiantes_grupo->num_rows === 0) {
        $error = "No se encontraron estudiantes activos en ese grupo para el año $anio.";
        $estudiantes_grupo = null;
    }
}

// PROMOVER
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'promover') {
    $ids_promover = $_POST['promover'] ?? [];
    $grado_actual = (int)$_POST['grado_actual'];
    $grupo_actual = (int)$_POST['grupo_actual'];
    $anio = (int)$_POST['anio'];
    $anio_nuevo = $anio + 1;

    $grado_siguiente = $grado_actual + 1;

    // Buscar un grupo del grado siguiente en la misma sede
    $sede_r = $conn->query("SELECT idSede FROM grupo WHERE idGrupo = $grupo_actual");
    $sede = $sede_r->fetch_assoc()['idSede'];

    // Si es grado 5 (último), enviar a "Retirados"
    $max_grado_r = $conn->query("SELECT MAX(idgrado) as max FROM grado");
    $max_grado = $max_grado_r->fetch_assoc()['max'];

    if ($grado_actual >= $max_grado) {
        // Buscar grupo de retirados
        $grupo_ret_r = $conn->query("SELECT idGrupo FROM grupo WHERE nombre LIKE '%retir%' AND idSede = $sede LIMIT 1");
        $grupo_siguiente = $grupo_ret_r && $grupo_ret_r->num_rows > 0 ? $grupo_ret_r->fetch_assoc()['idGrupo'] : null;

        if (!$grupo_siguiente) {
            $error = "No existe un grupo de 'Retirados' en la sede. Créelo primero desde Institución > Grupos.";
            goto fin_promocion;
        }
        $grado_promo = $grado_actual;
    } else {
        // Buscar grupo del grado siguiente en misma sede
        $grupo_sig_r = $conn->query("SELECT idGrupo FROM grupo WHERE idgrado = $grado_siguiente AND idSede = $sede LIMIT 1");
        if (!$grupo_sig_r || $grupo_sig_r->num_rows === 0) {
            $error = "No existe un grupo para el grado siguiente en esa sede. Créelo primero.";
            goto fin_promocion;
        }
        $grupo_siguiente = $grupo_sig_r->fetch_assoc()['idGrupo'];
        $grado_promo = $grado_siguiente;
    }

    $promovidos = 0;
    foreach ($ids_promover as $id_est) {
        $id_est = (int)$id_est;
        // Marcar año actual como completado
        $conn->query("UPDATE matricula SET idEstadoActual=3 WHERE idEstudiante=$id_est AND anio=$anio");

        // Crear matrícula año siguiente
        $check = $conn->query("SELECT * FROM matricula WHERE idEstudiante=$id_est AND anio=$anio_nuevo");
        if ($check->num_rows === 0) {
            $conn->query("INSERT INTO matricula (idEstudiante, anio, idSede, idGrado, idGrupo, idEstadoActual, fecha_matricula)
                          VALUES ($id_est, $anio_nuevo, $sede, $grado_promo, $grupo_siguiente, 1, '".date('Y-m-d')."')");
            $promovidos++;
        }
    }

    $msg = "$promovidos estudiante(s) promovidos al año $anio_nuevo.";
    fin_promocion:;
}

$grados = $conn->query("SELECT * FROM grado ORDER BY idgrado");
$grado_sel = (int)($_POST['idGrado'] ?? 0);
$grupo_sel = (int)($_POST['idGrupo'] ?? 0);
$anio_sel = (int)($_POST['anio'] ?? $anio_actual);

include '../includes/header.php';
?>

<div class="page-header">
    <div class="page-title">🎓 Promoción de Estudiantes</div>
    <div class="page-subtitle">Promover grupos completos al grado siguiente</div>
</div>

<?php if ($msg): ?><div class="alert alert-success">✅ <?= $msg ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-error">⚠️ <?= $error ?></div><?php endif; ?>

<div class="alert alert-info">
    ℹ️ Realizar una promoción modifica la matrícula del estudiante al grado siguiente. Los estudiantes de grado 5 son ubicados en el grupo de retirados.
</div>

<!-- Filtros -->
<div class="card">
    <div class="card-header">📋 Promoción de Estudiantes</div>
    <div class="card-body">
        <form method="POST">
            <input type="hidden" name="action" value="buscar">
            <div style="display:flex; gap:12px; align-items:flex-end; flex-wrap:wrap">
                <div class="form-group" style="min-width:160px">
                    <label>Grado <span class="req">*</span></label>
                    <select name="idGrado" id="sel_grado_prom" required onchange="cargarGruposProm()">
                        <option value="">— Seleccionar —</option>
                        <?php
                        while ($g = $grados->fetch_assoc()):
                        ?>
                        <option value="<?= $g['idgrado'] ?>" <?= $grado_sel == $g['idgrado'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($g['descripcion']) ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group" style="min-width:160px">
                    <label>Grupo <span class="req">*</span></label>
                    <select name="idGrupo" id="sel_grupo_prom" required>
                        <option value="<?= $grupo_sel ?>">— Seleccionar Grado primero —</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Año</label>
                    <input type="number" name="anio" value="<?= $anio_sel ?>" style="width:100px">
                </div>
                <button type="submit" class="btn btn-secondary" style="margin-bottom:1px">🔍 Buscar</button>
            </div>
        </form>
    </div>
</div>

<!-- Lista para promover -->
<?php if ($estudiantes_grupo): ?>
<div class="card">
    <div class="card-header">
        ✅ Estudiantes a Promover
        <span class="badge"><?= $estudiantes_grupo->num_rows ?></span>
    </div>
    <div class="card-body">
        <form method="POST">
            <input type="hidden" name="action" value="promover">
            <input type="hidden" name="grado_actual" value="<?= $grado_sel ?>">
            <input type="hidden" name="grupo_actual" value="<?= $grupo_sel ?>">
            <input type="hidden" name="anio" value="<?= $anio_sel ?>">

            <div class="alert alert-warning mb-2">
                Todos los estudiantes aparecen seleccionados por defecto. Desmarque los que NO serán promovidos.
            </div>

            <div class="btn-toolbar mb-2">
                <button type="button" onclick="selAll(true)" class="btn btn-outline btn-sm">☑️ Seleccionar Todos</button>
                <button type="button" onclick="selAll(false)" class="btn btn-outline btn-sm">☐ Deseleccionar Todos</button>
            </div>

            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>✓</th>
                            <th>Código</th>
                            <th>Estudiante</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($est = $estudiantes_grupo->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <input type="checkbox" name="promover[]"
                                       value="<?= $est['idEstudiante'] ?>"
                                       class="cb-promover" checked
                                       style="width:auto; accent-color: var(--verde-medio)">
                            </td>
                            <td><?= $est['idEstudiante'] ?></td>
                            <td><?= htmlspecialchars($est['primerApellido'] . ' ' . ($est['segundoApellido'] ?? '') . ', ' . $est['primerNombre']) ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

            <div class="btn-toolbar mt-2">
                <button type="submit" class="btn btn-primary"
                        onclick="return confirm('¿Confirmar la promoción de los estudiantes seleccionados?')">
                    🎓 Promocionar
                </button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<script>
function selAll(estado) {
    document.querySelectorAll('.cb-promover').forEach(cb => cb.checked = estado);
}

function cargarGruposProm() {
    const grado = document.getElementById('sel_grado_prom').value;
    const sel = document.getElementById('sel_grupo_prom');
    if (!grado) return;
    fetch(`/ajax/get_grupos.php?grado=${grado}`)
        .then(r => r.json())
        .then(data => {
            sel.innerHTML = '<option value="">— Seleccionar Grupo —</option>';
            data.forEach(g => sel.innerHTML += `<option value="${g.idGrupo}">${g.nombre} (${g.sede})</option>`);
        });
}

// Si ya hay grado seleccionado, cargar grupos
const gradoSel = document.getElementById('sel_grado_prom').value;
if (gradoSel) cargarGruposProm();
</script>

<?php include '../includes/footer.php'; ?>
