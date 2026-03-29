<?php
// Título que aparece en la pestaña del navegador
$pageTitle = 'Notas del Estudiante';

// Incluye la conexión a la base de datos y funciones como isEstudiante(), requireAuth()
require_once '../includes/config.php';

// Verifica que haya un usuario logueado. 1=Admin, 2=Docente, 3=Estudiante
requireAuth([1, 2, 3]);

// Variable donde se guardarán las notas traídas de la base de datos
$notas = null;

// Variable donde se guardarán los datos del estudiante (nombre, grado, etc.)
$estudiante_info = null;

// Variable para mensajes de error
$error = '';

// Captura el grado que el usuario seleccionó en el formulario
// Si no seleccionó nada, queda en 0
$grado = (int)($_POST['idGrado'] ?? 0);

// ============================================
// SI EL USUARIO LOGUEADO ES ESTUDIANTE
// ============================================
if (isEstudiante()) {

    // Obtiene el username guardado en la sesión cuando hizo login
    $username = $_SESSION['usuario'];

    // Busca en la tabla usuarios el ID del estudiante
    // usando el username de la sesión y verificando que sea nivel 3
    $r_user = $conn->query("SELECT u.idUsuario FROM usuarios u 
                        WHERE u.username = '$username' 
                        AND u.idnivel = 3 LIMIT 1");

    // Guarda el resultado de la consulta o null si falló
    $user_data = $r_user ? $r_user->fetch_assoc() : null;

    // Extrae el idUsuario como número entero, o 0 si no encontró nada
    $codigo = $user_data ? (int)$user_data['idUsuario'] : 0;

    // Verifica que ese código exista en la tabla personas
    if ($codigo) {
        $check = $conn->query("SELECT identificacion FROM personas 
                               WHERE identificacion = $codigo");

        // Si no existe en personas, muestra error y pone código en 0
        if (!$check || $check->num_rows === 0) {
            $error = "El usuario no está registrado como persona en el sistema.";
            $codigo = 0;
        }
    }

    // Si el estudiante no seleccionó grado manualmente,
    // busca automáticamente el grado de su matrícula más reciente
    if (!$grado && $codigo) {
        // Trae el grado de la matrícula más reciente del estudiante
        $mat = $conn->query("SELECT idGrado FROM matricula 
                             WHERE idEstudiante = $codigo 
                             ORDER BY anio DESC LIMIT 1");

        // Si encontró matrícula, guarda el grado
        if ($mat && $mat->num_rows > 0) {
            $grado = (int)$mat->fetch_assoc()['idGrado'];
        }
    }

    // Si tiene código y grado, busca la información completa
    if ($codigo && $grado) {

        // Trae nombre, apellido, identificación y descripción del grado
        $r = $conn->query("SELECT p.primerNombre, p.primerApellido, p.identificacion,
                           g.descripcion as grado_desc
                           FROM personas p
                           JOIN grado g ON g.idgrado = $grado
                           WHERE p.identificacion = $codigo");

        // Guarda los datos del estudiante o null si no encontró
        $estudiante_info = $r ? $r->fetch_assoc() : null;

        // Si no encontró datos del estudiante muestra error
        if (!$estudiante_info) {
            $error = "No se encontraron datos del estudiante.";
        } else {
            // Busca todas las notas del estudiante en ese grado
            // Hace JOIN con materias para obtener el nombre de la materia
            // Hace LEFT JOIN con desempenio para obtener letra y nombre del desempeño
            $notas = $conn->query("SELECT n.*, m.nombre as materia_nombre,
                                   d.nombre as desempenio_nombre, d.letra as desempenio_letra
                                   FROM nota n
                                   JOIN materias m ON n.idmateria = m.idMateria
                                   LEFT JOIN desempenio d ON n.idDesempeño = d.idDesempenio
                                   WHERE n.idEstudiante = $codigo AND n.idgrado = $grado
                                   ORDER BY m.nombre");
        }

    // Si no tiene código válido muestra error
    } else if (!$codigo) {
        $error = "No se encontró el estudiante en el sistema.";
    }

// ============================================
// SI EL USUARIO ES ADMIN O DOCENTE
// ============================================
} else {

    // Captura el código que escribió el admin o docente en el formulario
    $codigo = (int)($_POST['codigo'] ?? 0);

    // Solo busca si ingresó código y seleccionó grado
    if ($codigo && $grado) {

        // Busca los datos del estudiante por código y grado
        $r = $conn->query("SELECT p.primerNombre, p.primerApellido, p.identificacion,
                           g.descripcion as grado_desc
                           FROM personas p
                           JOIN grado g ON g.idgrado = $grado
                           WHERE p.identificacion = $codigo");

        // Guarda los datos o null si no encontró
        $estudiante_info = $r ? $r->fetch_assoc() : null;

        // Si no encontró al estudiante muestra error
        if (!$estudiante_info) {
            $error = "No se encontró estudiante con código $codigo.";
        } else {
            // Busca las notas del estudiante igual que en el bloque de estudiante
            $notas = $conn->query("SELECT n.*, m.nombre as materia_nombre,
                                   d.nombre as desempenio_nombre, d.letra as desempenio_letra
                                   FROM nota n
                                   JOIN materias m ON n.idmateria = m.idMateria
                                   LEFT JOIN desempenio d ON n.idDesempeño = d.idDesempenio
                                   WHERE n.idEstudiante = $codigo AND n.idgrado = $grado
                                   ORDER BY m.nombre");
        }
    }
}

// Trae todos los grados para mostrarlos en el select del formulario
$grados = $conn->query("SELECT * FROM grado ORDER BY idgrado");

// Incluye el header con el menú de navegación
include '../includes/header.php';
?>

<!-- Título de la página -->
<div class="page-header">
    <div class="page-title">📊 Notas del Estudiante</div>
    <div class="page-subtitle">Calificaciones por asignatura y unidad</div>
</div>

<!-- Muestra el error si existe -->
<?php if ($error): ?>
    <div class="alert alert-error">⚠️ <?= $error ?></div>
<?php endif; ?>

<!-- Formulario de búsqueda -->
<div class="card">
    <div class="card-header">🔍 Consultar Notas</div>
    <div class="card-body">
        <form method="POST">
            <div style="display:flex; gap:12px; align-items:flex-end; flex-wrap:wrap">

                <!-- Solo muestra el campo código si NO es estudiante -->
                <?php if (!isEstudiante()): ?>
                    <div class="form-group">
                        <label>Código del Estudiante</label>
                        <!-- Mantiene el valor escrito si se envió el formulario -->
                        <input type="number" name="codigo"
                            value="<?= htmlspecialchars($_POST['codigo'] ?? '') ?>"
                            placeholder="Ej: 14189" style="width:180px">
                    </div>
                <?php endif; ?>

                <!-- Select de grados, marca como seleccionado el que ya eligió -->
                <div class="form-group">
                    <label>Grado</label>
                    <select name="idGrado">
                        <option value="">— Seleccionar —</option>
                        <?php while ($g = $grados->fetch_assoc()): ?>
                            <option value="<?= $g['idgrado'] ?>"
                                <?= $grado == $g['idgrado'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($g['descripcion']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <!-- Botón para enviar el formulario -->
                <button type="submit" class="btn btn-secondary">🔍 Buscar</button>
            </div>
        </form>
    </div>
</div>

<!-- Solo muestra la información si encontró al estudiante y tiene notas -->
<?php if ($estudiante_info && $notas): ?>

    <!-- Tarjeta con datos del estudiante -->
    <div class="card">
        <div class="card-header">📋 Información del Estudiante</div>
        <div class="card-body">
            <div style="display:flex; gap:32px; flex-wrap:wrap">
                <div>
                    <div style="font-size:12px; color:var(--texto-suave)">Código</div>
                    <!-- Muestra el código/identificación del estudiante -->
                    <div style="font-weight:700; font-size:18px"><?= $estudiante_info['identificacion'] ?></div>
                </div>
                <div>
                    <div style="font-size:12px; color:var(--texto-suave)">Nombre</div>
                    <!-- Concatena primer nombre y primer apellido -->
                    <div style="font-weight:600">
                        <?= htmlspecialchars($estudiante_info['primerNombre'] . ' ' . $estudiante_info['primerApellido']) ?>
                    </div>
                </div>
                <div>
                    <div style="font-size:12px; color:var(--texto-suave)">Grado</div>
                    <!-- Muestra la descripción del grado (ej: Tercero) -->
                    <div style="font-weight:600"><?= htmlspecialchars($estudiante_info['grado_desc']) ?></div>
                </div>
            </div>
        </div>
    </div>

    <?php
    // Organiza las notas en un array por nombre de materia y número de informe
    // Estructura: $materias_notas['Matemáticas'][1] = datos de la nota
    $materias_notas = [];
    if ($notas->num_rows > 0) {
        while ($n = $notas->fetch_assoc()) {
            $materias_notas[$n['materia_nombre']][$n['num_informe']] = $n;
        }
    }
    ?>

    <!-- Tabla de calificaciones -->
    <div class="card">
        <div class="card-header">📚 Calificaciones por Asignatura</div>
        <div class="card-body" style="padding:0; overflow-x:auto">

            <!-- Si no hay notas muestra mensaje -->
            <?php if (empty($materias_notas)): ?>
                <div style="padding:20px; text-align:center; color:var(--texto-suave)">
                    No hay notas registradas para este estudiante
                </div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <!-- Columna del nombre de la asignatura -->
                            <th>Asignatura</th>
                            <!-- Columnas para cada unidad del 1 al 10 -->
                            <th>U1</th><th>U2</th><th>U3</th><th>U4</th><th>U5</th>
                            <th>U6</th><th>U7</th><th>U8</th><th>U9</th><th>U10</th>
                            <!-- Columna del desempeño final -->
                            <th>Desempeño</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Recorre cada materia del array -->
                        <?php foreach ($materias_notas as $materia => $informes): ?>
                            <!-- Toma el primer informe disponible de esa materia -->
                            <?php $n = reset($informes); ?>
                            <tr>
                                <!-- Nombre de la materia en negrita -->
                                <td><strong><?= htmlspecialchars($materia) ?></strong></td>

                                <!-- Recorre las 10 unidades -->
                                <?php for ($u = 1; $u <= 10; $u++): ?>
                                    <td class="text-center">
                                        <?php
                                        // Busca el valor de la unidad (Unidad1, Unidad2, etc.)
                                        $val = $n["Unidad$u"] ?? null;

                                        // Solo muestra si tiene valor mayor a 0
                                        if ($val !== null && $val > 0) {
                                            // Asigna clase CSS según el rango de la nota
                                            if ($val < 30) $clase = 'nota-bajo';        // Menos de 3.0
                                            elseif ($val < 40) $clase = 'nota-basico'; // Entre 3.0 y 3.9
                                            elseif ($val < 46) $clase = 'nota-alto';   // Entre 4.0 y 4.5
                                            else $clase = 'nota-superior';              // 4.6 o más

                                            // Divide entre 10 para convertir ej: 45 → 4.5
                                            echo "<span class='$clase'>" . number_format($val / 10, 1) . "</span>";
                                        } else {
                                            // Si no hay nota muestra un guión gris
                                            echo '<span style="color:#ccc">—</span>';
                                        }
                                        ?>
                                    </td>
                                <?php endfor; ?>

                                <!-- Columna del desempeño -->
                                <td class="text-center">
                                    <?php if ($n['desempenio_letra']): ?>
                                        <!-- Muestra letra y nombre del desempeño (ej: E — Superior) -->
                                        <strong><?= $n['desempenio_letra'] ?></strong> — <?= $n['desempenio_nombre'] ?>
                                    <?php else: ?>—<?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- Leyenda explicando los colores de las notas -->
    <div class="card">
        <div class="card-body">
            <div style="display:flex; gap:24px; flex-wrap:wrap; font-size:12px">
                <span><span class="nota-bajo">B</span> — Bajo (1.0–2.9)</span>
                <span><span class="nota-basico">S</span> — Básico (3.0–3.9)</span>
                <span><span class="nota-alto">A</span> — Alto (4.0–4.5)</span>
                <span><span class="nota-superior">E</span> — Superior (4.6–5.0)</span>
            </div>
        </div>
    </div>

<?php endif; ?>

<!-- Incluye el footer con los scripts de JavaScript -->
<?php include '../includes/footer.php'; ?>