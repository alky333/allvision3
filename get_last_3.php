<?php
require_once 'db.php';

$sql = "SELECT id, temperatura, humedad, suelo, luz, viento_velocidad, viento_direccion, lluvia_mm, estado_lluvia, hora FROM sensores ORDER BY id DESC LIMIT 3";
$result = $conn->query($sql);

$datos = [];
while ($row = $result->fetch_assoc()) {
    $datos[] = $row;
}

echo json_encode($datos);
$conn->close();
?>
