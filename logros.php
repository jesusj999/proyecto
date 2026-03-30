<?php
// Título de la página que aparece en el navegador
$pageTitle = 'Logros e Indicadores';

// Incluye la conexión a la base de datos y funciones principales
require_once 'includes/config.php';

// Solo Admin (1) y Docente (2) pueden acceder a esta página
requireAuth([1, 2]);

// Variables para mensajes de éxito y error
$msg = '';
$error = '';

// ============================================
// GUARDAR NUEVO LOGRO
// ============================================
// Verifica que el formulario fue enviado y la acción es guardar
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'guardar') {

    // Captura y limpia los datos del formulario
    $grado = (int)$_POST['idGrado'];           // Grado como número entero
    $materia = (int)$_POST['idMateria'];        // Materia como número entero
    $tipo = (int)$_POST['idtipo'];              // Tipo de logro como número entero
    $unidad = (int)$_POST['unidad'];            // Unidad como número entero
    $desc = sanitize($_POST['descripcion']);     // Descripción limpia de caracteres especiales

    // Inserta el nuevo logro en la base de datos
    // El código (idlogro) se asigna automáticamente con AUTO_INCREMENT
    if ($conn->query("INSERT INTO logros (idtipo, descripcion, idMateria, idGrado, unidad)
                      VALUES ($tipo, '$desc', $materia, $grado, $unidad)")) {
        $msg = "Logro guardado. El código fue asignado automáticamente por el sistema.";
    } else {
        $error = "Error: " . $conn->error;
    }
}

// ============================================
// MODIFICAR LOGRO EXISTENTE
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'modificar') {

    // Captura el ID del logro a modificar y los nuevos datos
    $id = (int)$_POST['idlogro'];
    $desc = sanitize($_POST['descripcion']);
    $unidad = (int)$_POST['unidad'];
    $tipo = (int)$_POST['idtipo'];

    // Actualiza solo descripción, unidad y tipo del logro
    if ($conn->query("UPDATE logros SET descripcion='$desc', unidad=$unidad, idtipo=$tipo WHERE idlogro=$id")) {
        $msg = "Logro actualizado.";
    } else {
        $error = "Error: " . $conn->error;
    }
}

// ============================================
// ELIMINAR LOGRO
// ============================================
// Verifica si viene el parámetro 'eliminar' en la URL
if (isset($_GET['eliminar'])) {
    $id = (int)$_GET['eliminar'];

    // Intenta eliminar el logro de la base de datos
    if ($conn->query("DELETE FROM logros WHERE idlogro = $id")) {
        $msg = "Logro eliminado.";
    } else {
        // Si falla es porque está siendo usado en calificaciones (llave foránea)
        $error = "No se puede eliminar: está siendo usado en calificaciones.";
    }
}

// ============================================
// BUSCAR LOGROS CON FILTROS
// ============================================
// Variable donde se guardarán los resultados de búsqueda
$logros_resultado = null;

// Captura los filtros del formulario o de la URL
$filtro_grado = (int)($_POST['filtro_grado'] ?? $_GET['grado'] ?? 0);
$filtro_materia = (int)($_POST['filtro_materia'] ?? $_GET['materia'] ?? 0);

