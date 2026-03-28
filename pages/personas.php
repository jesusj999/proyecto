<?php
$pageTitle = 'Registro de Personas';
require_once '../includes/config.php';
requireAuth([1]); // Solo admin

$msg = '';
$error = '';
$persona = null;

// GUARDAR nueva persona
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    if ($_POST['action'] === 'guardar') {
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
        $funcion = sanitize($_POST['funcion']); // Estudiante, Docente, Administrativo
        $documento = sanitize($_POST['documento']);

        // Verificar si ya existe
        $check = $conn->query("SELECT identificacion FROM personas WHERE identificacion = $id");
        if ($check->num_rows > 0) {
            $error = "Ya existe una persona con el código $id.";
            // Sugerir siguiente código
            $next = $conn->query("SELECT MAX(identificacion)+1 as siguiente FROM personas");
            $siguiente = $next->fetch_assoc()['siguiente'];
            $error .= " Puede usar el código: $siguiente";
        } else {
            $sql = "INSERT INTO personas (identificacion, tipoDocumento, primerNombre, segundoNombre,
                    primerApellido, segundoApellido, fechaNacimiento, idGenero, email, telefono, funcion, documento)
                    VALUES ($id, $tipo_doc, '$primer_nombre', '$segundo_nombre',
                    '$primer_apellido', '$segundo_apellido', " . ($fecha_nac ? "'$fecha_nac'" : "NULL") . ",
                    $genero, '$email', '$telefono', '$funcion', '$documento')";

            if ($conn->query($sql)) {
                // Insertar en tabla específica
                if ($funcion === 'Estudiante') {
                    $conn->query("INSERT INTO estudiantes (identificacion) VALUES ($id)");
                } elseif ($funcion === 'Docente') {
                    $conn->query("INSERT INTO docentes (idDocentes) VALUES ($id)");
                } elseif ($funcion === 'Administrativo') {
                    $conn->query("INSERT INTO administrativos (idpersonas) VALUES ($id)");
                }
                $msg = "Persona registrada exitosamente con código $id.";
            } else {
                $error = "Error al guardar: " . $conn->error;
            }
        }
    }

    if ($_POST['action'] === 'modificar') {
        $id = (int)$_POST['identificacion'];
        $primer_nombre = sanitize($_POST['primerNombre']);
        $segundo_nombre = sanitize($_POST['segundoNombre']);
        $primer_apellido = sanitize($_POST['primerApellido']);
        $segundo_apellido = sanitize($_POST['segundoApellido']);
        $fecha_nac = sanitize($_POST['fechaNacimiento']);
        $email = sanitize($_POST['email']);
        $telefono = sanitize($_POST['telefono']);
        $documento = sanitize($_POST['documento']);

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

// BUSCAR persona
$busqueda = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'buscar') {
    $codigo = sanitize($_POST['buscar_codigo'] ?? '');
    $nombre = sanitize($_POST['buscar_nombre'] ?? '');

    $where = [];
    if ($codigo) $where[] = "(p.identificacion LIKE '%$codigo%' OR p.documento LIKE '%$codigo%')";
    if ($nombre) $where[] = "(p.primerNombre LIKE '%$nombre%' OR p.primerApellido LIKE '%$nombre%' OR p.segundoApellido LIKE '%$nombre%')";

    $where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
    $busqueda = $conn->query("SELECT p.*, g.descripcion as genero_desc, d.tipoDocumento as tipo_doc_desc
                              FROM personas p
                              LEFT JOIN genero g ON p.idGenero = g.idGenero
                              LEFT JOIN documento d ON p.tipoDocumento = d.idDocumento
                              $where_sql ORDER BY p.primerApellido LIMIT 50");
}

// CARGAR persona para editar
if (isset($_GET['editar'])) {
    $id_editar = (int)$_GET['editar'];
    $r = $conn->query("SELECT p.*, g.descripcion as genero_desc
                       FROM personas p
                       LEFT JOIN genero g ON p.idGenero = g.idGenero
                       WHERE p.identificacion = $id_editar");
    $persona = $r ? $r->fetch_assoc() : null;
}

// Siguiente código disponible
$r_next = $conn->query("SELECT COALESCE(MAX(identificacion)+1, 10000) as siguiente FROM personas WHERE funcion = 'Estudiante'");
$siguiente_codigo = $r_next->fetch_assoc()['siguiente'];

// Listas
$generos = $conn->query("SELECT * FROM genero");
$tipos_doc = $conn->query("SELECT * FROM documento");

include '../includes/header.php';
?>

<div class="page-header">
    <div>
        <div class="page-title">👤 Registro de Personas</div>
        <div class="page-subtitle">Administrar estudiantes, docentes y personal administrativo</div>
    </div>
</div>

<?php if ($msg): ?><div class="alert alert-success">✅ <?= $msg ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-error">⚠️ <?= $error ?></div><?php endif; ?>

<!-- Formulario Nuevo Registro / Edición -->
<div class="card">
    <div class="card-header">
        📋 <?= $persona ? 'Formulario de Modificación' : 'Nuevo Registro' ?>
    </div>
    <div class="card-body">
        <form method="POST">
            <input type="hidden" name="action" value="<?= $persona ? 'modificar' : 'guardar' ?>">

            <!-- Tipo de persona -->
            <?php if (!$persona): ?>
            <div class="form-group mb-2">
                <label>Tipo de Persona <span class="req">*</span></label>
                <div class="radio-group">
                    <label><input type="radio" name="funcion" value="Estudiante" checked> Estudiante</label>
                    <label><input type="radio" name="funcion" value="Docente"> Docente</label>
                    <label><input type="radio" name="funcion" value="Administrativo"> Administrativo</label>
                </div>
            </div>
            <?php endif; ?>

            <div class="form-grid">
                <div class="form-group">
                    <label>Código <span class="req">*</span></label>
                    <input type="number" name="identificacion"
                           value="<?= $persona ? $persona['identificacion'] : $siguiente_codigo ?>"
                           <?= $persona ? 'readonly' : 'required' ?>>
                    <?php if (!$persona): ?>
                    <small style="color:var(--texto-suave)">Siguiente disponible: <?= $siguiente_codigo ?></small>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label>Tipo Documento</label>
                    <select name="tipoDocumento" <?= $persona ? 'disabled' : '' ?>>
                        <?php
                        $tipos_doc->data_seek(0);
                        while ($td = $tipos_doc->fetch_assoc()):
                        ?>
                        <option value="<?= $td['idDocumento'] ?>"
                            <?= ($persona && $persona['tipoDocumento'] == $td['idDocumento']) ? 'selected' : '' ?>>
                            <?= $td['tipoDocumento'] ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Número de Documento</label>
                    <input type="text" name="documento" value="<?= htmlspecialchars($persona['documento'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label>Primer Nombre <span class="req">*</span></label>
                    <input type="text" name="primerNombre" value="<?= htmlspecialchars($persona['primerNombre'] ?? '') ?>" required>
                </div>

                <div class="form-group">
                    <label>Segundo Nombre</label>
                    <input type="text" name="segundoNombre" value="<?= htmlspecialchars($persona['segundoNombre'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label>Primer Apellido <span class="req">*</span></label>
                    <input type="text" name="primerApellido" value="<?= htmlspecialchars($persona['primerApellido'] ?? '') ?>" required>
                </div>

                <div class="form-group">
                    <label>Segundo Apellido</label>
                    <input type="text" name="segundoApellido" value="<?= htmlspecialchars($persona['segundoApellido'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label>Fecha de Nacimiento</label>
                    <input type="date" name="fechaNacimiento" value="<?= $persona['fechaNacimiento'] ?? '' ?>">
                </div>

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

                <div class="form-group">
                    <label>Correo Electrónico</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($persona['email'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label>Teléfono</label>
                    <input type="text" name="telefono" value="<?= htmlspecialchars($persona['telefono'] ?? '') ?>">
                </div>
            </div>

            <div class="btn-toolbar mt-2">
                <button type="submit" class="btn btn-primary">
                    💾 <?= $persona ? 'Guardar Cambios' : 'Registrar Persona' ?>
                </button>
                <?php if ($persona): ?>
                <a href="/pages/personas.php" class="btn btn-outline">✕ Cancelar</a>
                <?php else: ?>
                <button type="reset" class="btn btn-outline">↺ Limpiar</button>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Búsqueda -->
<div class="card">
    <div class="card-header">🔍 Buscar Personas</div>
    <div class="card-body">
        <form method="POST">
            <input type="hidden" name="action" value="buscar">
            <div class="form-grid">
                <div class="form-group">
                    <label>Código o Documento</label>
                    <input type="text" name="buscar_codigo" placeholder="Ej: 14058" value="<?= htmlspecialchars($_POST['buscar_codigo'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Nombre o Apellido</label>
                    <input type="text" name="buscar_nombre" placeholder="Ej: González" value="<?= htmlspecialchars($_POST['buscar_nombre'] ?? '') ?>">
                </div>
                <div class="form-group" style="justify-content: flex-end;">
                    <button type="submit" class="btn btn-secondary" style="margin-top:20px">🔍 Buscar</button>
                </div>
            </div>
        </form>

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
                    <?php if ($busqueda->num_rows === 0): ?>
                    <tr><td colspan="7" class="text-center" style="padding:20px; color:var(--texto-suave)">No se encontraron resultados</td></tr>
                    <?php else: ?>
                    <?php while ($p = $busqueda->fetch_assoc()): ?>
                    <tr>
                        <td><strong><?= $p['identificacion'] ?></strong></td>
                        <td><?= htmlspecialchars($p['documento'] ?? '—') ?></td>
                        <td><?= htmlspecialchars($p['primerNombre'] . ' ' . ($p['segundoNombre'] ? $p['segundoNombre'] . ' ' : '') . $p['primerApellido'] . ' ' . $p['segundoApellido']) ?></td>
                        <td>
                            <span class="badge-estado badge-<?= strtolower($p['funcion'] ?? 'estudiante') ?>">
                                <?= $p['funcion'] ?? 'Estudiante' ?>
                            </span>
                        </td>
                        <td><?= htmlspecialchars($p['email'] ?? '—') ?></td>
                        <td><?= htmlspecialchars($p['telefono'] ?? '—') ?></td>
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

<?php include '../includes/footer.php'; ?>
