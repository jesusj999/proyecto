<?php
$pageTitle = 'Institución';
require_once 'includes/config.php';
requireAuth([1]);

$msg = '';
$error = '';
$tab_activa = $_GET['tab'] ?? 'sedes';

// ---- SEDES ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'guardar_sede') {
    $codigo = (int)$_POST['codigoDane'];
    $nombre = sanitize($_POST['nombre_sede']);
    $dir = sanitize($_POST['direccion_sede']);
    $tel = sanitize($_POST['telefono_sede']);

    $check = $conn->query("SELECT codigoDane FROM sedes WHERE codigoDane = $codigo");
    if ($check->num_rows > 0) {
        $conn->query("UPDATE sedes SET nombre='$nombre', direccion='$dir', telefono='$tel' WHERE codigoDane=$codigo");
        $msg = "Sede actualizada.";
    } else {
        $conn->query("INSERT INTO sedes (codigoDane, nombre, direccion, telefono) VALUES ($codigo, '$nombre', '$dir', '$tel')");
        $msg = "Sede creada correctamente.";
    }
    $tab_activa = 'sedes';
}

if (isset($_GET['del_sede'])) {
    $id = (int)$_GET['del_sede'];
    if ($conn->query("DELETE FROM sedes WHERE codigoDane = $id")) {
        $msg = "Sede eliminada.";
    } else {
        $error = "No se puede eliminar: tiene grupos o matrículas asignadas.";
    }
    $tab_activa = 'sedes';
}

// ---- GRUPOS ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'guardar_grupo') {
    $nombre = sanitize($_POST['nombre_grupo']);
    $sede = (int)$_POST['idSede'];
    $grado = (int)$_POST['idgrado'];
    $id_grupo = (int)($_POST['idGrupo'] ?? 0);

    if ($id_grupo > 0) {
        $conn->query("UPDATE grupo SET nombre='$nombre' WHERE idGrupo=$id_grupo");
        $msg = "Grupo modificado.";
    } else {
        $check = $conn->query("SELECT idGrupo FROM grupo WHERE nombre='$nombre' AND idSede=$sede AND idgrado=$grado");
        if ($check->num_rows > 0) {
            $error = "Ya existe ese grupo en esa sede y grado.";
        } else {
            $conn->query("INSERT INTO grupo (nombre, idSede, idgrado) VALUES ('$nombre', $sede, $grado)");
            $msg = "Grupo creado correctamente.";
        }
    }
    $tab_activa = 'grupos';
}

if (isset($_GET['del_grupo'])) {
    $id = (int)$_GET['del_grupo'];
    if ($conn->query("DELETE FROM grupo WHERE idGrupo = $id")) {
        $msg = "Grupo eliminado.";
    } else {
        $error = "No se puede eliminar: tiene estudiantes o docentes asignados.";
    }
    $tab_activa = 'grupos';
}

// ---- ASIGNATURAS ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'guardar_asignatura') {
    $nombre = sanitize($_POST['nombre_asignatura']);
    $id_mat = (int)($_POST['idMateria'] ?? 0);

    if ($id_mat > 0) {
        $nuevo_nombre = sanitize($_POST['nuevo_nombre']);
        $conn->query("UPDATE materias SET nombre='$nuevo_nombre' WHERE idMateria=$id_mat");
        $msg = "Asignatura modificada.";
    } else {
        $conn->query("INSERT INTO materias (nombre) VALUES ('$nombre')");
        $msg = "Asignatura creada.";
    }
    $tab_activa = 'asignaturas';
}

if (isset($_GET['del_mat'])) {
    $id = (int)$_GET['del_mat'];
    if ($conn->query("DELETE FROM materias WHERE idMateria = $id")) {
        $msg = "Asignatura eliminada.";
    } else {
        $error = "No se puede eliminar: está asignada a docentes o tiene calificaciones.";
    }
    $tab_activa = 'asignaturas';
}

