<?php
require_once "db.php";

$sql = "SELECT * FROM clima ORDER BY timestamp DESC LIMIT 1";
$resultado = $conn->query($sql);

if ($resultado && $resultado->num_rows > 0) {
    $fila = $resultado->fetch_assoc();
    echo json_encode([
        "temperatura" => $fila["temperatura"],
        "humedad" => $fila["humedad"],
        "suelo" => $fila["suelo"],
        "luz" => $fila["luz"],
        "viento_direccion" => $fila["viento_direccion"],
        "viento_velocidad" => $fila["viento_velocidad"],
        "lluvia" => $fila["lluvia"],
        "lluvia_mm" => $fila["lluvia_mm"],
        "estado_lluvia" => $fila["estado_lluvia"]
    ]);
} else {
    echo json_encode(["error" => "No data found"]);
}

$conn->close();
?>
