<?php
$pageTitle = 'Logros e Indicadores';
require_once 'includes/config.php';
requireAuth([1, 2]);

$msg = '';
$error = '';

// GUARDAR logro
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'guardar') {
    $grado = (int)$_POST['idGrado'];
    $materia = (int)$_POST['idMateria'];
    $tipo = (int)$_POST['idtipo'];
    $unidad = (int)$_POST['unidad'];
    $desc = sanitize($_POST['descripcion']);

    if ($conn->query("INSERT INTO logros (idtipo, descripcion, idMateria, idGrado, unidad)
                      VALUES ($tipo, '$desc', $materia, $grado, $unidad)")) {
        $msg = "Logro guardado. El código fue asignado automáticamente por el sistema.";
    } else {
        $error = "Error: " . $conn->error;
    }
}

// MODIFICAR logro
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'modificar') {
    $id = (int)$_POST['idlogro'];
    $desc = sanitize($_POST['descripcion']);
    $unidad = (int)$_POST['unidad'];
    $tipo = (int)$_POST['idtipo'];

    if ($conn->query("UPDATE logros SET descripcion='$desc', unidad=$unidad, idtipo=$tipo WHERE idlogro=$id")) {
        $msg = "Logro actualizado.";
    } else {
        $error = "Error: " . $conn->error;
    }
}

// ELIMINAR logro
if (isset($_GET['eliminar'])) {
    $id = (int)$_GET['eliminar'];
    if ($conn->query("DELETE FROM logros WHERE idlogro = $id")) {
        $msg = "Logro eliminado.";
    } else {
        $error = "No se puede eliminar: está siendo usado en calificaciones.";
    }
}

// Buscar logros
$logros_resultado = null;
$filtro_grado = (int)($_POST['filtro_grado'] ?? $_GET['grado'] ?? 0);
$filtro_materia = (int)($_POST['filtro_materia'] ?? $_GET['materia'] ?? 0);