// Datos
$sedes = $conn->query("SELECT * FROM sedes ORDER BY nombre");
$grados = $conn->query("SELECT * FROM grado ORDER BY idgrado");
$grupos = $conn->query("SELECT gr.*, g.descripcion as grado_desc, s.nombre as sede_nombre
                        FROM grupo gr
                        JOIN grado g ON gr.idgrado = g.idgrado
                        JOIN sedes s ON gr.idSede = s.codigoDane
                        ORDER BY s.nombre, g.idgrado, gr.nombre");
$materias = $conn->query("SELECT * FROM materias ORDER BY idMateria");

include '../proyecto/includes/header.php';
?>

<div class="page-header">
    <div class="page-title">🏫 Institución</div>
    <div class="page-subtitle">Administrar Sedes, Grupos y Asignaturas</div>
</div>

<?php if ($msg): ?><div class="alert alert-success">✅ <?= $msg ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-error">⚠️ <?= $error ?></div><?php endif; ?>

<div class="tabs">
    <a class="tab <?= $tab_activa == 'sedes' ? 'active' : '' ?>" href="?tab=sedes" data-tab="tab-sedes">🏢 Sedes</a>
    <a class="tab <?= $tab_activa == 'grupos' ? 'active' : '' ?>" href="?tab=grupos" data-tab="tab-grupos">👥 Grupos</a>
    <a class="tab <?= $tab_activa == 'asignaturas' ? 'active' : '' ?>" href="?tab=asignaturas" data-tab="tab-asignaturas">📚 Asignaturas</a>
</div>

<!-- ===== SEDES ===== -->
<div class="tab-content <?= $tab_activa == 'sedes' ? 'active' : '' ?>" id="tab-sedes" style="display: <?= $tab_activa == 'sedes' ? 'block' : 'none' ?>">
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
        <div class="card">
            <div class="card-header">➕ Administrar Sede</div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="action" value="guardar_sede">
                    <div class="form-group mb-2">
                        <label>Código DANE <span class="req">*</span></label>
                        <input type="number" name="codigoDane" required>
                    </div>
                    <div class="form-group mb-2">
                        <label>Nombre <span class="req">*</span></label>
                        <input type="text" name="nombre_sede" required>
                    </div>
                    <div class="form-group mb-2">
                        <label>Dirección</label>
                        <input type="text" name="direccion_sede">
                    </div>
                    <div class="form-group mb-2">
                        <label>Teléfono</label>
                        <input type="text" name="telefono_sede">
                    </div>
                    <button class="btn btn-primary">💾 Guardar Sede</button>
                </form>
            </div>
        </div>
        <div class="card">
            <div class="card-header">📋 Sedes Registradas</div>
            <div class="card-body" style="padding:0">
                <table>
                    <thead>
                        <tr>
                            <th>Código DANE</th>
                            <th>Nombre</th>
                            <th>Teléfono</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Reinicia el puntero del resultado para leerlo desde el inicio
                        $sedes->data_seek(0);

                        // Recorre cada sede traída de la base de datos
                        while ($s = $sedes->fetch_assoc()):
                        ?>
                            <tr>
                                <!-- Muestra el código DANE de la sede -->
                                <td><?= $s['codigoDane'] ?></td>

                                <!-- Muestra el nombre de la sede protegido contra código malicioso -->
                                <td><?= htmlspecialchars($s['nombre']) ?></td>

                                <!-- Muestra el teléfono, si no tiene muestra un guión -->
                                <td><?= htmlspecialchars($s['telefono'] ?? '—') ?></td>

                                <!-- Botón rojo para eliminar la sede -->
                                <!-- Pasa el codigoDane por URL para saber cuál eliminar -->
                                <!-- confirm() muestra una ventana de confirmación antes de eliminar -->
                                <td><a href="?del_sede=<?= $s['codigoDane'] ?>&tab=sedes" class="btn btn-sm btn-danger" onclick="return confirm('¿Eliminar sede?')">🗑️</a></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- ===== GRUPOS ===== -->
<!-- Pestaña de Grupos, se muestra u oculta según la pestaña activa -->
<!-- Si $tab_activa es 'grupos' agrega clase 'active' y lo muestra, si no lo oculta -->
<div class="tab-content <?= $tab_activa == 'grupos' ? 'active' : '' ?>" id="tab-grupos" style="display: <?= $tab_activa == 'grupos' ? 'block' : 'none' ?>">

    <!-- Grid de dos columnas: formulario izquierda | listado derecha -->
    <div style="display: grid; grid-template-columns: 1fr 1.5fr; gap: 20px;">

        <!-- Columna izquierda: formulario para crear o modificar grupos -->
        <div class="card">
            <div class="card-header">➕ Administrar Grupos</div>
            <div class="card-body">
                <form method="POST">

                    <!-- Indica a PHP que la acción es guardar un grupo -->
                    <input type="hidden" name="action" value="guardar_grupo">

                    <!-- Guarda el ID del grupo cuando se va a modificar, vacío si es nuevo -->
                    <input type="hidden" name="idGrupo" id="idGrupo_edit" value="">

                    <!-- Select de sedes obligatorio -->
                    <div class="form-group mb-2">
                        <label>Sede <span class="req">*</span></label>
                        <select name="idSede" required>
                            <option value="">— Seleccionar —</option>
                            <?php
                            // Reinicia el puntero para leer sedes desde el inicio
                            $sedes->data_seek(0);
                            while ($s = $sedes->fetch_assoc()):
                            ?>
                                <!-- Cada opción muestra el nombre y envía el codigoDane -->
                                <option value="<?= $s['codigoDane'] ?>"><?= htmlspecialchars($s['nombre']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <!-- Select de grados obligatorio -->
                    <div class="form-group mb-2">
                        <label>Grado <span class="req">*</span></label>
                        <select name="idgrado" required>
                            <option value="">— Seleccionar —</option>
                            <?php
                            // Reinicia el puntero para leer grados desde el inicio
                            $grados->data_seek(0);
                            while ($g = $grados->fetch_assoc()):
                            ?>
                                <!-- Cada opción muestra la descripción del grado y envía el id -->
                                <option value="<?= $g['idgrado'] ?>"><?= htmlspecialchars($g['descripcion']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <!-- Campo para escribir el nombre del grupo -->
                    <!-- El enlace 'Nuevo' limpia el formulario vía JavaScript -->
                    <div class="form-group mb-2">
                        <label>Nombre del Grupo <span class="req">*</span></label>
                        <input type="text" name="nombre_grupo" id="nombre_grupo_input" placeholder="Ej: Abejero, Alturas..." required>
                        <small style="color:var(--texto-suave)">Modificar grupo: <a href="#" id="limpiar_grupo">Nuevo</a></small>
                    </div>

                    <!-- Botón para enviar el formulario -->
                    <button class="btn btn-primary">💾 Guardar Grupo</button>
                </form>
            </div>
        </div>

        <!-- Columna derecha: tabla con todos los grupos registrados -->
        <!-- max-height limita la altura y overflow-y agrega scroll si hay muchos grupos -->
        <div class="card">
            <div class="card-header">📋 Listado de Grupos</div>
            <div class="card-body" style="padding:0; max-height:400px; overflow-y:auto">
                <table>
                    <thead>
                        <tr>
                            <th>Grupo</th>
                            <th>Grado</th>
                            <th>Sede</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Recorre todos los grupos traídos de la base de datos -->
                        <?php while ($g = $grupos->fetch_assoc()): ?>
                            <tr>
                                <!-- Muestra nombre, grado y sede del grupo -->
                                <td><?= htmlspecialchars($g['nombre']) ?></td>
                                <td><?= htmlspecialchars($g['grado_desc']) ?></td>
                                <td><?= htmlspecialchars($g['sede_nombre']) ?></td>
                                <td style="white-space:nowrap">
                                    <!-- Botón editar: llama a editarGrupo() en JavaScript -->
                                    <!-- Pasa el ID y nombre del grupo para llenar el formulario -->
                                    <a href="#" class="btn btn-sm btn-outline" onclick="editarGrupo(<?= $g['idGrupo'] ?>, '<?= htmlspecialchars($g['nombre']) ?>')">✏️</a>

                                    <!-- Botón eliminar: pasa el ID por URL y pide confirmación -->
                                    <a href="?del_grupo=<?= $g['idGrupo'] ?>&tab=grupos" class="btn btn-sm btn-danger" onclick="return confirm('¿Eliminar grupo?')">🗑️</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div><!-- Cierre pestaña Grupos -->

<!-- ===== ASIGNATURAS ===== -->
<div class="tab-content <?= $tab_activa == 'asignaturas' ? 'active' : '' ?>" id="tab-asignaturas" style="display: <?= $tab_activa == 'asignaturas' ? 'block' : 'none' ?>">
    <div style="display: grid; grid-template-columns: 1fr 1.5fr; gap: 20px;">
        <div class="card">
            <div class="card-header">➕ Administrar Asignaturas</div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="action" value="guardar_asignatura">
                    <input type="hidden" name="idMateria" id="idMateria_edit" value="">
                    <div class="form-group mb-2">
                        <label>Código</label>
                        <input type="text" id="codigo_asig" readonly style="background:#f0f0f0">
                    </div>
                    <div class="form-group mb-2">
                        <label>Nombre de la Asignatura <span class="req">*</span></label>
                        <input type="text" name="nombre_asignatura" id="nombre_asig_input" required>
                    </div>
                    <div class="form-group mb-2" id="campo_nuevo_nombre" style="display:none">
                        <label>Nuevo Nombre</label>
                        <input type="text" name="nuevo_nombre" id="nuevo_nombre_input">
                    </div>
                    <div class="btn-toolbar">
                        <button class="btn btn-primary">💾 Guardar</button>
                        <a href="#" class="btn btn-outline" onclick="limpiarAsig()">Nuevo</a>
                    </div>
                </form>
            </div>
        </div>
        <div class="card">
            <div class="card-header">📋 Listado de Asignaturas</div>
            <div class="card-body" style="padding:0">
                <table>
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Código Interno</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Recorre todas las asignaturas traídas de la base de datos -->
                        <?php while ($m = $materias->fetch_assoc()): ?>
                            <tr>
                                <!-- Muestra el nombre de la asignatura protegido contra código malicioso -->
                                <td><?= htmlspecialchars($m['nombre']) ?></td>

                                <!-- Muestra el código interno de la asignatura -->
                                <td><?= $m['idMateria'] ?></td>

                                <!-- Botones de acción, white-space:nowrap evita que se partan en dos líneas -->
                                <td style="white-space:nowrap">

                                    <!-- Botón editar: llama a editarAsig() en JavaScript -->
                                    <!-- Pasa el ID y nombre para llenar el formulario de modificación -->
                                    <a href="#" class="btn btn-sm btn-outline" onclick="editarAsig(<?= $m['idMateria'] ?>, '<?= htmlspecialchars($m['nombre']) ?>')">✏️</a>

                                    <!-- Botón eliminar: pasa el ID por URL y pide confirmación antes de eliminar -->
                                    <a href="?del_mat=<?= $m['idMateria'] ?>&tab=asignaturas" class="btn btn-sm btn-danger" onclick="return confirm('¿Eliminar?')">🗑️</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    // Función que llena el formulario con los datos del grupo a editar
    // Se ejecuta al hacer clic en el botón ✏️ de la tabla
    function editarGrupo(id, nombre) {
        // Pone el ID del grupo en el campo oculto para que PHP sepa cuál modificar
        document.getElementById('idGrupo_edit').value = id;
        // Pone el nombre actual del grupo en el campo de texto
        document.getElementById('nombre_grupo_input').value = nombre;
    }

    // Escucha el clic en el enlace "Nuevo" para limpiar el formulario
    document.getElementById('limpiar_grupo').addEventListener('click', function(e) {
        // Evita que el enlace recargue la página
        e.preventDefault();
        // Limpia el ID oculto para que PHP lo trate como nuevo grupo
        document.getElementById('idGrupo_edit').value = '';
        // Limpia el campo de nombre del grupo
        document.getElementById('nombre_grupo_input').value = '';
    });

    // Función que llena el formulario con los datos de la asignatura a editar
    // Se ejecuta al hacer clic en el botón ✏️ de la tabla de asignaturas
    function editarAsig(id, nombre) {
        // Pone el ID de la asignatura en el campo oculto
        document.getElementById('idMateria_edit').value = id;
        // Pone el nombre actual en el campo de nombre
        document.getElementById('nombre_asig_input').value = nombre;
        // Muestra el código interno de la asignatura
        document.getElementById('codigo_asig').value = id;
        // Muestra el campo "Nuevo Nombre" que está oculto por defecto
        document.getElementById('campo_nuevo_nombre').style.display = 'block';
        // Pone el nombre actual en el campo de nuevo nombre
        document.getElementById('nuevo_nombre_input').value = nombre;
    }

    // Función que limpia el formulario de asignaturas para crear una nueva
    function limpiarAsig() {
        // Limpia el ID oculto para que PHP lo trate como nueva asignatura
        document.getElementById('idMateria_edit').value = '';
        // Limpia el campo de nombre
        document.getElementById('nombre_asig_input').value = '';
        // Limpia el campo de código
        document.getElementById('codigo_asig').value = '';
        // Oculta el campo de nuevo nombre
        document.getElementById('campo_nuevo_nombre').style.display = 'none';
    }

    // Maneja el cambio entre pestañas (Sedes, Grupos, Asignaturas) sin recargar la página
    document.querySelectorAll('.tab').forEach(tab => {
        tab.addEventListener('click', function(e) {
            // Evita que el enlace recargue la página
            e.preventDefault();
            // Quita la clase 'active' de todas las pestañas
            document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
            // Oculta todos los contenidos de pestañas
            document.querySelectorAll('.tab-content').forEach(c => c.style.display = 'none');
            // Marca como activa la pestaña en la que se hizo clic
            this.classList.add('active');
            // Obtiene el ID del contenido que debe mostrar
            const target = this.getAttribute('data-tab');
            // Muestra el contenido correspondiente a la pestaña seleccionada
            document.getElementById(target).style.display = 'block';
        });
    });
</script>

<?php include './includes./footer.php'; ?>