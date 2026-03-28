<?php
$pageTitle = 'Listar Estudiantes';
require_once '../includes/config.php';
requireAuth([1, 2]);

$msg = '';
$lista_estudiantes = null;
$anio_actual = date('Y');

$grado_sel = (int)($_POST['idGrado'] ?? $_GET['grado'] ?? 0);
$grupo_sel = (int)($_POST['idGrupo'] ?? $_GET['grupo'] ?? 0);

if ($grado_sel && $grupo_sel) {
    $lista_estudiantes = $conn->query("SELECT p.identificacion, p.primerNombre, p.primerApellido, p.segundoApellido,
                                       p.primerNombre as nombre, p.documento, p.email, p.telefono,
                                       ea.descripcion as estado
                                       FROM matricula m
                                       JOIN personas p ON m.idEstudiante = p.identificacion
                                       JOIN estado_actual ea ON m.idEstadoActual = ea.idEstado
                                       WHERE m.idGrado = $grado_sel AND m.idGrupo = $grupo_sel
                                       AND m.anio = $anio_actual
                                       ORDER BY p.primerApellido, p.primerNombre");
}

$grados = $conn->query("SELECT * FROM grado ORDER BY idgrado");

include '../includes/header.php';
?>

<div class="page-header">
    <div class="page-title">👥 Listar Estudiantes</div>
    <div class="page-subtitle">Ver estudiantes matriculados por grado y grupo</div>
</div>

<div class="card">
    <div class="card-header">🔍 Filtrar Estudiantes</div>
    <div class="card-body">
        <form method="POST">
            <div style="display:flex; gap:12px; align-items:flex-end; flex-wrap:wrap">
                <div class="form-group" style="min-width:160px">
                    <label>Grado</label>
                    <select name="idGrado" id="sel_grado_list" onchange="cargarGruposLista()">
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
                    <label>Grupo</label>
                    <select name="idGrupo" id="sel_grupo_list">
                        <?php if ($grupo_sel): ?>
                        <option value="<?= $grupo_sel ?>" selected>Grupo actual</option>
                        <?php else: ?>
                        <option value="">— Seleccionar Grado —</option>
                        <?php endif; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-secondary" style="margin-bottom:1px">🔍 Buscar</button>
            </div>
        </form>
    </div>
</div>

<?php if ($lista_estudiantes !== null): ?>
<div class="card">
    <div class="card-header">
        📋 Listado de Estudiantes — <?= $anio_actual ?>
        <span class="badge"><?= $lista_estudiantes->num_rows ?></span>
    </div>
    <div class="card-body" style="padding:0">
        <table>
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Apellidos</th>
                    <th>Nombres</th>
                    <th>Documento</th>
                    <th>Estado</th>
                    <?php if (isAdmin()): ?>
                    <th>Acción</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php if ($lista_estudiantes->num_rows === 0): ?>
                <tr><td colspan="6" class="text-center" style="padding:20px; color:var(--texto-suave)">
                    No hay estudiantes en este grupo
                </td></tr>
                <?php else: ?>
                <?php while ($est = $lista_estudiantes->fetch_assoc()): ?>
                <tr>
                    <td><strong><?= $est['identificacion'] ?></strong></td>
                    <td><?= htmlspecialchars($est['primerApellido'] . ' ' . ($est['segundoApellido'] ?? '')) ?></td>
                    <td><?= htmlspecialchars($est['primerNombre']) ?></td>
                    <td><?= htmlspecialchars($est['documento'] ?? '—') ?></td>
                    <td><span class="badge-estado badge-<?= strtolower($est['estado']) ?>"><?= $est['estado'] ?></span></td>
                    <?php if (isAdmin()): ?>
                    <td>
                        <a href="/pages/personas.php?editar=<?= $est['identificacion'] ?>"
                           class="btn btn-sm btn-outline">✏️ Modificar</a>
                    </td>
                    <?php endif; ?>
                </tr>
                <?php endwhile; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<script>
function cargarGruposLista() {
    const grado = document.getElementById('sel_grado_list').value;
    const sel = document.getElementById('sel_grupo_list');
    if (!grado) { sel.innerHTML = '<option value="">— Seleccionar Grado —</option>'; return; }
    fetch(`/ajax/get_grupos.php?grado=${grado}`)
        .then(r => r.json())
        .then(data => {
            sel.innerHTML = '<option value="">— Seleccionar Grupo —</option>';
            data.forEach(g => sel.innerHTML += `<option value="${g.idGrupo}">${g.nombre} (${g.sede})</option>`);
        });
}
if (document.getElementById('sel_grado_list').value) cargarGruposLista();
</script>

<?php include '../includes/footer.php'; ?>
