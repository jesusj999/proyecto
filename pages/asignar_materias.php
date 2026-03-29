<?php
// Título de la página
$pageTitle = 'Asignar Materias a Docentes';

// Conecta con la base de datos
require_once '../includes/config.php';

// Verifica que solo el administrador (nivel 1) pueda entrar
requireAuth([1]);

// Variables para mensajes
$msg = '';
$error = '';

// Obtiene el año actual
$anio_actual = date('Y');


// ==========================
// GUARDAR ASIGNACIÓN
// ==========================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'asignar') {

    // Captura los datos del formulario
    $docente = (int)$_POST['idDocente'];
    $sede = (int)$_POST['idSede'];
    $grupo = (int)$_POST['idGrupo'];
    $grado = (int)$_POST['idGrado'];
    $anio = (int)$_POST['anio'];

    // Obtiene las materias seleccionadas (pueden ser varias)
    $materias_sel = $_POST['materias'] ?? [];

    // Contadores
    $insertados = 0;
    $duplicados = 0;

    // Recorre cada materia seleccionada
    foreach ($materias_sel as $materia) {

        $materia = (int)$materia;

        // Verifica si esa materia ya está asignada a OTRO docente
        $check = $conn->query("SELECT idDocente FROM horario 
        WHERE idMateria=$materia AND idgrupo=$grupo AND año=$anio AND idDocente != $docente");

        // Si existe, se cuenta como duplicado
        if ($check->num_rows > 0) {
            $duplicados++;
            continue;
        }

        // Verifica si YA existe la misma asignación
        $check2 = $conn->query("SELECT idDocente FROM horario 
        WHERE idDocente=$docente AND idMateria=$materia AND idgrupo=$grupo AND año=$anio");

        if ($check2->num_rows > 0) {
            $duplicados++;
            continue;
        }

        // Inserta la asignación en la base de datos
        if ($conn->query("INSERT INTO horario (idDocente, idMateria, idGrupo, año, idgrado, idsede)
        VALUES ($docente, $materia, $grupo, $anio, $grado, $sede)")) {

            $insertados++;
        }
    }

    // Mensajes de resultado
    if ($insertados > 0) $msg = "$insertados materia(s) asignada(s) correctamente.";
    if ($duplicados > 0) $msg .= ($msg ? ' ' : '') . "$duplicados ya existían o tienen conflicto.";
}


// ==========================
// ELIMINAR ASIGNACIÓN
// ==========================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'eliminar') {

    // Captura datos
    $docente = (int)$_POST['idDocente'];
    $materia = (int)$_POST['idMateria'];
    $grupo = (int)$_POST['idGrupo'];
    $anio = (int)$_POST['anio'];

    // Elimina el registro de la base de datos
    if ($conn->query("DELETE FROM horario 
    WHERE idDocente=$docente AND idMateria=$materia AND idgrupo=$grupo AND año=$anio")) {

        $msg = "Asignación eliminada.";
    } else {
        $error = "Error al eliminar.";
    }
}


// ==========================
// CARGAR HORARIO DEL DOCENTE
// ==========================

// Obtiene el docente seleccionado (POST o GET)
$docente_sel = (int)($_POST['docente_ver'] ?? $_GET['docente'] ?? 0);

// Variable donde se guardará el horario
$horario_docente = null;

// Si hay docente seleccionado
if ($docente_sel) {

    // Consulta el horario del docente
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


// ==========================
// DATOS PARA LOS SELECT
// ==========================

// Lista de docentes
$docentes = $conn->query("SELECT d.idDocentes, CONCAT(p.primerNombre, ' ', p.primerApellido) as nombre
FROM docentes d 
JOIN personas p ON d.idDocentes = p.identificacion
ORDER BY p.primerApellido");

// Lista de sedes
$sedes = $conn->query("SELECT * FROM sedes ORDER BY nombre");

// Lista de materias
$materias = $conn->query("SELECT * FROM materias ORDER BY nombre");


// Incluye el header (encabezado)
include '../includes/header.php';
?>