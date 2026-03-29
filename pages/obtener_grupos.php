<?php
// Incluye la conexión a la base de datos (config.php)
require_once '../includes/config.php';

// Indica que la respuesta será en formato JSON (para usar con fetch en JS)
header('Content-Type: application/json');


// =============================
// 📥 RECIBIR DATO DESDE LA URL
// =============================

// Se obtiene el id del grado desde la URL (GET)
// Ejemplo: get_grupos.php?idGrado=3
$idGrado = (int)$_GET['idGrado'];


// =============================
// 🔎 CONSULTA A LA BASE DE DATOS
// =============================

// Consulta para traer los grupos que pertenecen a ese grado
$r = $conn->query("SELECT idGrupo, nombre FROM grupo WHERE idgrado=$idGrado");


// =============================
// 📦 GUARDAR RESULTADOS EN ARRAY
// =============================

// Se crea un arreglo vacío
$grupos = [];

// Se recorre cada fila que devuelve la consulta
while ($g = $r->fetch_assoc()) {
    // Se agrega cada grupo al arreglo
    $grupos[] = $g;
}


// =============================
// 📤 ENVIAR RESULTADO EN JSON
// =============================

// Convierte el arreglo en JSON y lo envía
echo json_encode($grupos);