// Solo busca si se seleccionó al menos un filtro
if ($filtro_grado || $filtro_materia) {

    // Construye el WHERE dinámicamente según los filtros aplicados
    $where = [];
    if ($filtro_grado) $where[] = "l.idGrado = $filtro_grado";
    if ($filtro_materia) $where[] = "l.idMateria = $filtro_materia";

    // Une las condiciones con AND
    $where_sql = 'WHERE ' . implode(' AND ', $where);

    // Busca logros con JOIN para traer nombre de grado, materia y tipo
    $logros_resultado = $conn->query("SELECT l.*, g.descripcion as grado_desc,
                                     m.nombre as materia_nombre, t.descripcion as tipo_desc
                                     FROM logros l
                                     JOIN grado g ON l.idGrado = g.idgrado
                                     JOIN materias m ON l.idMateria = m.idMateria
                                     JOIN tipologro t ON l.idtipo = t.id
                                     $where_sql ORDER BY l.unidad, l.idlogro");
}

// ============================================
// CARGAR LOGRO PARA EDITAR
// ============================================
$logro_edit = null;

// Si viene el parámetro 'editar' en la URL carga ese logro
if (isset($_GET['editar'])) {
    $id = (int)$_GET['editar'];
    $r = $conn->query("SELECT * FROM logros WHERE idlogro = $id");
    // Guarda los datos del logro o null si no lo encontró
    $logro_edit = $r ? $r->fetch_assoc() : null;
}

// Trae todos los grados, materias y tipos de logro para los selects
$grados = $conn->query("SELECT * FROM grado ORDER BY idgrado");
$materias = $conn->query("SELECT * FROM materias ORDER BY nombre");
$tipos = $conn->query("SELECT * FROM tipologro");

// Incluye el header con el menú de navegación
include '../proyecto/includes/header.php';
?>

<!-- Título de la página -->
<div class="page-header">
    <div class="page-title">🏆 Logros o Indicadores de Desempeño</div>
    <div class="page-subtitle">Crear y administrar logros académicos por grado y asignatura</div>
</div>

<!-- Muestra mensaje de éxito si existe -->
<?php if ($msg): ?><div class="alert alert-success">✅ <?= $msg ?></div><?php endif; ?>

<!-- Muestra mensaje de error si existe -->
<?php if ($error): ?><div class="alert alert-error">⚠️ <?= $error ?></div><?php endif; ?>

<!-- Grid de dos columnas: formulario izquierda | búsqueda derecha -->
<div style="display: grid; grid-template-columns: 380px 1fr; gap: 20px;">

    <!-- COLUMNA IZQUIERDA: Formulario crear o modificar logro -->
    <div>
        <div class="card">
            <!-- Si hay logro a editar muestra "Modificar", si no muestra "Crear Nuevo" -->
            <div class="card-header">
                <?= $logro_edit ? '✏️ Modificar Logro' : '➕ Crear Nuevo Logro' ?>
            </div>
            <div class="card-body">
                <form method="POST">

                    <!-- Si hay logro a editar la acción es modificar, si no es guardar -->
                    <input type="hidden" name="action" value="<?= $logro_edit ? 'modificar' : 'guardar' ?>">

                    <!-- Solo aparece cuando se está editando un logro existente -->
                    <?php if ($logro_edit): ?>

                        <!-- Campo oculto con el ID del logro a modificar -->
                        <input type="hidden" name="idlogro" value="<?= $logro_edit['idlogro'] ?>">

                        <!-- Muestra el código del logro como solo lectura -->
                        <div class="form-group mb-2">
                            <label>Código</label>
                            <input type="text" value="<?= $logro_edit['idlogro'] ?>" readonly style="background:#f0f0f0">
                        </div>
                    <?php endif; ?>

                    <!-- Select de asignaturas obligatorio -->
                    <!-- Si hay filtro o logro a editar marca la opción correspondiente -->
                    <div class="form-group mb-2">
                        <label>Asignatura <span class="req">*</span></label>
                        <select name="idMateria" id="sel_materia" required>
                            <option value="">— Seleccionar —</option>
                            <?php
                            // Reinicia el puntero para leer desde el inicio
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

                    <!-- Select de grados obligatorio -->
                    <!-- Marca el grado del logro si se está editando o si hay filtro activo -->
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

                    <!-- Select del tipo de logro: Académico, Actitudinal o Comportamental -->
                    <div class="form-group mb-2">
                        <label>Tipo <span class="req">*</span></label>
                        <select name="idtipo" required>
                            <?php
                            $tipos->data_seek(0);
                            while ($t = $tipos->fetch_assoc()):
                            ?>
                                <!-- Marca el tipo si se está editando el logro -->
                                <option value="<?= $t['id'] ?>"
                                    <?= ($logro_edit && $logro_edit['idtipo'] == $t['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($t['descripcion']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <!-- Campo numérico para la unidad, mínimo 1 máximo 10 -->
                    <!-- Si se edita muestra la unidad actual, si es nuevo muestra 1 por defecto -->
                    <div class="form-group mb-2">
                        <label>Unidad <span class="req">*</span></label>
                        <input type="number" name="unidad" min="1" max="10"
                            value="<?= htmlspecialchars($logro_edit['unidad'] ?? '1') ?>" required>
                    </div>

                    <!-- Área de texto para escribir la descripción del logro -->
                    <!-- Si se edita muestra la descripción actual -->
                    <div class="form-group mb-2">
                        <label>Descripción del Logro <span class="req">*</span></label>
                        <textarea name="descripcion" rows="4" required
                            placeholder="Ej: Comprende el concepto de conjunto y sus diferentes representaciones"><?= htmlspecialchars($logro_edit['descripcion'] ?? '') ?></textarea>
                    </div>

                    <div class="btn-toolbar">
                        <!-- Botón para guardar o modificar el logro -->
                        <button class="btn btn-primary">💾 Guardar</button>

                        <!-- Botón cancelar solo aparece cuando se está editando -->
                        <?php if ($logro_edit): ?>
                            <a href="/pages/logros.php" class="btn btn-outline">✕ Cancelar</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- COLUMNA DERECHA: Buscador y listado de logros -->
    <div>
        <div class="card">
            <div class="card-header">🔍 Buscar Logros</div>
            <div class="card-body">
                <form method="POST">
                    <div style="display:flex; gap:12px; align-items:flex-end">

                        <!-- Filtro por grado, mantiene el seleccionado después de buscar -->
                        <div class="form-group" style="flex:1">
                            <label>Grado</label>
                            <select name="filtro_grado">
                                <option value="">— Todos —</option>
                                <?php
                                $grados->data_seek(0);
                                while ($g = $grados->fetch_assoc()):
                                ?>
                                    <!-- Marca el grado si ya fue seleccionado anteriormente -->
                                    <option value="<?= $g['idgrado'] ?>" <?= $filtro_grado == $g['idgrado'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($g['descripcion']) ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <!-- Filtro por asignatura, mantiene la seleccionada después de buscar -->
                        <div class="form-group" style="flex:1">
                            <label>Asignatura</label>
                            <select name="filtro_materia">
                                <option value="">— Todas —</option>
                                <?php
                                $materias->data_seek(0);
                                while ($m = $materias->fetch_assoc()):
                                ?>
                                    <!-- Marca la materia si ya fue seleccionada anteriormente -->
                                    <option value="<?= $m['idMateria'] ?>" <?= $filtro_materia == $m['idMateria'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($m['nombre']) ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <!-- Botón para aplicar los filtros -->
                        <button class="btn btn-secondary" style="margin-bottom:1px">🔍 Buscar</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Solo muestra la tabla si se hizo una búsqueda -->
        <?php if ($logros_resultado !== null): ?>
            <div class="card">
                <div class="card-header">
                    📋 Listado de Logros
                    <!-- Badge que muestra el total de logros encontrados -->
                    <span class="badge"><?= $logros_resultado->num_rows ?></span>
                </div>
                <div class="card-body" style="padding:0">
                    <table>
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Unidad</th>
                                <th>Descripción</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Si no hay resultados muestra mensaje informativo -->
                            <?php if ($logros_resultado->num_rows === 0): ?>
                                <tr>
                                    <td colspan="4" class="text-center" style="padding:20px; color:var(--texto-suave)">
                                        No hay logros registrados para ese filtro
                                    </td>
                                </tr>
                            <?php else: ?>

                                <!-- Recorre y muestra cada logro encontrado -->
                                <?php while ($l = $logros_resultado->fetch_assoc()): ?>
                                    <tr>
                                        <!-- Código del logro en negrita -->
                                        <td><strong><?= $l['idlogro'] ?></strong></td>

                                        <!-- Número de unidad del logro -->
                                        <td><?= $l['unidad'] ?></td>

                                        <!-- Descripción del logro con ancho máximo para no deformar la tabla -->
                                        <td style="max-width:400px"><?= htmlspecialchars($l['descripcion']) ?></td>

                                        <td style="white-space:nowrap">
                                            <!-- Botón editar: pasa el ID del logro y los filtros activos por URL -->
                                            <a href="?editar=<?= $l['idlogro'] ?>&grado=<?= $filtro_grado ?>&materia=<?= $filtro_materia ?>"
                                                class="btn btn-sm btn-outline">✏️</a>

                                            <!-- Botón eliminar: pasa el ID y pide confirmación antes de eliminar -->
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

</div><!-- Cierre del grid de dos columnas -->

<!-- Incluye el footer con los scripts globales -->
<?php include '../includes/footer.php'; ?>