<?php
// Título que aparece en la pestaña del navegador
$pageTitle = 'Registro de Personas';

// Incluye la conexión a la base de datos y funciones principales
require_once '../includes/config.php';

// Solo el administrador (nivel 1) puede acceder a esta página
requireAuth([1]);

// Variables para mensajes de éxito, error y datos de persona
$msg = '';
$error = '';
$persona = null;

// ============================================
// GUARDAR O MODIFICAR PERSONA
// ============================================
// Verifica que el formulario fue enviado y que viene el campo action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    // ============================================
    // GUARDAR NUEVA PERSONA
    // ============================================
    if ($_POST['action'] === 'guardar') {

        // Captura y limpia todos los datos del formulario
        $id = (int)$_POST['identificacion'];
        $tipo_doc = (int)$_POST['tipoDocumento'];
        $primer_nombre = sanitize($_POST['primerNombre']);
        $segundo_nombre = sanitize($_POST['segundoNombre']);
        $primer_apellido = sanitize($_POST['primerApellido']);
        $segundo_apellido = sanitize($_POST['segundoApellido']);
        $fecha_nac = sanitize($_POST['fechaNacimiento']);
        $genero = (int)$_POST['idGenero'];
        $email = sanitize($_POST['email']);
        $telefono = sanitize($_POST['telefono']);
        // Puede ser Estudiante, Docente o Administrativo
        $funcion = sanitize($_POST['funcion']);
        $documento = sanitize($_POST['documento']);

        // Verifica si ya existe una persona con ese código
        $check = $conn->query("SELECT identificacion FROM personas WHERE identificacion = $id");
        if ($check->num_rows > 0) {
            $error = "Ya existe una persona con el código $id.";
            // Sugiere el siguiente código disponible
            $next = $conn->query("SELECT MAX(identificacion)+1 as siguiente FROM personas");
            $siguiente = $next->fetch_assoc()['siguiente'];
            $error .= " Puede usar el código: $siguiente";
        } else {
            // Inserta la persona en la tabla personas
            // Si no tiene fecha de nacimiento inserta NULL en vez de cadena vacía
            $sql = "INSERT INTO personas (identificacion, tipoDocumento, primerNombre, segundoNombre,
                    primerApellido, segundoApellido, fechaNacimiento, idGenero, email, telefono, funcion, documento)
                    VALUES ($id, $tipo_doc, '$primer_nombre', '$segundo_nombre',
                    '$primer_apellido', '$segundo_apellido', " . ($fecha_nac ? "'$fecha_nac'" : "NULL") . ",
                    $genero, '$email', '$telefono', '$funcion', '$documento')";

            if ($conn->query($sql)) {
                // Según la función inserta también en la tabla específica
                if ($funcion === 'Estudiante') {
                    // Inserta en tabla estudiantes
                    $conn->query("INSERT INTO estudiantes (identificacion) VALUES ($id)");
                } elseif ($funcion === 'Docente') {
                    // Inserta en tabla docentes
                    $conn->query("INSERT INTO docentes (idDocentes) VALUES ($id)");
                } elseif ($funcion === 'Administrativo') {
                    // Inserta en tabla administrativos
                    $conn->query("INSERT INTO administrativos (idpersonas) VALUES ($id)");
                }
                $msg = "Persona registrada exitosamente con código $id.";
            } else {
                $error = "Error al guardar: " . $conn->error;
            }
        }
    }

    // ============================================
    // MODIFICAR PERSONA EXISTENTE
    // ============================================
    if ($_POST['action'] === 'modificar') {

        // Captura el ID y los nuevos datos del formulario
        $id = (int)$_POST['identificacion'];
        $primer_nombre = sanitize($_POST['primerNombre']);
        $segundo_nombre = sanitize($_POST['segundoNombre']);
        $primer_apellido = sanitize($_POST['primerApellido']);
        $segundo_apellido = sanitize($_POST['segundoApellido']);
        $fecha_nac = sanitize($_POST['fechaNacimiento']);
        $email = sanitize($_POST['email']);
        $telefono = sanitize($_POST['telefono']);
        $documento = sanitize($_POST['documento']);

        // Actualiza los datos de la persona en la base de datos
        // Si no tiene fecha de nacimiento inserta NULL
        $sql = "UPDATE personas SET
                primerNombre='$primer_nombre', segundoNombre='$segundo_nombre',
                primerApellido='$primer_apellido', segundoApellido='$segundo_apellido',
                fechaNacimiento=" . ($fecha_nac ? "'$fecha_nac'" : "NULL") . ",
                email='$email', telefono='$telefono', documento='$documento'
                WHERE identificacion = $id";

        if ($conn->query($sql)) {
            $msg = "Datos actualizados correctamente.";
        } else {
            $error = "Error al actualizar: " . $conn->error;
        }
    }
}

