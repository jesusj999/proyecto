<?php
// Título que aparece en la pestaña del navegador
$pageTitle = 'Asignar Materias a Docentes';

// Incluye la conexión a la base de datos y funciones principales
require_once '../includes/config.php';

// Solo el administrador (nivel 1) puede acceder a esta página
requireAuth([1]);

// Variables para mensajes de éxito y error
$msg = '';
$error = '';

// Obtiene el año actual del servidor
$anio_actual = date('Y');

// ============================================
// GUARDAR ASIGNACIÓN DE MATERIAS
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'asignar') {

    // Captura los datos del formulario
    $docente = (int)$_POST['idDocente'];
    $sede = (int)$_POST['idSede'];
    $grupo = (int)$_POST['idGrupo'];
    $grado = (int)$_POST['idGrado'];
    $anio = (int)$_POST['anio'];

    // Array con las materias seleccionadas, vacío si no seleccionó ninguna
    $materias_sel = $_POST['materias'] ?? [];

    // Contadores para el mensaje final
    $insertados = 0;
    $duplicados = 0;

    // Recorre cada materia seleccionada e intenta insertarla
    foreach ($materias_sel as $materia) {
        $materia = (int)$materia;

        // Verifica si otro docente ya tiene esa materia en el mismo grupo y año
        $check = $conn->query("SELECT idDocente FROM horario WHERE idMateria=$materia AND idgrupo=$grupo AND año=$anio AND idDocente != $docente");
        if ($check->num_rows > 0) {
            // Si existe conflicto con otro docente cuenta como duplicado y salta
            $duplicados++;
            continue;
        }

        // Verifica si el mismo docente ya tiene esa materia asignada en ese grupo y año
        $check2 = $conn->query("SELECT idDocente FROM horario WHERE idDocente=$docente AND idMateria=$materia AND idgrupo=$grupo AND año=$anio");
        if ($check2->num_rows > 0) {
            // Si ya existe la asignación cuenta como duplicado y salta
            $duplicados++;
            continue;
        }

        // Si no hay conflictos inserta la nueva asignación en la tabla horario
        if ($conn->query("INSERT INTO horario (idDocente, idMateria, idGrupo, año, idgrado, idsede)
                            VALUES ($docente, $materia, $grupo, $anio, $grado, $sede)")) {
            $insertados++;
        }
    }

    // Construye el mensaje final con el resumen de la operación
    if ($insertados > 0) $msg = "$insertados materia(s) asignada(s) correctamente.";
    if ($duplicados > 0) $msg .= ($msg ? ' ' : '') . "$duplicados ya existían o tienen conflicto.";
}

// ============================================
// ELIMINAR ASIGNACIÓN
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'eliminar') {

    // Captura los datos necesarios para identificar la asignación a eliminar
    $docente = (int)$_POST['idDocente'];
    $materia = (int)$_POST['idMateria'];
    $grupo = (int)$_POST['idGrupo'];
    $anio = (int)$_POST['anio'];

    // Elimina la asignación específica del horario
    if ($conn->query("DELETE FROM horario WHERE idDocente=$docente AND idMateria=$materia AND idgrupo=$grupo AND año=$anio")) {
        $msg = "Asignación eliminada.";
    } else {
        $error = "Error al eliminar.";
    }
}

// ============================================
// CARGAR HORARIO DEL DOCENTE SELECCIONADO
// ============================================
// Captura el ID del docente del formulario o de la URL
$docente_sel = (int)($_POST['docente_ver'] ?? $_GET['docente'] ?? 0);
$horario_docente = null;

