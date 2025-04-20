<?php
require_once 'db.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $fecha = date('Y-m-d');
    $hora = date('H:i:s');

    $stmt = $conn->prepare("INSERT INTO sensores (fecha, hora, temperatura, humedad, suelo, luz, viento_direccion, viento_velocidad, lluvia, lluvia_mm, estado_lluvia) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssddddsdsss",
        $fecha, $hora,
        $input['temperatura'], $input['humedad'], $input['suelo'], $input['luz'],
        $input['viento_direccion'], $input['viento_velocidad'],
        $input['lluvia'], $input['lluvia_mm'], $input['estado_lluvia']
    );

    if ($stmt->execute()) {
        echo json_encode(["estado" => "ok", "mensaje" => "Datos guardados"]);
    } else {
        http_response_code(500);
        echo json_encode(["estado" => "error", "mensaje" => "Error al guardar"]);
    }
    $stmt->close();
} else {
    $res = $conn->query("SELECT * FROM sensores ORDER BY id DESC LIMIT 1");
    echo json_encode($res->fetch_assoc());
}
$conn->close();
?>