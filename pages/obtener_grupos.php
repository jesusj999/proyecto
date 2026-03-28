<?php
require_once '../includes/config.php';
header('Content-Type: application/json');

$idGrado = (int)$_GET['idGrado'];
$r = $conn->query("SELECT idGrupo, nombre FROM grupo WHERE idgrado=$idGrado");

$grupos = [];
while ($g = $r->fetch_assoc()) {
    $grupos[] = $g;
}

echo json_encode($grupos);