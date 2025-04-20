<?php
require_once 'db.php';

// Configurar el manejo de errores
ini_set('display_errors', 0);
header('Content-Type: application/json');

try {
    $fecha = $_GET['fecha'] ?? date('Y-m-d');
    
    // Validar formato de fecha
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
        throw new Exception('Formato de fecha inválido');
    }

    // Consulta optimizada para intervalos de 20 minutos
    $sql = "SELECT 
        DATE_FORMAT(MIN(CONCAT(fecha, ' ', hora)), '%H:%i') as hora,
        ROUND(AVG(temperatura), 1) as temperatura,
        ROUND(AVG(humedad), 1) as humedad,
        ROUND(AVG(suelo), 1) as suelo,
        ROUND(AVG(luz), 1) as luz,
        ROUND(AVG(viento_velocidad), 1) as viento_velocidad,
        (SELECT viento_direccion FROM sensores 
         WHERE fecha = s.fecha 
         ORDER BY CONCAT(fecha, ' ', hora) DESC LIMIT 1) as viento_direccion,
        ROUND(SUM(lluvia), 2) as lluvia,
        (SELECT estado_lluvia FROM sensores 
         WHERE fecha = s.fecha 
         ORDER BY CONCAT(fecha, ' ', hora) DESC LIMIT 1) as estado_lluvia
    FROM sensores s
    WHERE fecha = ?
    GROUP BY 
        HOUR(CONCAT(fecha, ' ', hora)),
        FLOOR(MINUTE(CONCAT(fecha, ' ', hora))/20)
    ORDER BY hora ASC";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception('Error al preparar la consulta: ' . $conn->error);
    }

    $stmt->bind_param("s", $fecha);
    if (!$stmt->execute()) {
        throw new Exception('Error al ejecutar la consulta: ' . $stmt->error);
    }

    $res = $stmt->get_result();
    $data = [];
    
    while ($row = $res->fetch_assoc()) {
        // Asegurar valores nulos se muestren como null
        foreach ($row as $key => $value) {
            if ($value === null || $value === '') {
                $row[$key] = null;
            }
        }
        $data[] = $row;
    }

    // Si no hay datos, devolver array vacío
    if (empty($data)) {
        echo json_encode([]);
    } else {
        echo json_encode($data);
    }

} catch (Exception $e) {
    // Devolver error en formato JSON
    echo json_encode([
        'error' => true,
        'message' => $e->getMessage()
    ]);
} finally {
    // Cerrar conexiones
    if (isset($stmt)) $stmt->close();
    if (isset($conn)) $conn->close();
}
?>