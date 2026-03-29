<?php
// Título de la página
$pageTitle = 'Calificación de Estudiantes';

// Conexión a la base de datos
require_once '../includes/config.php';

// Permite acceso solo a admin (1) y docente (2)
requireAuth([1, 2]);

// Variables de mensajes
$msg = '';
$error = '';

// Año actual
$anio_actual = date('Y');

// Variables principales
$estudiantes_cal = null; // guardará estudiantes
$logros_materia = null;  // guardará logros


// ==========================
// FILTROS
// ==========================

// Si es admin, puede elegir docente, si no, toma el logueado
$docente_sel = isAdmin() ? (int)($_POST['idDocente'] ?? 0) : (int)$_SESSION['identificacion'];

// Otros filtros
$grado_sel = (int)($_POST['idGrado'] ?? 0);
$grupo_sel = (int)($_POST['idGrupo'] ?? 0);
$materia_sel = (int)($_POST['idMateria'] ?? 0);
$unidad_sel = (int)($_POST['idUnidad'] ?? 1);
$informe_sel = (int)($_POST['informe'] ?? 1);
$anio_sel = (int)($_POST['anio'] ?? $anio_actual);


// ==========================
// GUARDAR NOTAS
// ==========================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'guardar_notas') {

    // Datos enviados desde el formulario
    $ids = $_POST['id_est'] ?? [];
    $unidades = $_POST['unidad'] ?? [];
    $logros1 = $_POST['logro1'] ?? [];
    $logros2 = $_POST['logro2'] ?? [];
    $logros3 = $_POST['logro3'] ?? [];
    $desempenos = $_POST['desempenio'] ?? [];
    $observaciones = $_POST['observacion'] ?? [];

    // Recorrer cada estudiante
    foreach ($ids as $i => $id_est) {

        $id_est = (int)$id_est;

        // Valores de cada campo
        $unidad_val = (int)($unidades[$i] ?? 0);
        $logro1_val = !empty($logros1[$i]) ? (int)$logros1[$i] : 'NULL';
        $logro2_val = !empty($logros2[$i]) ? (int)$logros2[$i] : 'NULL';
        $logro3_val = !empty($logros3[$i]) ? (int)$logros3[$i] : 'NULL';
        $desemp_val = !empty($desempenos[$i]) ? (int)$desempenos[$i] : 'NULL';

        // Limpia texto
        $obs_val = sanitize($observaciones[$i] ?? '');

        // Verifica si ya existe nota
        $check = $conn->query("SELECT idEstudiante FROM nota 
        WHERE idEstudiante=$id_est AND idmateria=$materia_sel 
        AND idgrado=$grado_sel AND num_informe=$informe_sel AND anio=$anio_sel");

        // Define columna dinámica (Unidad1, Unidad2, etc.)
        $unidad_col = "Unidad$unidad_sel";

        // Si ya existe -> UPDATE
        if ($check && $check->num_rows > 0) {

            $conn->query("UPDATE nota SET 
            $unidad_col=$unidad_val,
            Logro_1=$logro1_val, 
            Logro_2=$logro2_val, 
            Logro_3=$logro3_val,
            idDesempeño=$desemp_val, 
            descripcion='$obs_val'
            WHERE idEstudiante=$id_est 
            AND idmateria=$materia_sel
            AND idgrado=$grado_sel 
            AND num_informe=$informe_sel 
            AND anio=$anio_sel");

        } else {

            // Si no existe -> INSERT
            $conn->query("INSERT INTO nota 
            (idEstudiante, num_informe, idmateria, idgrado, anio, $unidad_col, Logro_1, Logro_2, Logro_3, idDesempeño, descripcion)
            VALUES ($id_est, $informe_sel, $materia_sel, $grado_sel, $anio_sel, $unidad_val, $logro1_val, $logro2_val, $logro3_val, $desemp_val, '$obs_val')");
        }
    }

    $msg = "Calificaciones guardadas exitosamente.";
}


// ==========================
// CARGAR ESTUDIANTES
// ==========================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $grado_sel && $grupo_sel && $materia_sel) {

    // Consulta estudiantes
    $estudiantes_cal = $conn->query("SELECT p.identificacion, p.primerNombre, p.primerApellido,
    n.Unidad1, n.Unidad2, n.Unidad3, n.Unidad4, n.Unidad5,
    n.Logro_1, n.Logro_2, n.Logro_3, n.idDesempeño, n.descripcion
    FROM matricula m
    JOIN personas p ON m.idEstudiante = p.identificacion
    LEFT JOIN nota n ON (
        n.idEstudiante = p.identificacion
        AND n.idmateria = $materia_sel 
        AND n.idgrado = $grado_sel
        AND n.num_informe = $informe_sel 
        AND n.anio = $anio_sel)
    WHERE m.idGrado = $grado_sel 
    AND m.idGrupo = $grupo_sel
    AND m.anio = $anio_sel 
    AND m.idEstadoActual = 1
    ORDER BY p.primerApellido, p.primerNombre");

    // Consulta logros
    $logros_materia = $conn->query("SELECT idlogro, descripcion, unidad 
    FROM logros
    WHERE idMateria = $materia_sel 
    AND idGrado = $grado_sel
    ORDER BY unidad, idlogro");
}


// ==========================
// DATOS PARA SELECTS
// ==========================

// Docentes (solo admin)
$docentes = isAdmin() ? $conn->query("SELECT d.idDocentes, 
CONCAT(p.primerNombre, ' ', p.primerApellido) as nombre
FROM docentes d 
JOIN personas p ON d.idDocentes = p.identificacion
ORDER BY p.primerApellido") : null;

// Grados
$grados = $conn->query("SELECT * FROM grado ORDER BY idgrado");

// Desempeños
$desempenos = $conn->query("SELECT * FROM desempenio ORDER BY minimo");


// ==========================
// MATERIAS DEL DOCENTE
// ==========================
if ($docente_sel) {

    $materias_doc = $conn->query("SELECT DISTINCT m.idMateria, m.nombre 
    FROM horario h
    JOIN materias m ON h.idMateria = m.idMateria
    WHERE h.idDocente = $docente_sel 
    AND h.año = $anio_sel
    ORDER BY m.nombre");

} else {

    $materias_doc = $conn->query("SELECT * FROM materias ORDER BY nombre");
}


// Incluye encabezado
include '../includes/header.php';
?>