// Si hay un docente seleccionado carga su horario actual
if ($docente_sel) {
    // Trae todas las materias asignadas al docente en el año actual
    // Con JOIN trae los nombres de materia, grado, grupo y sede
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

// Trae todos los docentes para el select con nombre completo
$docentes = $conn->query("SELECT d.idDocentes, CONCAT(p.primerNombre, ' ', p.primerApellido) as nombre
                          FROM docentes d JOIN personas p ON d.idDocentes = p.identificacion
                          ORDER BY p.primerApellido");

// Trae todas las sedes para el select
$sedes = $conn->query("SELECT * FROM sedes ORDER BY nombre");

// Trae todas las materias para el select múltiple
$materias = $conn->query("SELECT * FROM materias ORDER BY nombre");

// Incluye el header con el menú de navegación
include '../includes/header.php';
?>

<!-- Título de la página -->
<div class="page-header">
    <div class="page-title">📚 Asignar Materias a Docentes</div>
    <div class="page-subtitle">Administrar el horario académico de cada docente</div>
</div>

<!-- Muestra mensaje de éxito si existe -->
<?php if ($msg): ?><div class="alert alert-success">✅ <?= $msg ?></div><?php endif; ?>

<!-- Muestra mensaje de error si existe -->
<?php if ($error): ?><div class="alert alert-error">⚠️ <?= $error ?></div><?php endif; ?>

<!-- Grid de dos columnas: formulario izquierda | horario actual derecha -->
<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">

    <!-- COLUMNA IZQUIERDA: Formulario para asignar materias -->
    <div class="card">
        <div class="card-header">📋 Asignar Horario al Docente</div>
        <div class="card-body">
            <form method="POST">

                <!-- Indica a PHP que la acción es asignar materias -->
                <input type="hidden" name="action" value="asignar">

                <!-- Select de docentes, al cambiar recarga la página para mostrar su horario -->
                <div class="form-group mb-2">
                    <label>Docente <span class="req">*</span></label>
                    <select name="idDocente" id="sel_docente" required onchange="this.form.submit()">
                        <option value="">— Seleccionar Docente —</option>
                        <?php while ($d = $docentes->fetch_assoc()): ?>
                            <!-- Marca el docente que ya estaba seleccionado -->
                            <option value="<?= $d['idDocentes'] ?>"
                                <?= $docente_sel == $d['idDocentes'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($d['nombre']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <!-- Select múltiple para elegir varias materias a la vez -->
                <!-- Ctrl+clic selecciona varias no consecutivas, Shift+clic consecutivas -->
                <div class="form-group mb-2">
                    <label>Asignar Nuevas Materias</label>
                    <div style="font-size:12px; color:var(--texto-suave); margin-bottom:6px">
                        Ctrl+clic para selección múltiple no consecutiva, Shift+clic para consecutiva
                    </div>
                    <!-- name="materias[]" envía un array con todas las materias seleccionadas -->
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

                    <!-- Select de sedes, al cambiar llama a cargarGrupos() en JavaScript -->
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

                    <!-- Select de grados, al cambiar también llama a cargarGrupos() -->
                    <div class="form-group">
                        <label>Grado <span class="req">*</span></label>
                        <select name="idGrado" id="sel_grado_asig" required onchange="cargarGrupos()">
                            <option value="">— Seleccionar —</option>
                            <?php
                            // Consulta los grados directamente aquí
                            $r_g = $conn->query("SELECT * FROM grado ORDER BY idgrado");
                            while ($g = $r_g->fetch_assoc()):
                            ?>
                                <option value="<?= $g['idgrado'] ?>"><?= htmlspecialchars($g['descripcion']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <!-- Select de grupos, se llena dinámicamente con JavaScript según sede y grado -->
                    <div class="form-group">
                        <label>Grupo <span class="req">*</span></label>
                        <select name="idGrupo" id="sel_grupo_asig" required>
                            <option value="">— Primero seleccione sede y grado —</option>
                        </select>
                    </div>

                    <!-- Campo del año, por defecto el año actual -->
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

    <!-- COLUMNA DERECHA: Horario actual del docente seleccionado -->
    <div class="card">
        <div class="card-header">📊 Horario Actual del Docente</div>
        <div class="card-body" style="padding:0">

            <?php if (!$docente_sel): ?>
                <!-- Si no hay docente seleccionado muestra mensaje informativo -->
                <div style="padding:20px; text-align:center; color:var(--texto-suave)">
                    Seleccione un docente para ver su horario
                </div>

            <?php elseif ($horario_docente && $horario_docente->num_rows > 0): ?>
                <!-- Si el docente tiene materias asignadas muestra la tabla -->
                <table>
                    <thead>
                        <tr>
                            <th>Asignatura</th>
                            <th>Grado</th>
                            <th>Grupo</th>
                            <th>Sede</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Recorre cada materia del horario del docente -->
                        <?php while ($h = $horario_docente->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($h['materia']) ?></td>
                                <td><?= htmlspecialchars($h['grado_desc']) ?></td>
                                <td><?= htmlspecialchars($h['grupo_nombre']) ?></td>
                                <td><?= htmlspecialchars($h['sede_nombre']) ?></td>
                                <td>
                                    <!-- Formulario individual por fila para eliminar esa asignación -->
                                    <form method="POST" style="display:inline">
                                        <!-- Campos ocultos para identificar qué asignación eliminar -->
                                        <input type="hidden" name="action" value="eliminar">
                                        <input type="hidden" name="idDocente" value="<?= $docente_sel ?>">
                                        <input type="hidden" name="idMateria" value="<?= $h['idMateria'] ?>">
                                        <input type="hidden" name="idGrupo" value="<?= $h['idgrupo'] ?>">
                                        <input type="hidden" name="anio" value="<?= $h['año'] ?>">
                                        <!-- Botón eliminar con confirmación -->
                                        <button type="submit" class="btn btn-sm btn-danger"
                                            onclick="return confirm('¿Eliminar esta asignación?')">🗑️</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>

            <?php else: ?>
                <!-- Si el docente no tiene materias asignadas muestra mensaje -->
                <div style="padding:20px; text-align:center; color:var(--texto-suave)">
                    Este docente no tiene materias asignadas en <?= $anio_actual ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

</div><!-- Cierre del grid de dos columnas -->

<!-- Incluye el JavaScript para cargar grupos dinámicamente según sede y grado -->
<script src="../js/cargar_grupos.js"></script>

<!-- Incluye el footer con los scripts globales -->
<?php include '../includes/footer.php'; ?>