<?php
header("Content-Type: application/json");
include "../db/conexao.php";

$codigo = $_GET['codigo'] ?? '';

if (empty($codigo)) {
    echo json_encode(["status" => "erro", "mensagem" => "Código não informado"]);
    exit;
}

$sql = "SELECT status FROM salas WHERE codigo_sala = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $codigo);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(["status" => "erro", "mensagem" => "Sala não encontrada"]);
    exit;
}

$sala = $result->fetch_assoc();
echo json_encode(["status" => $sala['status']]);
?>