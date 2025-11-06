<?php
session_start();
header("Content-Type: application/json");
include "../db/conexao.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["status" => "erro", "mensagem" => "Método não permitido"]);
    exit;
}

$codigo = $_POST['codigo'] ?? '';

if (empty($codigo)) {
    echo json_encode(["status" => "erro", "mensagem" => "Código não informado"]);
    exit;
}

$sql_update = "UPDATE salas SET status = 'encerrada' WHERE codigo_sala = ?";
$stmt_update = $conn->prepare($sql_update);
$stmt_update->bind_param("s", $codigo);

if ($stmt_update->execute()) {
    echo json_encode(["status" => "ok"]);
} else {
    echo json_encode(["status" => "erro", "mensagem" => "Erro ao finalizar jogo"]);
}
?>