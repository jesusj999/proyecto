<?php
$pageTitle = 'Registro de Usuarios';
require_once '../includes/config.php'; // Carga la conexión a BD y funciones como sanitize(), requireAuth(), isAdmin()
requireAuth([1, 2, 3]); // Solo usuarios autenticados con nivel 1, 2 o 3 pueden entrar

$msg = '';    // Variable para mensajes de éxito
$error = '';  // Variable para mensajes de error
$is_admin = isAdmin(); // Verifica si el usuario logueado es administrador (nivel 1)

// Si NO es admin, carga solo los datos de su propio usuario para que pueda editarse
if (!$is_admin) {
    $id_buscar = $_SESSION['usuario_id'];
    $r = $conn->query("SELECT u.*, n.descripcion as rol FROM usuarios u JOIN nivelusuario n ON u.idnivel = n.idNivel WHERE u.idUsuario = $id_buscar");
    $usuario_actual = $r ? $r->fetch_assoc() : null;
}

// Escucha cualquier envío de formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    // ===================== GUARDAR =====================
    // Se ejecuta cuando el admin envía el formulario de crear usuario
    if ($_POST['action'] === 'guardar' && $is_admin) {
        $id_persona = (int)$_POST['idPersona']; // Identificación de la persona (debe existir en tabla personas)
        $nivel      = (int)$_POST['idnivel'];   // Rol: 1=Admin, 2=Docente, 3=Estudiante
        $username   = sanitize($_POST['username']);   // Limpia el texto para evitar inyección SQL
        $password   = sanitize($_POST['contrasena']); // Limpia la contraseña

        // Verifica que la persona ya exista en la tabla personas
        // Si no existe, muestra error y no crea nada
        $check_persona = $conn->query("SELECT identificacion FROM personas WHERE identificacion = $id_persona");
        if ($check_persona->num_rows === 0) {
            $error = "No existe una persona con identificación $id_persona. Regístrela primero en Personas.";
        } else {

            // Verifica que el username no esté ya en uso
            $check = $conn->query("SELECT idUsuario FROM usuarios WHERE username='$username'");
            if ($check->num_rows > 0) {
                $error = "El username '$username' ya está en uso.";
            } else {
                $pass_hash = md5($password); // Encripta la contraseña con MD5

                // Inserta el usuario en la tabla usuarios
                if ($conn->query("INSERT INTO usuarios (idUsuario, idnivel, username, contrasena) VALUES ($id_persona, $nivel, '$username', '$pass_hash')")) {

                    $rol_ok = false; // Variable para saber si el insert del rol fue exitoso

                    // Según el nivel, inserta en la tabla correspondiente
                    if ($nivel == 2) {
                        // Es DOCENTE — inserta en tabla docentes
                        $nivelEscalafon = sanitize($_POST['nivelEscalafon']);
                        $tituloProf     = sanitize($_POST['tituloProf']);
                        $dirGrupo       = sanitize($_POST['dirGrupo']);
                        $sede           = sanitize($_POST['sede']);
                        $stmt2 = $conn->prepare("INSERT INTO docentes (idDocentes, nivelEscalafon, tituloProf, dirGrupo, sede) VALUES (?, ?, ?, ?, ?)");
                        $stmt2->bind_param("issss", $id_persona, $nivelEscalafon, $tituloProf, $dirGrupo, $sede);
                        $rol_ok = $stmt2->execute();
                        $stmt2->close();

                    } elseif ($nivel == 2) {
                        // Es ESTUDIANTE — inserta en tabla estudiantes
                        $nombreAcudiente    = sanitize($_POST['nombreAcudiente']);
                        $direccionAcudiente = sanitize($_POST['direccionAcudiente']);
                        $telAcudi           = sanitize($_POST['telAcudi']);
                        $instProcedencia    = sanitize($_POST['instProcedencia']);
                        $stmt2 = $conn->prepare("INSERT INTO estudiantes (identificacion, nombreAcudiente, direccionAcudiente, telAcudi, instProcedencia) VALUES (?, ?, ?, ?, ?)");
                        $stmt2->bind_param("sssss", $id_persona, $nombreAcudiente, $direccionAcudiente, $telAcudi, $instProcedencia);
                        $rol_ok = $stmt2->execute();
                        $stmt2->close();

                    } elseif ($nivel == 1) {
                        // Es ADMINISTRATIVO — inserta en tabla administrativos
                        $cargo            = sanitize($_POST['cargo']);
                        $tipo_vinculacion = sanitize($_POST['tipo_vinculacion']);
                        $stmt2 = $conn->prepare("INSERT INTO administrativos (idpersonas, cargo, tipo_vinculacion) VALUES (?, ?, ?)");
                        $stmt2->bind_param("iss", $id_persona, $cargo, $tipo_vinculacion);
                        $rol_ok = $stmt2->execute();
                        $stmt2->close();
                    }

                    // Muestra mensaje según si el rol se guardó bien o no
                    if ($rol_ok) {
                        $msg = "Usuario creado y registrado en su rol exitosamente.";
                    } else {
                        $error = "Usuario creado pero error en el rol: " . $conn->error;
                    }

                } else {
                    $error = "Error al crear usuario: " . $conn->error;
                }
            }
        }
    }

    // ===================== MODIFICAR =====================
    // Se ejecuta cuando alguien quiere cambiar username o contraseña
    if ($_POST['action'] === 'modificar') {
        $id_usuario = (int)$_POST['idUsuario'];

        // Si no es admin, solo puede modificarse a sí mismo
        if (!$is_admin && $id_usuario != $_SESSION['usuario_id']) {
            $error = "No tiene permisos para modificar este usuario.";
        } else {
            $nuevo_user = sanitize($_POST['nuevo_username']);
            $nueva_pass = sanitize($_POST['nueva_contrasena']);

            $updates = [];
            if ($nuevo_user) $updates[] = "username='$nuevo_user'";           // Solo actualiza si escribió algo
            if ($nueva_pass) $updates[] = "contrasena='" . md5($nueva_pass) . "'"; // Encripta la nueva contraseña

            if ($updates) {
                $conn->query("UPDATE usuarios SET " . implode(', ', $updates) . " WHERE idUsuario = $id_usuario");
                $msg = "Usuario actualizado.";
                if (!$is_admin) $_SESSION['usuario'] = $nuevo_user ?: $_SESSION['usuario']; // Actualiza la sesión si es el mismo usuario
            }
        }
    }

    // ===================== ELIMINAR =====================
    // Solo el admin puede eliminar usuarios
    if ($_POST['action'] === 'eliminar' && $is_admin) {
        $id_usuario = (int)$_POST['idUsuario'];
        if ($conn->query("DELETE FROM usuarios WHERE idUsuario = $id_usuario")) {
            $msg = "Usuario eliminado.";
        } else {
            $error = "Error al eliminar usuario.";
        }
    }
}

