<?php
// ==========================
// CONFIGURACIÓN INICIAL
// ==========================

// Título de la página
$pageTitle = 'Listar Estudiantes';

// Conexión a la base de datos
require_once '../includes/config.php';

// Solo permite acceso a administrador (1) y docentes (2)
requireAuth([1, 2]);

// Variable para mensajes (por si luego quieres usarla)
$msg = '';

// Variable donde se guardará la lista de estudiantes
$lista_estudiantes = null;

// Año actual
$anio_actual = date('Y');


// ==========================
// CAPTURA DE FILTROS
// ==========================

// Captura el grado seleccionado (puede venir por POST o GET)
$grado_sel = (int)($_POST['idGrado'] ?? $_GET['grado'] ?? 0);

// Captura el grupo seleccionado
$grupo_sel = (int)($_POST['idGrupo'] ?? $_GET['grupo'] ?? 0);


// ==========================
// CONSULTA A LA BASE DE DATOS
// ==========================

// Solo ejecuta la consulta si hay grado y grupo
if ($grado_sel && $grupo_sel) {

    $lista_estudiantes = $conn->query("
        SELECT 
            p.identificacion,              -- Código del estudiante
            p.primerNombre,               -- Nombre
            p.primerApellido,             -- Primer apellido
            p.segundoApellido,            -- Segundo apellido
            p.documento,                  -- Documento
            p.email,                      -- Correo
            p.telefono,                   -- Teléfono
            ea.descripcion as estado      -- Estado (Activo, Retirado, etc.)
        FROM matricula m
        JOIN personas p ON m.idEstudiante = p.identificacion
        JOIN estado_actual ea ON m.idEstadoActual = ea.idEstado
        WHERE m.idGrado = $grado_sel 
        AND m.idGrupo = $grupo_sel
        AND m.anio = $anio_actual
        ORDER BY p.primerApellido, p.primerNombre
    ");
}

// Consulta para llenar el select de grados
$grados = $conn->query("SELECT * FROM grado ORDER BY idgrado");

// Incluye el encabezado (header)
include '../includes/header.php';
?>

<!-- ==========================
     TÍTULO DE LA PÁGINA
========================== -->
<div class="page-header">
    <div class="page-title">👥 Listar Estudiantes</div>
    <div class="page-subtitle">Ver estudiantes matriculados por grado y grupo</div>
</div>


<!-- ==========================
     FORMULARIO DE FILTRO
========================== -->
<div class="card">
    <div class="card-header">🔍 Filtrar Estudiantes</div>
    <div class="card-body">

        <!-- Formulario que envía los datos -->
        <form method="POST">

            <div style="display:flex; gap:12px; align-items:flex-end; flex-wrap:wrap">

                <!-- SELECT GRADO -->
                <div class="form-group" style="min-width:160px">
                    <label>Grado</label>

                    <!-- Cuando cambia el grado llama JS -->
                    <select name="idGrado" id="sel_grado_list" onchange="cargarGruposLista()">
                        <option value="">— Seleccionar —</option>

                        <?php while ($g = $grados->fetch_assoc()): ?>
                        <option value="<?= $g['idgrado'] ?>" 
                            <?= $grado_sel == $g['idgrado'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($g['descripcion']) ?>
                        </option>
                        <?php endwhile; ?>

                    </select>
                </div>


                <!-- SELECT GRUPO -->
                <div class="form-group" style="min-width:160px">
                    <label>Grupo</label>

                    <select name="idGrupo" id="sel_grupo_list">

                        <!-- Si ya hay grupo seleccionado -->
                        <?php if ($grupo_sel): ?>
                        <option value="<?= $grupo_sel ?>" selected>Grupo actual</option>

                        <!-- Si no hay -->
                        <?php else: ?>
                        <option value="">— Seleccionar Grado —</option>
                        <?php endif; ?>

                    </select>
                </div>


                <!-- BOTÓN BUSCAR -->
                <button type="submit" class="btn btn-secondary">
                    🔍 Buscar
                </button>

            </div>
        </form>
    </div>
</div>


<!-- ==========================
     TABLA DE RESULTADOS
========================== -->
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

                    <!-- Solo admin ve acciones -->
                    <?php if (isAdmin()): ?>
                    <th>Acción</th>
                    <?php endif; ?>
                </tr>
            </thead>

            <tbody>

                <!-- Si no hay estudiantes -->
                <?php if ($lista_estudiantes->num_rows === 0): ?>
                <tr>
                    <td colspan="6" style="padding:20px; text-align:center">
                        No hay estudiantes en este grupo
                    </td>
                </tr>

                <!-- Si hay estudiantes -->
                <?php else: ?>
                <?php while ($est = $lista_estudiantes->fetch_assoc()): ?>

                <tr>
                    <!-- Código -->
                    <td><strong><?= $est['identificacion'] ?></strong></td>

                    <!-- Apellidos -->
                    <td>
                        <?= htmlspecialchars($est['primerApellido'] . ' ' . ($est['segundoApellido'] ?? '')) ?>
                    </td>

                    <!-- Nombre -->
                    <td><?= htmlspecialchars($est['primerNombre']) ?></td>

                    <!-- Documento -->
                    <td><?= htmlspecialchars($est['documento'] ?? '—') ?></td>

                    <!-- Estado -->
                    <td><?= $est['estado'] ?></td>

                    <!-- Botón editar solo admin -->
                    <?php if (isAdmin()): ?>
                    <td>
                        <a href="../pages/personas.php?editar=<?= $est['identificacion'] ?>">
                            ✏️ Editar
                        </a>
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


<!-- ==========================
     JAVASCRIPT PARA GRUPOS
========================== -->
<script>
function cargarGruposLista() {

    // Obtiene el grado seleccionado
    const grado = document.getElementById('sel_grado_list').value;

    // Select de grupos
    const sel = document.getElementById('sel_grupo_list');

    // Si no hay grado, limpia
    if (!grado) {
        sel.innerHTML = '<option value="">— Seleccionar Grado —</option>';
        return;
    }

    // Petición al servidor
    fetch('../ajax/get_grupos.php?grado=' + grado)
        .then(r => r.json())
        .then(data => {

            // Limpia opciones
            sel.innerHTML = '<option value="">— Seleccionar Grupo —</option>';

            // Llena con los grupos
            data.forEach(g => {
                sel.innerHTML += `<option value="${g.idGrupo}">
                    ${g.nombre} (${g.sede})
                </option>`;
            });
        });
}

// Si ya hay grado seleccionado, carga grupos automáticamente
if (document.getElementById('sel_grado_list').value) {
    cargarGruposLista();
}
</script>


<?php
// Incluye el footer
include '../includes/footer.php';
?>