// ============================================
// BUSCAR PERSONAS
// ============================================
$busqueda = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'buscar') {

    // Captura los filtros de búsqueda
    $codigo = sanitize($_POST['buscar_codigo'] ?? '');
    $nombre = sanitize($_POST['buscar_nombre'] ?? '');

    // Construye el WHERE dinámicamente según los filtros ingresados
    $where = [];
    // Busca por código o documento si se ingresó
    if ($codigo) $where[] = "(p.identificacion LIKE '%$codigo%' OR p.documento LIKE '%$codigo%')";
    // Busca por nombre o apellidos si se ingresó
    if ($nombre) $where[] = "(p.primerNombre LIKE '%$nombre%' OR p.primerApellido LIKE '%$nombre%' OR p.segundoApellido LIKE '%$nombre%')";

    // Si hay filtros los une con AND, si no trae todos
    $where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

    // Busca personas con JOIN para traer descripción de género y tipo de documento
    // LIMIT 50 evita traer demasiados registros de una vez
    $busqueda = $conn->query("SELECT p.*, g.descripcion as genero_desc, d.tipoDocumento as tipo_doc_desc
                              FROM personas p
                              LEFT JOIN genero g ON p.idGenero = g.idGenero
                              LEFT JOIN documento d ON p.tipoDocumento = d.idDocumento
                              $where_sql ORDER BY p.primerApellido LIMIT 50");
}

// ============================================
// CARGAR PERSONA PARA EDITAR
// ============================================
// Si viene el parámetro 'editar' en la URL carga esa persona
if (isset($_GET['editar'])) {
    $id_editar = (int)$_GET['editar'];
    $r = $conn->query("SELECT p.*, g.descripcion as genero_desc
                       FROM personas p
                       LEFT JOIN genero g ON p.idGenero = g.idGenero
                       WHERE p.identificacion = $id_editar");
    // Guarda los datos o null si no encontró la persona
    $persona = $r ? $r->fetch_assoc() : null;
}

// Calcula el siguiente código disponible para nuevos estudiantes
// COALESCE devuelve 10000 si no hay ningún estudiante registrado aún
$r_next = $conn->query("SELECT COALESCE(MAX(identificacion)+1, 10000) as siguiente FROM personas WHERE funcion = 'Estudiante'");
$siguiente_codigo = $r_next->fetch_assoc()['siguiente'];

// Trae géneros y tipos de documento para los selects del formulario
$generos = $conn->query("SELECT * FROM genero");
$tipos_doc = $conn->query("SELECT * FROM documento");

// Incluye el header con el menú de navegación
include '../includes/header.php';
?>

<!-- Título de la página -->
<div class="page-header">
    <div>
        <div class="page-title">👤 Registro de Personas</div>
        <div class="page-subtitle">Administrar estudiantes, docentes y personal administrativo</div>
    </div>
</div>

<!-- Muestra mensaje de éxito si existe -->
<?php if ($msg): ?><div class="alert alert-success">✅ <?= $msg ?></div><?php endif; ?>

<!-- Muestra mensaje de error si existe -->
<?php if ($error): ?><div class="alert alert-error">⚠️ <?= $error ?></div><?php endif; ?>

<!-- Tarjeta del formulario, el título cambia según si es nuevo o edición -->
<div class="card">
    <div class="card-header">
        📋 <?= $persona ? 'Formulario de Modificación' : 'Nuevo Registro' ?>
    </div>
    <div class="card-body">
        <form method="POST">

            <!-- Si hay persona a editar la acción es modificar, si no es guardar -->
            <input type="hidden" name="action" value="<?= $persona ? 'modificar' : 'guardar' ?>">

            <!-- Selector de tipo de persona, solo aparece en nuevo registro -->
            <?php if (!$persona): ?>
                <div class="form-group mb-2">
                    <label>Tipo de Persona <span class="req">*</span></label>
                    <!-- Botones de radio para elegir Estudiante, Docente o Administrativo -->
                    <!-- Estudiante viene marcado por defecto con checked -->
                    <div class="radio-group">
                        <label><input type="radio" name="funcion" value="Estudiante" checked> Estudiante</label>
                        <label><input type="radio" name="funcion" value="Docente"> Docente</label>
                        <label><input type="radio" name="funcion" value="Administrativo"> Administrativo</label>
                    </div>
                </div>
            <?php endif; ?>

            <div class="form-grid">

                <!-- Campo del código, muestra el siguiente disponible si es nuevo -->
                <!-- readonly si se está editando para no cambiar el código -->
                <div class="form-group">
                    <label>Código <span class="req">*</span></label>
                    <input type="number" name="identificacion"
                        value="<?= $persona ? $persona['identificacion'] : $siguiente_codigo ?>"
                        <?= $persona ? 'readonly' : 'required' ?>>
                    <!-- Muestra el siguiente código disponible solo en nuevo registro -->
                    <?php if (!$persona): ?>
                        <small style="color:var(--texto-suave)">Siguiente disponible: <?= $siguiente_codigo ?></small>
                    <?php endif; ?>
                </div>

                <!-- Select de tipo de documento, deshabilitado si se está editando -->
                <div class="form-group">
                    <label>Tipo Documento</label>
                    <select name="tipoDocumento" <?= $persona ? 'disabled' : '' ?>>
                        <?php
                        $tipos_doc->data_seek(0);
                        while ($td = $tipos_doc->fetch_assoc()):
                        ?>
                            <!-- Marca el tipo de documento actual si se está editando -->
                            <option value="<?= $td['idDocumento'] ?>"
                                <?= ($persona && $persona['tipoDocumento'] == $td['idDocumento']) ? 'selected' : '' ?>>
                                <?= $td['tipoDocumento'] ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <!-- Campo del número de documento -->
                <div class="form-group">
                    <label>Número de Documento</label>
                    <input type="text" name="documento" value="<?= htmlspecialchars($persona['documento'] ?? '') ?>">
                </div>

                <!-- Campo primer nombre obligatorio -->
                <div class="form-group">
                    <label>Primer Nombre <span class="req">*</span></label>
                    <input type="text" name="primerNombre" value="<?= htmlspecialchars($persona['primerNombre'] ?? '') ?>" required>
                </div>

                <!-- Campo segundo nombre opcional -->
                <div class="form-group">
                    <label>Segundo Nombre</label>
                    <input type="text" name="segundoNombre" value="<?= htmlspecialchars($persona['segundoNombre'] ?? '') ?>">
                </div>

                <!-- Campo primer apellido obligatorio -->
                <div class="form-group">
                    <label>Primer Apellido <span class="req">*</span></label>
                    <input type="text" name="primerApellido" value="<?= htmlspecialchars($persona['primerApellido'] ?? '') ?>" required>
                </div>

                <!-- Campo segundo apellido opcional -->
                <div class="form-group">
                    <label>Segundo Apellido</label>
                    <input type="text" name="segundoApellido" value="<?= htmlspecialchars($persona['segundoApellido'] ?? '') ?>">
                </div>

                <!-- Campo fecha de nacimiento tipo date -->
                <div class="form-group">
                    <label>Fecha de Nacimiento</label>
                    <input type="date" name="fechaNacimiento" value="<?= $persona['fechaNacimiento'] ?? '' ?>">
                </div>

                <!-- Select de género, marca el actual si se está editando -->
                <div class="form-group">
                    <label>Género</label>
                    <select name="idGenero">
                        <?php
                        $generos->data_seek(0);
                        while ($g = $generos->fetch_assoc()):
                        ?>
                            <option value="<?= $g['idGenero'] ?>"
                                <?= ($persona && $persona['idGenero'] == $g['idGenero']) ? 'selected' : '' ?>>
                                <?= $g['descripcion'] ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <!-- Campo de correo electrónico -->
                <div class="form-group">
                    <label>Correo Electrónico</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($persona['email'] ?? '') ?>">
                </div>

                <!-- Campo de teléfono -->
                <div class="form-group">
                    <label>Teléfono</label>
                    <input type="text" name="telefono" value="<?= htmlspecialchars($persona['telefono'] ?? '') ?>">
                </div>
            </div>

            <div class="btn-toolbar mt-2">
                <!-- Botón principal, texto cambia según si es nuevo o edición -->
                <button type="submit" class="btn btn-primary">
                    💾 <?= $persona ? 'Guardar Cambios' : 'Registrar Persona' ?>
                </button>

                <!-- Si se edita muestra cancelar, si es nuevo muestra limpiar -->
                <?php if ($persona): ?>
                    <a href="/pages/personas.php" class="btn btn-outline">✕ Cancelar</a>
                <?php else: ?>
                    <!-- type="reset" limpia todos los campos del formulario -->
                    <button type="reset" class="btn btn-outline">↺ Limpiar</button>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Tarjeta de búsqueda de personas -->
<div class="card">
    <div class="card-header">🔍 Buscar Personas</div>
    <div class="card-body">
        <form method="POST">
            <!-- Indica a PHP que la acción es buscar -->
            <input type="hidden" name="action" value="buscar">
            <div class="form-grid">

                <!-- Campo para buscar por código o número de documento -->
                <div class="form-group">
                    <label>Código o Documento</label>
                    <!-- Mantiene el valor escrito después de buscar -->
                    <input type="text" name="buscar_codigo" placeholder="Ej: 14058" value="<?= htmlspecialchars($_POST['buscar_codigo'] ?? '') ?>">
                </div>

                <!-- Campo para buscar por nombre o apellido -->
                <div class="form-group">
                    <label>Nombre o Apellido</label>
                    <input type="text" name="buscar_nombre" placeholder="Ej: González" value="<?= htmlspecialchars($_POST['buscar_nombre'] ?? '') ?>">
                </div>

                <div class="form-group" style="justify-content: flex-end;">
                    <button type="submit" class="btn btn-secondary" style="margin-top:20px">🔍 Buscar</button>
                </div>
            </div>
        </form>

        <!-- Solo muestra la tabla si se realizó una búsqueda -->
        <?php if ($busqueda !== null): ?>
            <div class="table-wrapper mt-2">
                <table>
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Documento</th>
                            <th>Nombre Completo</th>
                            <th>Función</th>
                            <th>Email</th>
                            <th>Teléfono</th>
                            <th>Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Si no hay resultados muestra mensaje informativo -->
                        <?php if ($busqueda->num_rows === 0): ?>
                            <tr>
                                <td colspan="7" class="text-center" style="padding:20px; color:var(--texto-suave)">No se encontraron resultados</td>
                            </tr>
                        <?php else: ?>

                            <!-- Recorre y muestra cada persona encontrada -->
                            <?php while ($p = $busqueda->fetch_assoc()): ?>
                                <tr>
                                    <!-- Código en negrita -->
                                    <td><strong><?= $p['identificacion'] ?></strong></td>

                                    <!-- Documento o guión si no tiene -->
                                    <td><?= htmlspecialchars($p['documento'] ?? '—') ?></td>

                                    <!-- Nombre completo concatenando los 4 campos -->
                                    <!-- El segundo nombre solo se agrega si existe -->
                                    <td><?= htmlspecialchars($p['primerNombre'] . ' ' . ($p['segundoNombre'] ? $p['segundoNombre'] . ' ' : '') . $p['primerApellido'] . ' ' . $p['segundoApellido']) ?></td>

                                    <!-- Badge de color según la función de la persona -->
                                    <td>
                                        <span class="badge-estado badge-<?= strtolower($p['funcion'] ?? 'estudiante') ?>">
                                            <?= $p['funcion'] ?? 'Estudiante' ?>
                                        </span>
                                    </td>

                                    <!-- Email o guión si no tiene -->
                                    <td><?= htmlspecialchars($p['email'] ?? '—') ?></td>

                                    <!-- Teléfono o guión si no tiene -->
                                    <td><?= htmlspecialchars($p['telefono'] ?? '—') ?></td>

                                    <!-- Botón que lleva al formulario de edición pasando el ID por URL -->
                                    <td><a href="?editar=<?= $p['identificacion'] ?>" class="btn btn-sm btn-outline">✏️ Modificar</a></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Incluye el footer con los scripts globales -->
<?php include '../includes/footer.php'; ?>