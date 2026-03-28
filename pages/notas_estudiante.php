<?php
$pageTitle = 'Notas del Estudiante';
require_once '../includes/config.php';
requireAuth([1, 2, 3]);

$anio_actual = date('Y');
$notas = null;
$estudiante_info = null;

// Si es estudiante, código fijo
$codigo_fijo = isEstudiante() ? $_SESSION['identificacion'] : null;

$codigo = (int)($_POST['codigo'] ?? $codigo_fijo ?? 0);
$grado = (int)($_POST['idGrado'] ?? 0);

if ($codigo && $grado) {
    // Info del estudiante
    $r = $conn->query("SELECT p.primerNombre, p.primerApellido, p.identificacion,
                       g.descripcion as grado_desc
                       FROM personas p, grado g
                       WHERE p.identificacion = $codigo AND g.idgrado = $grado");
    $estudiante_info = $r ? $r->fetch_assoc() : null;

    if ($estudiante_info) {
        // Notas por materia
        $notas = $conn->query("SELECT n.*, m.nombre as materia_nombre,
                               d.nombre as desempenio_nombre, d.letra as desempenio_letra
                               FROM nota n
                               JOIN materias m ON n.idmateria = m.idMateria
                               LEFT JOIN desempenio d ON n.idDesempeño = d.idDesempenio
                               WHERE n.idEstudiante = $codigo AND n.idgrado = $grado
                               AND n.anio = $anio_actual
                               ORDER BY m.nombre, n.num_informe");
    }
}

$grados = $conn->query("SELECT * FROM grado ORDER BY idgrado");

include '../includes/header.php';
?>

<div class="page-header">
    <div class="page-title">📊 Notas del Estudiante</div>
    <div class="page-subtitle">Calificaciones por asignatura y unidad</div>
</div>

<div class="card">
    <div class="card-header">🔍 Consultar Notas</div>
    <div class="card-body">
        <form method="POST">
            <div style="display:flex; gap:12px; align-items:flex-end; flex-wrap:wrap">
                <?php if (!isEstudiante()): ?>
                <div class="form-group">
                    <label>Código del Estudiante</label>
                    <input type="number" name="codigo" value="<?= $codigo ?: '' ?>" placeholder="Ej: 14189" style="width:180px">
                </div>
                <?php else: ?>
                <input type="hidden" name="codigo" value="<?= $codigo_fijo ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label>Grado</label>
                    <select name="idGrado">
                        <option value="">— Seleccionar —</option>
                        <?php
                        while ($g = $grados->fetch_assoc()):
                        ?>
                        <option value="<?= $g['idgrado'] ?>" <?= $grado == $g['idgrado'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($g['descripcion']) ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-secondary" style="margin-bottom:1px">🔍 Buscar</button>
            </div>
        </form>
    </div>
</div>

<?php if ($estudiante_info && $notas !== null): ?>
<!-- Header del reporte -->
<div class="card">
    <div class="card-header">
        📋 Calificación de Estudiantes — <?= $anio_actual ?>
    </div>
    <div class="card-body">
        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px">
            <div>
                <div style="font-size:12px; color:var(--texto-suave)">Código del Estudiante</div>
                <div style="font-weight:700; font-size:18px"><?= $estudiante_info['identificacion'] ?></div>
            </div>
            <div>
                <div style="font-size:12px; color:var(--texto-suave)">Estudiante</div>
                <div style="font-weight:600"><?= htmlspecialchars($estudiante_info['primerNombre'] . ' ' . $estudiante_info['primerApellido']) ?></div>
            </div>
            <div>
                <div style="font-size:12px; color:var(--texto-suave)">Grado</div>
                <div style="font-weight:600"><?= htmlspecialchars($estudiante_info['grado_desc']) ?></div>
            </div>
            <a href="/pages/reporte_pdf.php?codigo=<?= $codigo ?>&grado=<?= $grado ?>"
               class="btn btn-secondary" target="_blank">
                📄 Imprimir PDF
            </a>
        </div>
    </div>
</div>

<!-- Tabla de notas -->
<?php
$materias_notas = [];
if ($notas->num_rows > 0) {
    while ($n = $notas->fetch_assoc()) {
        $materias_notas[$n['materia_nombre']][$n['num_informe']] = $n;
    }
}
?>

<div class="card">
    <div class="card-header">📚 Calificaciones por Asignatura</div>
    <div class="card-body" style="padding:0; overflow-x:auto">
        <?php if (empty($materias_notas)): ?>
        <div style="padding:20px; text-align:center; color:var(--texto-suave)">
            No hay notas registradas para este estudiante en <?= $anio_actual ?>
        </div>
        <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Asignatura</th>
                    <th>U1</th><th>U2</th><th>U3</th><th>U4</th><th>U5</th>
                    <th>U6</th><th>U7</th><th>U8</th><th>U9</th><th>U10</th>
                    <th>Logros</th>
                    <th>Desempeño</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($materias_notas as $materia => $informes): ?>
                <?php $n = reset($informes); // Tomar la primera entrada ?>
                <tr>
                    <td><strong><?= htmlspecialchars($materia) ?></strong></td>
                    <?php for ($u = 1; $u <= 10; $u++): ?>
                    <td class="text-center">
                        <?php
                        $val = $n["Unidad$u"] ?? null;
                        if ($val !== null) {
                            $clase = '';
                            if ($val < 3) $clase = 'nota-bajo';
                            elseif ($val < 4) $clase = 'nota-basico';
                            elseif ($val < 4.6) $clase = 'nota-alto';
                            else $clase = 'nota-superior';
                            echo "<span class='$clase'>" . number_format($val/10, 1) . "</span>";
                        } else {
                            echo '<span style="color:#ccc">—</span>';
                        }
                        ?>
                    </td>
                    <?php endfor; ?>
                    <td class="text-center">
                        <?= $n['Logro_1'] ?? '—' ?>
                    </td>
                    <td class="text-center">
                        <?php if ($n['desempenio_letra']): ?>
                        <strong><?= $n['desempenio_letra'] ?></strong> — <?= $n['desempenio_nombre'] ?>
                        <?php else: ?>—<?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

<!-- Leyenda -->
<div class="card">
    <div class="card-body">
        <div style="display:flex; gap:24px; flex-wrap:wrap; font-size:12px">
            <span><span class="nota-bajo">B</span> — Bajo (1.0–2.9)</span>
            <span><span class="nota-basico">S</span> — Básico (3.0–3.9)</span>
            <span><span class="nota-alto">A</span> — Alto (4.0–4.5)</span>
            <span><span class="nota-superior">E</span> — Superior (4.6–5.0)</span>
        </div>
    </div>
</div>

<?php endif; ?>

<?php include '../includes/footer.php'; ?>