// ===================== BUSCAR USUARIO =====================
// El admin puede buscar un usuario por ID para editarlo
$usuario_edit = null;
if ($is_admin && isset($_POST['action']) && $_POST['action'] === 'buscar_usuario') {
    $id_buscar = (int)$_POST['buscar_id'];
    // Trae los datos del usuario junto con su rol y nombre completo desde personas
    $r = $conn->query("SELECT u.*, n.descripcion as rol,
                       CONCAT(p.primerNombre, ' ', p.primerApellido) as nombre_persona
                       FROM usuarios u
                       JOIN nivelusuario n ON u.idnivel = n.idNivel
                       LEFT JOIN personas p ON p.identificacion = u.idUsuario
                       WHERE u.idUsuario = $id_buscar");
    $usuario_edit = $r && $r->num_rows > 0 ? $r->fetch_assoc() : null;
    if (!$usuario_edit) $error = "No se encontró usuario con ID $id_buscar.";
}

// ===================== LISTA DE USUARIOS =====================
// Solo el admin ve la lista completa con todos los usuarios del sistema
$lista_usuarios = null;
if ($is_admin) {
    $lista_usuarios = $conn->query("SELECT u.*, n.descripcion as rol,
                                   CONCAT(p.primerNombre, ' ', p.primerApellido) as nombre_persona
                                   FROM usuarios u
                                   JOIN nivelusuario n ON u.idnivel = n.idNivel
                                   LEFT JOIN personas p ON p.identificacion = u.idUsuario
                                   ORDER BY u.idnivel, u.username");
}

// Carga los niveles de usuario para el select del formulario
$niveles = $conn->query("SELECT * FROM nivelusuario");
include '../includes/header.php'; // Carga el encabezado visual del sistema
?>

<div class="page-header">
    <div class="page-title">🔑 Registro de Usuarios</div>
    <div class="page-subtitle"><?= $is_admin ? 'Administrar todos los usuarios del sistema' : 'Gestionar mis datos de acceso' ?></div>
</div>

<?php if ($msg): ?><div class="alert alert-success">✅ <?= $msg ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-error">⚠️ <?= $error ?></div><?php endif; ?>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">

<div class="card">
    <div class="card-header">
        <?= $is_admin ? '➕ Crear / Modificar Usuario' : '✏️ Modificar mis Datos de Acceso' ?>
    </div>
    <div class="card-body">

    <?php if ($is_admin): ?>
    <!-- Formulario para buscar un usuario por ID y poder editarlo -->
    <form method="POST" style="margin-bottom:20px; padding-bottom:16px; border-bottom:1px solid var(--crema-oscuro)">
        <input type="hidden" name="action" value="buscar_usuario">
        <div class="form-group">
            <label>Identificación de la persona</label>
            <div style="display:flex; gap:8px">
                <input type="number" name="buscar_id" placeholder="Ej: 14058" style="flex:1">
                <button type="submit" class="btn btn-secondary btn-icon">🔍</button>
            </div>
        </div>
    </form>
    <?php endif; ?>

    <!-- Formulario para CREAR usuario — solo visible para admin cuando no está editando -->
    <?php if ($is_admin && !$usuario_edit): ?>
    <form method="POST">
        <input type="hidden" name="action" value="guardar"> <!-- Indica que es acción de guardar -->

        <div class="form-group mb-2">
            <label>Identificación Persona <span class="req">*</span></label>
            <input type="number" name="idPersona" required> <!-- Debe coincidir con una persona ya registrada -->
        </div>
        <div class="form-group mb-2">
            <label>Nivel de Usuario <span class="req">*</span></label>
            <!-- Al cambiar el nivel, muestra/oculta los campos extra del rol -->
            <select name="idnivel" id="idnivel" onchange="mostrarCamposRol()" required>
                <option value="">— Seleccionar —</option>
                <?php while ($n = $niveles->fetch_assoc()): ?>
                <option value="<?= $n['idNivel'] ?>"><?= $n['descripcion'] ?></option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="form-group mb-2">
            <label>Username <span class="req">*</span></label>
            <input type="text" name="username" placeholder="Nombre de usuario" required>
        </div>
        <div class="form-group mb-2">
            <label>Contraseña <span class="req">*</span></label>
            <input type="password" name="contrasena" required>
        </div>

        <!-- Campos extra para DOCENTE — se muestran solo si selecciona nivel 2 -->
        <div id="camposDocente" style="display:none; border-top:1px solid var(--crema-oscuro); padding-top:12px; margin-top:8px">
            <p style="font-weight:600; margin-bottom:8px">📋 Datos del Docente</p>
            <div class="form-group mb-2">
                <label>Nivel Escalafón</label>
                <input type="text" name="nivelEscalafon" placeholder="Ej: 2B">
            </div>
            <div class="form-group mb-2">
                <label>Título Profesional</label>
                <input type="text" name="tituloProf" placeholder="Ej: Licenciado en Matemáticas">
            </div>
            <div class="form-group mb-2">
                <label>Dirección de Grupo</label>
                <input type="text" name="dirGrupo" placeholder="Ej: 10A">
            </div>
            <div class="form-group mb-2">
                <label>Sede</label>
                <input type="text" name="sede" placeholder="Ej: Principal">
            </div>
        </div>

        <!-- Campos extra para ESTUDIANTE — se muestran solo si selecciona nivel 3 -->
        <div id="camposEstudiante" style="display:none; border-top:1px solid var(--crema-oscuro); padding-top:12px; margin-top:8px">
            <p style="font-weight:600; margin-bottom:8px">📋 Datos del Estudiante</p>
            <div class="form-group mb-2">
                <label>Nombre Acudiente</label>
                <input type="text" name="nombreAcudiente" placeholder="Nombre completo">
            </div>
            <div class="form-group mb-2">
                <label>Dirección Acudiente</label>
                <input type="text" name="direccionAcudiente" placeholder="Dirección">
            </div>
            <div class="form-group mb-2">
                <label>Teléfono Acudiente</label>
                <input type="text" name="telAcudi" placeholder="Ej: 3001234567">
            </div>
            <div class="form-group mb-2">
                <label>Institución Procedencia</label>
                <input type="text" name="instProcedencia" placeholder="Colegio anterior">
            </div>
        </div>

        <!-- Campos extra para ADMINISTRATIVO — se muestran solo si selecciona nivel 1 -->
        <div id="camposAdmin" style="display:none; border-top:1px solid var(--crema-oscuro); padding-top:12px; margin-top:8px">
            <p style="font-weight:600; margin-bottom:8px">📋 Datos del Administrativo</p>
            <div class="form-group mb-2">
                <label>Cargo</label>
                <input type="text" name="cargo" placeholder="Ej: Rector">
            </div>
            <div class="form-group mb-2">
                <label>Tipo Vinculación</label>
                <input type="text" name="tipo_vinculacion" placeholder="Ej: Planta, Provisional">
            </div>
        </div>

        <button type="submit" class="btn btn-primary" style="margin-top:12px">💾 Crear Usuario</button>
    </form>
    <?php endif; ?>

    <?php
    // Decide qué usuario mostrar en el formulario de edición:
    // Si admin buscó uno → muestra ese | Si no es admin → muestra el suyo propio
    $u_editar = $usuario_edit ?? (!$is_admin ? $usuario_actual : null);
    if ($u_editar):
    ?>
    <!-- Muestra info del usuario encontrado -->
    <div class="alert alert-info" style="margin-bottom:16px">
        <strong>Nombre:</strong> <?= htmlspecialchars($u_editar['nombre_persona'] ?? $u_editar['username']) ?><br>
        <strong>Tipo de Usuario:</strong> <?= htmlspecialchars($u_editar['rol']) ?><br>
        <strong>Username actual:</strong> <?= htmlspecialchars($u_editar['username']) ?>
    </div>
    <!-- Formulario para EDITAR username y/o contraseña -->
    <form method="POST">
        <input type="hidden" name="action" value="modificar">
        <input type="hidden" name="idUsuario" value="<?= $u_editar['idUsuario'] ?>">
        <div class="form-group mb-2">
            <label>Nombre de Usuario</label>
            <!-- Admin puede editarlo, otros usuarios lo ven pero no pueden cambiarlo -->
            <input type="text" name="username" value="<?= htmlspecialchars($u_editar['username']) ?>" <?= $is_admin ? '' : 'readonly' ?>>
        </div>
        <div class="form-group mb-2">
            <label>Contraseña actual</label>
            <input type="password" name="contrasena_actual" placeholder="Verificación">
        </div>
        <div class="form-group mb-2">
            <label>Nuevo Nombre de Usuario</label>
            <input type="text" name="nuevo_username" placeholder="Dejar en blanco para no cambiar">
        </div>
        <div class="form-group mb-2">
            <label>Nueva Contraseña</label>
            <input type="password" name="nueva_contrasena" placeholder="Dejar en blanco para no cambiar">
        </div>
        <div class="btn-toolbar">
            <button class="btn btn-primary">💾 Guardar Cambios</button>
            <?php if ($is_admin): ?>
            <!-- Botón eliminar solo visible para admin -->
            <button type="submit" name="action" value="eliminar" class="btn btn-danger"
                    onclick="return confirm('¿Eliminar usuario?')">🗑️ Eliminar</button>
            <?php endif; ?>
        </div>
    </form>
    <?php endif; ?>

    </div>
</div>

<!-- Tabla con todos los usuarios — solo visible para admin -->
<?php if ($is_admin): ?>
<div class="card">
    <div class="card-header">👥 Usuarios del Sistema</div>
    <div class="card-body" style="padding:0; max-height:500px; overflow-y:auto">
        <table>
            <thead>
                <tr><th>ID</th><th>Username</th><th>Nombre</th><th>Rol</th><th>Acción</th></tr>
            </thead>
            <tbody>
                <?php while ($u = $lista_usuarios->fetch_assoc()): ?>
                <tr>
                    <td><?= $u['idUsuario'] ?></td>
                    <td><strong><?= htmlspecialchars($u['username']) ?></strong></td>
                    <td><?= htmlspecialchars($u['nombre_persona'] ?? '—') ?></td>
                    <td>
                        <!-- Badge de color según el rol -->
                        <span class="badge-estado badge-<?= strtolower($u['rol']) ?>">
                            <?= $u['rol'] ?>
                        </span>
                    </td>
                    <td>
                        <!-- Botón para cargar ese usuario en el formulario de edición -->
                        <form method="POST" style="display:inline">
                            <input type="hidden" name="action" value="buscar_usuario">
                            <input type="hidden" name="buscar_id" value="<?= $u['idUsuario'] ?>">
                            <button class="btn btn-sm btn-outline">✏️</button>
                        </form>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

</div>

<script>
// Muestra u oculta los campos extra según el rol seleccionado
function mostrarCamposRol() {
    const nivel = document.getElementById('idnivel').value;
    document.getElementById('camposDocente').style.display    = (nivel == 2) ? 'block' : 'none';
    document.getElementById('camposEstudiante').style.display = (nivel == 3) ? 'block' : 'none';
    document.getElementById('camposAdmin').style.display      = (nivel == 1) ? 'block' : 'none';
}
</script>

<?php include '../includes/footer.php'; // Carga el pie de página del sistema ?>