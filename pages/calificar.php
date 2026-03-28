<?php
$pageTitle = 'Calificación de Estudiantes';
require_once '../includes/config.php';
requireAuth([1, 2]);

$msg = '';
$error = '';
$anio_actual = date('Y');
$estudiantes_cal = null;
$logros_materia = null;

// Filtros
$docente_sel = isAdmin() ? (int)($_POST['idDocente'] ?? 0) : (int)$_SESSION['identificacion'];
$grado_sel = (int)($_POST['idGrado'] ?? 0);
$grupo_sel = (int)($_POST['idGrupo'] ?? 0);
$materia_sel = (int)($_POST['idMateria'] ?? 0);
$unidad_sel = (int)($_POST['idUnidad'] ?? 1);
$informe_sel = (int)($_POST['informe'] ?? 1);
$anio_sel = (int)($_POST['anio'] ?? $anio_actual);

// GUARDAR calificaciones
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'guardar_notas') {
    $ids = $_POST['id_est'] ?? [];
    $unidades = $_POST['unidad'] ?? [];
    $logros1 = $_POST['logro1'] ?? [];
    $logros2 = $_POST['logro2'] ?? [];
    $logros3 = $_POST['logro3'] ?? [];
    $desempenos = $_POST['desempenio'] ?? [];
    $observaciones = $_POST['observacion'] ?? [];

    foreach ($ids as $i => $id_est) {
        $id_est = (int)$id_est;
        $unidad_val = (int)($unidades[$i] ?? 0);
        $logro1_val = !empty($logros1[$i]) ? (int)$logros1[$i] : 'NULL';
        $logro2_val = !empty($logros2[$i]) ? (int)$logros2[$i] : 'NULL';
        $logro3_val = !empty($logros3[$i]) ? (int)$logros3[$i] : 'NULL';
        $desemp_val = !empty($desempenos[$i]) ? (int)$desempenos[$i] : 'NULL';
        $obs_val = sanitize($observaciones[$i] ?? '');

        // Verificar si ya existe la nota
        $check = $conn->query("SELECT idEstudiante FROM nota WHERE idEstudiante=$id_est AND idmateria=$materia_sel AND idgrado=$grado_sel AND num_informe=$informe_sel AND anio=$anio_sel");

        $unidad_col = "Unidad$unidad_sel";

        if ($check && $check->num_rows > 0) {
            $conn->query("UPDATE nota SET $unidad_col=$unidad_val,
                         Logro_1=$logro1_val, Logro_2=$logro2_val, Logro_3=$logro3_val,
                         idDesempeño=$desemp_val, descripcion='$obs_val'
                         WHERE idEstudiante=$id_est AND idmateria=$materia_sel
                         AND idgrado=$grado_sel AND num_informe=$informe_sel AND anio=$anio_sel");
        } else {
            $conn->query("INSERT INTO nota (idEstudiante, num_informe, idmateria, idgrado, anio, $unidad_col, Logro_1, Logro_2, Logro_3, idDesempeño, descripcion)
                         VALUES ($id_est, $informe_sel, $materia_sel, $grado_sel, $anio_sel, $unidad_val, $logro1_val, $logro2_val, $logro3_val, $desemp_val, '$obs_val')");
        }
    }
    $msg = "Calificaciones guardadas exitosamente.";
}

// CARGAR estudiantes y notas
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $grado_sel && $grupo_sel && $materia_sel) {
    $estudiantes_cal = $conn->query("SELECT p.identificacion, p.primerNombre, p.primerApellido,
                                     n.Unidad1, n.Unidad2, n.Unidad3, n.Unidad4, n.Unidad5,
                                     n.Logro_1, n.Logro_2, n.Logro_3, n.idDesempeño, n.descripcion
                                     FROM matricula m
                                     JOIN personas p ON m.idEstudiante = p.identificacion
                                     LEFT JOIN nota n ON (n.idEstudiante = p.identificacion
                                         AND n.idmateria = $materia_sel AND n.idgrado = $grado_sel
                                         AND n.num_informe = $informe_sel AND n.anio = $anio_sel)
                                     WHERE m.idGrado = $grado_sel AND m.idGrupo = $grupo_sel
                                     AND m.anio = $anio_sel AND m.idEstadoActual = 1
                                     ORDER BY p.primerApellido, p.primerNombre");

    // Logros de la materia y grado seleccionado
    $logros_materia = $conn->query("SELECT idlogro, descripcion, unidad FROM logros
                                    WHERE idMateria = $materia_sel AND idGrado = $grado_sel
                                    ORDER BY unidad, idlogro");
}

// Datos para selects
$docentes = isAdmin() ? $conn->query("SELECT d.idDocentes, CONCAT(p.primerNombre, ' ', p.primerApellido) as nombre
                                      FROM docentes d JOIN personas p ON d.idDocentes = p.identificacion
                                      ORDER BY p.primerApellido") : null;
$grados = $conn->query("SELECT * FROM grado ORDER BY idgrado");
$desempenos = $conn->query("SELECT * FROM desempenio ORDER BY minimo");

// Materias disponibles para el docente
if ($docente_sel) {
    $materias_doc = $conn->query("SELECT DISTINCT m.idMateria, m.nombre FROM horario h
                                  JOIN materias m ON h.idMateria = m.idMateria
                                  WHERE h.idDocente = $docente_sel AND h.año = $anio_sel
                                  ORDER BY m.nombre");
} else {
    $materias_doc = $conn->query("SELECT * FROM materias ORDER BY nombre");
}

include '../includes/header.php';
?>

<div class="page-header">
    <div class="page-title">✏️ Calificación de Estudiantes</div>
    <div class="page-subtitle">Ingresar y modificar calificaciones por grupo y asignatura</div>
</div>

<?php if ($msg): ?><div class="alert alert-success">✅ <?= $msg ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-error">⚠️ <?= $error ?></div><?php endif; ?>

<!-- Filtros -->
<div class="card">
    <div class="card-header">📋 Seleccionar Grupo a Calificar</div>
    <div class="card-body">
        <form method="POST" id="form_filtros">
            <input type="hidden" name="action" value="filtrar">
            <div class="form-grid">
                <?php if (isAdmin()): ?>
                <div class="form-group">
                    <label>Docente <span class="req">*</span></label>
                    <select name="idDocente" onchange="document.getElementById('form_filtros').submit()">
                        <option value="">— Seleccionar Docente —</option>
                        <?php while ($d = $docentes->fetch_assoc()): ?>
                        <option value="<?= $d['idDocentes'] ?>" <?= $docente_sel == $d['idDocentes'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($d['nombre']) ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <?php else: ?>
                <input type="hidden" name="idDocente" value="<?= $docente_sel ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label>Grado <span class="req">*</span></label>
                    <select name="idGrado" onchange="cargarGruposCalif()">
                        <option value="">— Seleccionar —</option>
                        <?php while ($g = $grados->fetch_assoc()): ?>
                        <option value="<?= $g['idgrado'] ?>" <?= $grado_sel == $g['idgrado'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($g['descripcion']) ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Grupo <span class="req">*</span></label>
                    <select name="idGrupo" id="sel_grupo_cal">
                        <?php if ($grupo_sel): ?>
                        <option value="<?= $grupo_sel ?>" selected>Grupo actual</option>
                        <?php else: ?>
                        <option value="">— Seleccionar Grupo —</option>
                        <?php endif; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Asignatura <span class="req">*</span></label>
                    <select name="idMateria">
                        <option value="">— Seleccionar —</option>
                        <?php if ($materias_doc): while ($m = $materias_doc->fetch_assoc()): ?>
                        <option value="<?= $m['idMateria'] ?>" <?= $materia_sel == $m['idMateria'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($m['nombre']) ?>
                        </option>
                        <?php endwhile; endif; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Unidad</label>
                    <select name="idUnidad">
                        <?php for ($u = 1; $u <= 10; $u++): ?>
                        <option value="<?= $u ?>" <?= $unidad_sel == $u ? 'selected' : '' ?>>Unidad <?= $u ?></option>
                        <?php endfor; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Informe (Reporte)</label>
                    <select name="informe">
                        <?php for ($inf = 1; $inf <= 4; $inf++): ?>
                        <option value="<?= $inf ?>" <?= $informe_sel == $inf ? 'selected' : '' ?>><?= $inf ?></option>
                        <?php endfor; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Año</label>
                    <input type="number" name="anio" value="<?= $anio_sel ?>" style="width:100px">
                </div>
            </div>

            <div class="btn-toolbar mt-2">
                <button type="submit" class="btn btn-secondary">🔍 Buscar Estudiantes</button>
            </div>
        </form>
    </div>
</div>

<!-- Tabla de calificación -->
<?php if ($estudiantes_cal !== null): ?>
<form method="POST">
    <input type="hidden" name="action" value="guardar_notas">
    <input type="hidden" name="idGrado" value="<?= $grado_sel ?>">
    <input type="hidden" name="idGrupo" value="<?= $grupo_sel ?>">
    <input type="hidden" name="idMateria" value="<?= $materia_sel ?>">
    <input type="hidden" name="idUnidad" value="<?= $unidad_sel ?>">
    <input type="hidden" name="informe" value="<?= $informe_sel ?>">
    <input type="hidden" name="anio" value="<?= $anio_sel ?>">
    <input type="hidden" name="idDocente" value="<?= $docente_sel ?>">

    <div style="display: grid; grid-template-columns: 1fr 300px; gap: 20px;">

    <!-- Tabla izquierda -->
    <div class="card">
        <div class="card-header">
            ✏️ Calificar — Unidad <?= $unidad_sel ?> — Informe <?= $informe_sel ?>
            <span class="badge"><?= $estudiantes_cal->num_rows ?> estudiantes</span>
        </div>
        <div class="card-body" style="padding:0; overflow-x:auto">
            <table class="tabla-calificacion">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Estudiante</th>
                        <th>Nota U<?= $unidad_sel ?></th>
                        <th>L1</th>
                        <th>L2</th>
                        <th>L3</th>
                        <th>Observaciones</th>
                        <th>Desempeño</th>
                        <th>PDF</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $i = 0;
                    while ($est = $estudiantes_cal->fetch_assoc()):
                    $nota_unidad = $est["Unidad$unidad_sel"] ?? '';
                    ?>
                    <tr>
                        <input type="hidden" name="id_est[]" value="<?= $est['identificacion'] ?>">
                        <td><strong><?= $est['identificacion'] ?></strong></td>
                        <td style="white-space:nowrap"><?= htmlspecialchars($est['primerApellido'] . ', ' . $est['primerNombre']) ?></td>
                        <td>
                            <input type="number" name="unidad[]" value="<?= htmlspecialchars($nota_unidad) ?>"
                                   min="0" max="50" style="width:55px"
                                   onchange="calcularPromedio(this)">
                        </td>
                        <td>
                            <select name="logro1[]" style="min-width:55px">
                                <option value="">—</option>
                                <?php
                                if ($logros_materia) {
                                    $logros_materia->data_seek(0);
                                    while ($l = $logros_materia->fetch_assoc()):
                                ?>
                                <option value="<?= $l['idlogro'] ?>" <?= $est['Logro_1'] == $l['idlogro'] ? 'selected' : '' ?>>
                                    <?= $l['idlogro'] ?>
                                </option>
                                <?php
                                    endwhile;
                                }
                                ?>
                            </select>
                        </td>
                        <td>
                            <select name="logro2[]" style="min-width:55px">
                                <option value="">—</option>
                                <?php
                                if ($logros_materia) {
                                    $logros_materia->data_seek(0);
                                    while ($l = $logros_materia->fetch_assoc()):
                                ?>
                                <option value="<?= $l['idlogro'] ?>" <?= $est['Logro_2'] == $l['idlogro'] ? 'selected' : '' ?>>
                                    <?= $l['idlogro'] ?>
                                </option>
                                <?php
                                    endwhile;
                                }
                                ?>
                            </select>
                        </td>
                        <td>
                            <select name="logro3[]" style="min-width:55px">
                                <option value="">—</option>
                                <?php
                                if ($logros_materia) {
                                    $logros_materia->data_seek(0);
                                    while ($l = $logros_materia->fetch_assoc()):
                                ?>
                                <option value="<?= $l['idlogro'] ?>" <?= $est['Logro_3'] == $l['idlogro'] ? 'selected' : '' ?>>
                                    <?= $l['idlogro'] ?>
                                </option>
                                <?php
                                    endwhile;
                                }
                                ?>
                            </select>
                        </td>
                        <td>
                            <input type="text" name="observacion[]"
                                   value="<?= htmlspecialchars($est['descripcion'] ?? '') ?>"
                                   placeholder="Observaciones..." style="width:160px">
                        </td>
                        <td>
                            <select name="desempenio[]">
                                <option value="">—</option>
                                <?php
                                $desempenos->data_seek(0);
                                while ($des = $desempenos->fetch_assoc()):
                                ?>
                                <option value="<?= $des['idDesempenio'] ?>"
                                    <?= $est['idDesempeño'] == $des['idDesempenio'] ? 'selected' : '' ?>>
                                    <?= $des['letra'] ?> (<?= $des['descripcion'] ?>)
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </td>
                        <td>
                            <a href="/pages/reporte_pdf.php?codigo=<?= $est['identificacion'] ?>&grado=<?= $grado_sel ?>"
                               target="_blank" class="btn btn-sm btn-secondary btn-icon" title="Ver PDF">📄</a>
                        </td>
                    </tr>
                    <?php $i++; endwhile; ?>
                </tbody>
            </table>
        </div>
        <div style="padding:16px">
            <button type="submit" class="btn btn-primary" style="width:100%">
                💾 Guardar Cambios
            </button>
        </div>
    </div>

    <!-- Panel indicadores de logro -->
    <div class="card" style="height:fit-content; position:sticky; top:80px">
        <div class="card-header">📋 Indicadores de Logro</div>
        <div class="card-body" style="font-size:12px; max-height:500px; overflow-y:auto">
            <?php if ($logros_materia): ?>
            <table style="width:100%; font-size:11px">
                <thead>
                    <tr style="background:var(--dorado-suave)">
                        <th style="padding:4px 6px">Código</th>
                        <th style="padding:4px 6px">Descripción</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $logros_materia->data_seek(0);
                    while ($l = $logros_materia->fetch_assoc()):
                    ?>
                    <tr style="border-bottom:1px solid var(--crema-oscuro)">
                        <td style="padding:4px 6px; font-weight:700"><?= $l['idlogro'] ?></td>
                        <td style="padding:4px 6px"><?= htmlspecialchars($l['descripcion']) ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <?php else: ?>
            <p style="color:var(--texto-suave)">No hay logros registrados para esta materia y grado.</p>
            <?php endif; ?>
        </div>
    </div>

    </div>
</form>
<?php endif; ?>

<script src="../js/cargar_grupos.js"></script>

<?php include '../includes/footer.php'; ?>
