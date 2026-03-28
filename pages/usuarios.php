<?php
$pageTitle = 'Registro de Usuarios';
require_once '../includes/config.php';
requireAuth([1, 2, 3]); // Todos pero con restricciones

$msg = '';
$error = '';
$is_admin = isAdmin();

// Solo admin puede buscar otros; no-admin solo ve el suyo
if (!$is_admin) {
    // Carga solo su propio usuario
    $id_buscar = $_SESSION['usuario_id'];
    $r = $conn->query("SELECT u.*, n.descripcion as rol FROM usuarios u JOIN nivelusuario n ON u.idnivel = n.idNivel WHERE u.idUsuario = $id_buscar");
    $usuario_actual = $r ? $r->fetch_assoc() : null;
}

// GUARDAR (solo admin puede crear/eliminar)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    if ($_POST['action'] === 'guardar' && $is_admin) {
        $id_persona = (int)$_POST['idPersona'];
        $nivel = (int)$_POST['idnivel'];
        $username = sanitize($_POST['username']);
        $password = sanitize($_POST['contrasena']);

        $check = $conn->query("SELECT idUsuario FROM usuarios WHERE username='$username'");
        if ($check->num_rows > 0) {
            $error = "El username '$username' ya está en uso.";
        } else {
            $pass_hash = md5($password);
            if ($conn->query("INSERT INTO usuarios (idUsuario, idnivel, username, contrasena) VALUES ($id_persona, $nivel, '$username', '$pass_hash')")) {
                $msg = "Usuario creado exitosamente.";
            } else {
                $error = "Error: " . $conn->error;
            }
        }
    }

    if ($_POST['action'] === 'modificar') {
        $id_usuario = (int)$_POST['idUsuario'];

        // No-admin solo puede modificarse a sí mismo
        if (!$is_admin && $id_usuario != $_SESSION['usuario_id']) {
            $error = "No tiene permisos para modificar este usuario.";
        } else {
            $nuevo_user = sanitize($_POST['nuevo_username']);
            $nueva_pass = sanitize($_POST['nueva_contrasena']);

            $updates = [];
            if ($nuevo_user) $updates[] = "username='$nuevo_user'";
            if ($nueva_pass) $updates[] = "contrasena='" . md5($nueva_pass) . "'";

            if ($updates) {
                $conn->query("UPDATE usuarios SET " . implode(', ', $updates) . " WHERE idUsuario = $id_usuario");
                $msg = "Usuario actualizado.";
                if (!$is_admin) $_SESSION['usuario'] = $nuevo_user ?: $_SESSION['usuario'];
            }
        }
    }

    if ($_POST['action'] === 'eliminar' && $is_admin) {
        $id_usuario = (int)$_POST['idUsuario'];
        if ($conn->query("DELETE FROM usuarios WHERE idUsuario = $id_usuario")) {
            $msg = "Usuario eliminado.";
        } else {
            $error = "Error al eliminar usuario.";
        }
    }
}

// BUSCAR usuario (admin)
$usuario_edit = null;
if ($is_admin && isset($_POST['action']) && $_POST['action'] === 'buscar_usuario') {
    $id_buscar = (int)$_POST['buscar_id'];
    $r = $conn->query("SELECT u.*, n.descripcion as rol,
                       CONCAT(p.primerNombre, ' ', p.primerApellido) as nombre_persona
                       FROM usuarios u
                       JOIN nivelusuario n ON u.idnivel = n.idNivel
                       LEFT JOIN personas p ON p.identificacion = u.idUsuario
                       WHERE u.idUsuario = $id_buscar");
    $usuario_edit = $r && $r->num_rows > 0 ? $r->fetch_assoc() : null;
    if (!$usuario_edit) $error = "No se encontró usuario con ID $id_buscar.";
}

// Lista usuarios (solo admin)
$lista_usuarios = null;
if ($is_admin) {
    $lista_usuarios = $conn->query("SELECT u.*, n.descripcion as rol,
                                   CONCAT(p.primerNombre, ' ', p.primerApellido) as nombre_persona
                                   FROM usuarios u
                                   JOIN nivelusuario n ON u.idnivel = n.idNivel
                                   LEFT JOIN personas p ON p.identificacion = u.idUsuario
                                   ORDER BY u.idnivel, u.username");
}

$niveles = $conn->query("SELECT * FROM nivelusuario");

include '../includes/header.php';
?>

<div class="page-header">
    <div class="page-title">🔑 Registro de Usuarios</div>
    <div class="page-subtitle"><?= $is_admin ? 'Administrar todos los usuarios del sistema' : 'Gestionar mis datos de acceso' ?></div>
</div>

<?php if ($msg): ?><div class="alert alert-success">✅ <?= $msg ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-error">⚠️ <?= $error ?></div><?php endif; ?>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">

<!-- Formulario de usuario -->
<div class="card">
    <div class="card-header">
        <?= $is_admin ? '➕ Crear / Modificar Usuario' : '✏️ Modificar mis Datos de Acceso' ?>
    </div>
    <div class="card-body">

    <?php if ($is_admin): ?>
    <!-- Admin: buscar por ID para editar -->
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

    <!-- Formulario crear (solo admin) -->
    <?php if ($is_admin && !$usuario_edit): ?>
    <form method="POST">
        <input type="hidden" name="action" value="guardar">
        <div class="form-group mb-2">
            <label>Identificación Persona <span class="req">*</span></label>
            <input type="number" name="idPersona" required>
        </div>
        <div class="form-group mb-2">
            <label>Nivel de Usuario <span class="req">*</span></label>
            <select name="idnivel" required>
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
        <button class="btn btn-primary">💾 Crear Usuario</button>
    </form>
    <?php endif; ?>

    <!-- Formulario editar -->
    <?php
    $u_editar = $usuario_edit ?? (!$is_admin ? $usuario_actual : null);
    if ($u_editar):
    ?>
    <div class="alert alert-info" style="margin-bottom:16px">
        <div>
            <strong>Nombre:</strong> <?= htmlspecialchars($u_editar['nombre_persona'] ?? $u_editar['username']) ?><br>
            <strong>Tipo de Usuario:</strong> <?= htmlspecialchars($u_editar['rol']) ?><br>
            <strong>Username actual:</strong> <?= htmlspecialchars($u_editar['username']) ?>
        </div>
    </div>
    <form method="POST">
        <input type="hidden" name="action" value="modificar">
        <input type="hidden" name="idUsuario" value="<?= $u_editar['idUsuario'] ?>">
        <div class="form-group mb-2">
            <label>Nombre de Usuario</label>
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
            <button type="submit" name="action" value="eliminar" class="btn btn-danger"
                    onclick="return confirm('¿Eliminar usuario?')">🗑️ Eliminar</button>
            <?php endif; ?>
        </div>
    </form>
    <?php endif; ?>
    </div>
</div>

<!-- Lista de usuarios (admin) -->
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
                        <span class="badge-estado badge-<?= strtolower($u['rol']) ?>">
                            <?= $u['rol'] ?>
                        </span>
                    </td>
                    <td>
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

<?php include '../includes/footer.php'; ?>