if ($filtro_grado || $filtro_materia) {
    $where = [];
    if ($filtro_grado) $where[] = "l.idGrado = $filtro_grado";
    if ($filtro_materia) $where[] = "l.idMateria = $filtro_materia";
    $where_sql = 'WHERE ' . implode(' AND ', $where);

    $logros_resultado = $conn->query("SELECT l.*, g.descripcion as grado_desc,
                                     m.nombre as materia_nombre, t.descripcion as tipo_desc
                                     FROM logros l
                                     JOIN grado g ON l.idGrado = g.idgrado
                                     JOIN materias m ON l.idMateria = m.idMateria
                                     JOIN tipologro t ON l.idtipo = t.id
                                     $where_sql ORDER BY l.unidad, l.idlogro");
}

// Cargar para editar
$logro_edit = null;
if (isset($_GET['editar'])) {
    $id = (int)$_GET['editar'];
    $r = $conn->query("SELECT * FROM logros WHERE idlogro = $id");
    $logro_edit = $r ? $r->fetch_assoc() : null;
}

$grados = $conn->query("SELECT * FROM grado ORDER BY idgrado");
$materias = $conn->query("SELECT * FROM materias ORDER BY nombre");
$tipos = $conn->query("SELECT * FROM tipologro");

include '../includes/header.php';
?>

<div class="page-header">
    <div class="page-title">🏆 Logros o Indicadores de Desempeño</div>
    <div class="page-subtitle">Crear y administrar logros académicos por grado y asignatura</div>
</div>

<?php if ($msg): ?><div class="alert alert-success">✅ <?= $msg ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-error">⚠️ <?= $error ?></div><?php endif; ?>

<div style="display: grid; grid-template-columns: 380px 1fr; gap: 20px;">

<!-- Formulario -->
<div>
<div class="card">
    <div class="card-header">
        <?= $logro_edit ? '✏️ Modificar Logro' : '➕ Crear Nuevo Logro' ?>
    </div>
    <div class="card-body">
        <form method="POST">
            <input type="hidden" name="action" value="<?= $logro_edit ? 'modificar' : 'guardar' ?>">
            <?php if ($logro_edit): ?>
            <input type="hidden" name="idlogro" value="<?= $logro_edit['idlogro'] ?>">
            <div class="form-group mb-2">
                <label>Código</label>
                <input type="text" value="<?= $logro_edit['idlogro'] ?>" readonly style="background:#f0f0f0">
            </div>
            <?php endif; ?>

            <div class="form-group mb-2">
                <label>Asignatura <span class="req">*</span></label>
                <select name="idMateria" id="sel_materia" required>
                    <option value="">— Seleccionar —</option>
                    <?php
                    $materias->data_seek(0);
                    while ($m = $materias->fetch_assoc()):
                    ?>
                    <option value="<?= $m['idMateria'] ?>"
                        <?= ($logro_edit && $logro_edit['idMateria'] == $m['idMateria']) ? 'selected' : '' ?>
                        <?= ($filtro_materia == $m['idMateria']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($m['nombre']) ?>
                    </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="form-group mb-2">
                <label>Grado <span class="req">*</span></label>
                <select name="idGrado" id="sel_grado" required>
                    <option value="">— Seleccionar —</option>
                    <?php
                    $grados->data_seek(0);
                    while ($g = $grados->fetch_assoc()):
                    ?>
                    <option value="<?= $g['idgrado'] ?>"
                        <?= ($logro_edit && $logro_edit['idGrado'] == $g['idgrado']) ? 'selected' : '' ?>
                        <?= ($filtro_grado == $g['idgrado']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($g['descripcion']) ?>
                    </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="form-group mb-2">
                <label>Tipo <span class="req">*</span></label>
                <select name="idtipo" required>
                    <?php
                    $tipos->data_seek(0);
                    while ($t = $tipos->fetch_assoc()):
                    ?>
                    <option value="<?= $t['id'] ?>"
                        <?= ($logro_edit && $logro_edit['idtipo'] == $t['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($t['descripcion']) ?>
                    </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="form-group mb-2">
                <label>Unidad <span class="req">*</span></label>
                <input type="number" name="unidad" min="1" max="10"
                       value="<?= htmlspecialchars($logro_edit['unidad'] ?? '1') ?>" required>
            </div>

            <div class="form-group mb-2">
                <label>Descripción del Logro <span class="req">*</span></label>
                <textarea name="descripcion" rows="4" required
                          placeholder="Ej: Comprende el concepto de conjunto y sus diferentes representaciones"><?= htmlspecialchars($logro_edit['descripcion'] ?? '') ?></textarea>
            </div>

            <div class="btn-toolbar">
                <button class="btn btn-primary">💾 Guardar</button>
                <?php if ($logro_edit): ?>
                <a href="/pages/logros.php" class="btn btn-outline">✕ Cancelar</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>
</div>

<!-- Lista de logros con filtros -->
<div>
<div class="card">
    <div class="card-header">🔍 Buscar Logros</div>
    <div class="card-body">
        <form method="POST">
            <div style="display:flex; gap:12px; align-items:flex-end">
                <div class="form-group" style="flex:1">
                    <label>Grado</label>
                    <select name="filtro_grado">
                        <option value="">— Todos —</option>
                        <?php
                        $grados->data_seek(0);
                        while ($g = $grados->fetch_assoc()):
                        ?>
                        <option value="<?= $g['idgrado'] ?>" <?= $filtro_grado == $g['idgrado'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($g['descripcion']) ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group" style="flex:1">
                    <label>Asignatura</label>
                    <select name="filtro_materia">
                        <option value="">— Todas —</option>
                        <?php
                        $materias->data_seek(0);
                        while ($m = $materias->fetch_assoc()):
                        ?>
                        <option value="<?= $m['idMateria'] ?>" <?= $filtro_materia == $m['idMateria'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($m['nombre']) ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <button class="btn btn-secondary" style="margin-bottom:1px">🔍 Buscar</button>
            </div>
        </form>
    </div>
</div>

<?php if ($logros_resultado !== null): ?>
<div class="card">
    <div class="card-header">
        📋 Listado de Logros
        <span class="badge"><?= $logros_resultado->num_rows ?></span>
    </div>
    <div class="card-body" style="padding:0">
        <table>
            <thead>
                <tr><th>Código</th><th>Unidad</th><th>Descripción</th><th></th></tr>
            </thead>
            <tbody>
                <?php if ($logros_resultado->num_rows === 0): ?>
                <tr><td colspan="4" class="text-center" style="padding:20px; color:var(--texto-suave)">
                    No hay logros registrados para ese filtro
                </td></tr>
                <?php else: ?>
                <?php while ($l = $logros_resultado->fetch_assoc()): ?>
                <tr>
                    <td><strong><?= $l['idlogro'] ?></strong></td>
                    <td><?= $l['unidad'] ?></td>
                    <td style="max-width:400px"><?= htmlspecialchars($l['descripcion']) ?></td>
                    <td style="white-space:nowrap">
                        <a href="?editar=<?= $l['idlogro'] ?>&grado=<?= $filtro_grado ?>&materia=<?= $filtro_materia ?>"
                           class="btn btn-sm btn-outline">✏️</a>
                        <a href="?eliminar=<?= $l['idlogro'] ?>&grado=<?= $filtro_grado ?>&materia=<?= $filtro_materia ?>"
                           class="btn btn-sm btn-danger"
                           onclick="return confirm('¿Eliminar este logro?')">🗑️</a>
                    </td>
                </tr>
                <?php endwhile; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>
</div>

</div>

<?php include '../includes/footer.php'; ?>
