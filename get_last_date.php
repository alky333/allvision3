<?php
require_once 'db.php';
header('Content-Type: application/json');
if (isset($_GET['listAll'])) {
    $sql = "SELECT DISTINCT fecha FROM sensores ORDER BY fecha DESC";
    $res = $conn->query($sql);
    $fechas = [];
    while ($row = $res->fetch_assoc()) $fechas[] = $row['fecha'];
    echo json_encode($fechas);
} else {
    $sql = "SELECT fecha FROM sensores ORDER BY fecha DESC LIMIT 1";
    $res = $conn->query($sql);
    echo json_encode($res->fetch_assoc());
}
$conn->close();
?>