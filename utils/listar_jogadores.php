<?php
include "../db/conexao.php";

$codigo = $_GET['codigo'] ?? '';

if (!$codigo) {
    echo json_encode([]);
    exit;
}

$sql = "SELECT j.nome, j.is_host 
        FROM jogadores j
        JOIN salas s ON j.id_sala = s.id_sala
        WHERE s.codigo_sala = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $codigo);
$stmt->execute();

$result = $stmt->get_result();
$jogadores = [];

while ($row = $result->fetch_assoc()) {
    $jogadores[] = $row;
}

echo json_encode($jogadores);
?>
