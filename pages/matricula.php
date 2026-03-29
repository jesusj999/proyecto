<?php
// Título que aparece en la pestaña del navegador
$pageTitle = 'Matrícula';

// Incluye la conexión a la base de datos y funciones principales
require_once '../includes/config.php';

// Solo permite acceso al administrador (nivel 1)
requireAuth([1]);

// Variable para mensajes de éxito
$msg = '';

// Variable para mensajes de error
$error = '';

// Obtiene el año actual del servidor
$anio_actual = date('Y');

// Variable donde se guardarán los datos del estudiante buscado
$estudiante_datos = null;

// ============================================
// BUSCAR ESTUDIANTE POR CÓDIGO
// ============================================
// Verifica que el formulario fue enviado y que la acción es buscar_est
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'buscar_est') {

    // Captura el código del estudiante como número entero
    $codigo = (int)$_POST['codigo_est'];

    // Busca en personas y estudiantes los datos del código ingresado
    // JOIN une las dos tablas por la identificación
    $r = $conn->query("SELECT p.*, e.instProcedencia
                       FROM personas p JOIN estudiantes e ON p.identificacion = e.identificacion
                       WHERE p.identificacion = $codigo");

    // Si encontró al estudiante
    if ($r && $r->num_rows > 0) {

        // Guarda todos los datos del estudiante
        $estudiante_datos = $r->fetch_assoc();

        // Busca la matrícula más reciente del estudiante
        // Trae también descripción de grado, grupo, sede y estado
        $mat = $conn->query("SELECT m.*, g.descripcion as grado_desc, gr.nombre as grupo_nombre,
                             s.nombre as sede_nombre, ea.descripcion as estado
                             FROM matricula m
                             JOIN grado g ON m.idGrado = g.idgrado
                             JOIN grupo gr ON m.idGrupo = gr.idGrupo
                             JOIN sedes s ON m.idSede = s.codigoDane
                             JOIN estado_actual ea ON m.idEstadoActual = ea.idEstado
                             WHERE m.idEstudiante = $codigo ORDER BY m.anio DESC LIMIT 1");

        // Agrega los datos de matrícula dentro del array del estudiante
        // Si no tiene matrícula queda como null
        $estudiante_datos['matricula'] = $mat ? $mat->fetch_assoc() : null;

    } else {
        // Si no encontró al estudiante muestra error
        $error = "No se encontró estudiante con código $codigo.";
    }
}

// ============================================
// MATRICULAR ESTUDIANTE
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'matricular') {

    // Captura todos los datos del formulario de matrícula
    $id_est = (int)$_POST['idEstudiante'];       // ID del estudiante
    $anio = (int)$_POST['anio'];                  // Año de matrícula
    $sede = (int)$_POST['idSede'];                // Sede seleccionada
    $grado = (int)$_POST['idGrado'];              // Grado seleccionado
    $grupo = (int)$_POST['idGrupo'];              // Grupo seleccionado
    $fecha = sanitize($_POST['fecha_matricula']); // Fecha limpia de caracteres especiales
    $inst_proc = sanitize($_POST['instProcedencia']); // Institución de procedencia limpia

    // Verifica si el estudiante ya tiene matrícula en ese año
    $check = $conn->query("SELECT * FROM matricula WHERE idEstudiante=$id_est AND anio=$anio");

    if ($check->num_rows > 0) {
        // Si ya existe, actualiza los datos de la matrícula
        $conn->query("UPDATE matricula SET idSede=$sede, idGrado=$grado, idGrupo=$grupo,
                      fecha_matricula='$fecha', idEstadoActual=1, institucionProcedencia='$inst_proc'
                      WHERE idEstudiante=$id_est AND anio=$anio");
        $msg = "Matrícula actualizada.";
    } else {
        // Si no existe, crea una nueva matrícula
        // idEstadoActual=1 significa Activo
        $conn->query("INSERT INTO matricula (idEstudiante, anio, idSede, idGrado, idGrupo,
                      idEstadoActual, fecha_matricula, institucionProcedencia)
                      VALUES ($id_est, $anio, $sede, $grado, $grupo, 1, '$fecha', '$inst_proc')");
        $msg = "Estudiante matriculado exitosamente en $anio.";
    }
}

// ============================================
// RETIRAR ESTUDIANTE
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'retirar') {

    // Captura los datos del formulario de retiro
    $id_est = (int)$_POST['idEstudiante'];          // ID del estudiante
    $anio = (int)$_POST['anio'];                     // Año de la matrícula
    $fecha_ret = sanitize($_POST['fecha_retiro']);    // Fecha de retiro limpia
    $motivo = sanitize($_POST['motivo_retiro']);      // Motivo del retiro limpio

    // Actualiza la matrícula con la fecha y motivo de retiro
    // idEstadoActual=2 significa Retirado
    $conn->query("UPDATE matricula SET fecha_retiro='$fecha_ret', motivo_retiro='$motivo', idEstadoActual=2
                  WHERE idEstudiante=$id_est AND anio=$anio");
    $msg = "Estudiante retirado del sistema.";
}

// Trae todas las sedes para el select del formulario
$sedes = $conn->query("SELECT * FROM sedes ORDER BY nombre");

// Trae todos los grados para el select del formulario
$grados = $conn->query("SELECT * FROM grado ORDER BY idgrado");

// Incluye el header con el menú de navegación
include '../includes/header.php';
?>

<!-- Título de la página -->
<div class="page-header">
    <div class="page-title">📋 Matrícula de Estudiantes</div>
    <div class="page-subtitle">Matricular estudiantes nuevos y gestionar retiros</div>
</div>

<!-- Muestra mensaje de éxito si existe -->
<?php if ($msg): ?><div class="alert alert-success">✅ <?= $msg ?></div><?php endif; ?>

<!-- Muestra mensaje de error si existe -->
<?php if ($error): ?><div class="alert alert-error">⚠️ <?= $error ?></div><?php endif; ?>

<!-- Formulario para buscar estudiante por código -->
<div class="card">
    <div class="card-header">🔍 Buscar Estudiante</div>
    <div class="card-body">
        <form method="POST">
            <!-- Campo oculto que indica qué acción ejecutar en PHP -->
            <input type="hidden" name="action" value="buscar_est">
            <div style="display:flex; gap:12px; align-items:flex-end">
                <div class="form-group" style="flex:1">
                    <label>Código del Estudiante</label>
                    <!-- Mantiene el valor escrito si se envió el formulario -->
                    <input type="number" name="codigo_est" placeholder="Ej: 14189" required
                           value="<?= htmlspecialchars($_POST['codigo_est'] ?? '') ?>">
                </div>
                <button type="submit" class="btn btn-secondary">🔍 Buscar</button>
            </div>
        </form>
    </div>
</div>

<!-- Solo muestra el resto si encontró al estudiante -->
<?php if ($estudiante_datos): ?>
<!-- Grid de dos columnas: datos del estudiante | formulario de matrícula -->
<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">

<!-- Tarjeta con datos del estudiante encontrado -->
<div class="card">
    <div class="card-header">👤 Datos del Estudiante</div>
    <div class="card-body">
        <table style="width:100%">
            <!-- Muestra el código del estudiante -->
            <tr><td style="color:var(--texto-suave); padding:4px 0">Código:</td>
                <td><strong><?= $estudiante_datos['identificacion'] ?></strong></td></tr>

            <!-- Concatena primer nombre y primer apellido -->
            <tr><td style="color:var(--texto-suave); padding:4px 0">Nombre:</td>
                <td><?= htmlspecialchars($estudiante_datos['primerNombre'] . ' ' . $estudiante_datos['primerApellido']) ?></td></tr>

            <!-- Muestra el documento o un guión si no tiene -->
            <tr><td style="color:var(--texto-suave); padding:4px 0">Documento:</td>
                <td><?= htmlspecialchars($estudiante_datos['documento'] ?? '—') ?></td></tr>

            <!-- Solo muestra datos de matrícula si tiene una registrada -->
            <?php if ($estudiante_datos['matricula']): ?>
            <tr><td colspan="2"><hr style="border-color:var(--crema-oscuro); margin:8px 0"></td></tr>

            <!-- Grado actual de la matrícula -->
            <tr><td style="color:var(--texto-suave); padding:4px 0">Grado actual:</td>
                <td><?= htmlspecialchars($estudiante_datos['matricula']['grado_desc']) ?></td></tr>

            <!-- Grupo de la matrícula -->
            <tr><td style="color:var(--texto-suave); padding:4px 0">Grupo:</td>
                <td><?= htmlspecialchars($estudiante_datos['matricula']['grupo_nombre']) ?></td></tr>

            <!-- Sede de la matrícula -->
            <tr><td style="color:var(--texto-suave); padding:4px 0">Sede:</td>
                <td><?= htmlspecialchars($estudiante_datos['matricula']['sede_nombre']) ?></td></tr>

            <!-- Estado con badge de color según el estado (Activo/Retirado) -->
            <tr><td style="color:var(--texto-suave); padding:4px 0">Estado:</td>
                <td><span class="badge-estado badge-<?= strtolower($estudiante_datos['matricula']['estado']) ?>">
                    <?= $estudiante_datos['matricula']['estado'] ?>
                </span></td></tr>
            <?php endif; ?>
        </table>
    </div>
</div>

<!-- Columna derecha con formularios -->
<div>

<!-- Formulario para matricular al estudiante -->
<div class="card">
    <div class="card-header">📝 Matrícula</div>
    <div class="card-body">
        <form method="POST">
            <!-- Indica que la acción es matricular -->
            <input type="hidden" name="action" value="matricular">

            <!-- Pasa el ID del estudiante sin mostrarlo en pantalla -->
            <input type="hidden" name="idEstudiante" value="<?= $estudiante_datos['identificacion'] ?>">

            <div class="form-grid">
                <!-- Campo del año de matrícula, por defecto el año actual -->
                <div class="form-group">
                    <label>Año <span class="req">*</span></label>
                    <input type="number" name="anio" value="<?= $anio_actual ?>" required>
                </div>

                <!-- Select de sedes, al cambiar ejecuta cargarGruposMat() en JavaScript -->
                <div class="form-group">
                    <label>Sede <span class="req">*</span></label>
                    <select name="idSede" required onchange="cargarGruposMat()">
                        <option value="">— Seleccionar —</option>
                        <?php
                        // Resetea el puntero para recorrer desde el inicio
                        $sedes->data_seek(0);
                        while ($s = $sedes->fetch_assoc()):
                        ?>
                        <option value="<?= $s['codigoDane'] ?>"><?= htmlspecialchars($s['nombre']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <!-- Select de grados, al cambiar también ejecuta cargarGruposMat() -->
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

                <!-- Select de grupos, se llena dinámicamente con JavaScript -->
                <div class="form-group">
                    <label>Grupo <span class="req">*</span></label>
                    <select name="idGrupo" id="sel_grupo_mat" required>
                        <option value="">— Seleccionar Sede/Grado —</option>
                    </select>
                </div>

                <!-- Fecha de matrícula, por defecto hoy -->
                <div class="form-group">
                    <label>Fecha de Matrícula <span class="req">*</span></label>
                    <input type="date" name="fecha_matricula" value="<?= date('Y-m-d') ?>" required>
                </div>

                <!-- Institución de donde viene el estudiante -->
                <div class="form-group">
                    <label>Institución de Procedencia</label>
                    <input type="text" name="instProcedencia" 
                           value="<?= htmlspecialchars($estudiante_datos['instProcedencia'] ?? '') ?>">
                </div>
            </div>

            <div class="btn-toolbar mt-2">
                <button class="btn btn-primary">💾 Matricular</button>
            </div>
        </form>
    </div>
</div>

<!-- Formulario de retiro, solo aparece si tiene matrícula activa -->
<?php if ($estudiante_datos['matricula']): ?>
<div class="card mt-2">
    <!-- Header rojo para indicar que es una acción de retiro -->
    <div class="card-header" style="background: linear-gradient(to right, #922b21, #c0392b)">
        ⛔ Retiro del Estudiante
    </div>
    <div class="card-body">
        <form method="POST">
            <!-- Indica que la acción es retirar -->
            <input type="hidden" name="action" value="retirar">

            <!-- Pasa el ID del estudiante sin mostrarlo -->
            <input type="hidden" name="idEstudiante" value="<?= $estudiante_datos['identificacion'] ?>">

            <!-- Pasa el año actual sin mostrarlo -->
            <input type="hidden" name="anio" value="<?= $anio_actual ?>">

            <div class="form-grid">
                <!-- Fecha en que se retira el estudiante -->
                <div class="form-group">
                    <label>Fecha de Retiro</label>
                    <input type="date" name="fecha_retiro" value="<?= date('Y-m-d') ?>">
                </div>

                <!-- Motivo del retiro -->
                <div class="form-group">
                    <label>Motivo</label>
                    <select name="motivo_retiro">
                        <option value="Traslado">Traslado</option>
                        <option value="Deserción">Deserción</option>
                        <option value="Otro">Otro</option>
                    </select>
                </div>
            </div>

            <!-- Botón con confirmación antes de ejecutar el retiro -->
            <button class="btn btn-danger mt-1" 
                    onclick="return confirm('¿Confirmar retiro del estudiante?')">
                ⛔ Retirar Estudiante
            </button>
        </form>
    </div>
</div>
<?php endif; ?>
</div>

</div><!-- Cierre del grid de dos columnas -->
<?php endif; ?>

<!-- Incluye el archivo JavaScript para cargar grupos dinámicamente -->
<script src="../js/cargar_grupos.js"></script>

<!-- Incluye el footer con los scripts globales -->
<?php include '../includes/footer.php'; ?>