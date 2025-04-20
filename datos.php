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
    // Consulta último dato
    $res = $conn->query("SELECT * FROM sensores ORDER BY id DESC LIMIT 1");
    $data = $res->fetch_assoc();

    // Si no hay datos, activar modo demo
    $modo_demo = false;
    if (!$data || empty($data['temperatura'])) {
        $modo_demo = true;
        $data = [
            'temperatura' => rand(180, 300) / 10,  // 18.0 - 30.0
            'humedad' => rand(40, 95),
            'suelo' => rand(30, 80),
            'luz' => rand(100, 1000),
            'viento_direccion' => ['Norte','Sur','Este','Oeste','Noreste','Noroeste','Sureste','Suroeste'][rand(0,7)],
            'viento_velocidad' => rand(0, 150) / 10,  // 0.0 - 15.0
            'lluvia_mm' => rand(0, 100) / 10,  // 0.0 - 10.0
            'estado_lluvia' => ['Ligera','Moderada','Aguacero'][rand(0,2)]
        ];
    }

    // Lluvia total del día (solo si no es modo demo)
    if (!$modo_demo) {
        $hoy = date('Y-m-d');
        $total = $conn->query("SELECT SUM(lluvia_mm) as total_lluvia FROM sensores WHERE fecha = '$hoy'");
        $lluvia = $total->fetch_assoc();
        $data['lluvia_mm_total'] = $lluvia['total_lluvia'] ?? 0;
    } else {
        $data['lluvia_mm_total'] = rand(0, 500) / 10;
    }

    // Indicador de modo demo
    $data['demo'] = $modo_demo;

    echo json_encode($data);
}

$conn->close();
?>
