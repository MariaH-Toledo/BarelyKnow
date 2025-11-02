<?php
header("Content-Type: application/json");
include "../db/conexao.php";

$codigo = $_GET['codigo'] ?? '';

if (empty($codigo)) {
    echo json_encode([]);
    exit;
}

$sql_sala = "SELECT id_sala FROM salas WHERE codigo_sala = ?";
$stmt_sala = $conn->prepare($sql_sala);
$stmt_sala->bind_param("s", $codigo);
$stmt_sala->execute();
$result_sala = $stmt_sala->get_result();

if ($result_sala->num_rows === 0) {
    echo json_encode([]);
    exit;
}

$sala = $result_sala->fetch_assoc();
$id_sala = $sala['id_sala'];

$sql = "SELECT j.id_jogador, j.nome, j.is_host 
        FROM jogadores j 
        WHERE j.id_sala = ? 
        ORDER BY j.is_host DESC, j.id_jogador ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_sala);
$stmt->execute();
$result = $stmt->get_result();

$jogadores = [];
while ($row = $result->fetch_assoc()) {
    $jogadores[] = $row;
}

echo json_encode($jogadores);
?>