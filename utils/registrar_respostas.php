<?php
session_start();
header("Content-Type: application/json");
include "../db/conexao.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["status" => "erro", "mensagem" => "Método não permitido"]);
    exit;
}

$id_jogador = $_POST['id_jogador'] ?? 0;
$id_pergunta = $_POST['id_pergunta'] ?? 0;
$resposta_escolhida = $_POST['resposta_escolhida'] ?? 0;
$correta = $_POST['correta'] ?? 0;
$tempo_resposta = $_POST['tempo_resposta'] ?? 0;

if (!$id_jogador || !$id_pergunta) {
    echo json_encode(["status" => "erro", "mensagem" => "Dados incompletos"]);
    exit;
}

$pontos = 0;
if ($correta) {
    $pontos = max(100, 1000 - ($tempo_resposta * 10));
}

$sql_resposta = "INSERT INTO respostas (id_jogador, id_pergunta, resposta_escolhida, correta, tempo_resposta) 
                 VALUES (?, ?, ?, ?, ?)";
$stmt_resposta = $conn->prepare($sql_resposta);
$stmt_resposta->bind_param("iiisi", $id_jogador, $id_pergunta, $resposta_escolhida, $correta, $tempo_resposta);

if ($stmt_resposta->execute()) {
    if ($pontos > 0) {
        $sql_pontos = "UPDATE jogadores SET pontos = pontos + ? WHERE id_jogador = ?";
        $stmt_pontos = $conn->prepare($sql_pontos);
        $stmt_pontos->bind_param("ii", $pontos, $id_jogador);
        $stmt_pontos->execute();
    }
    
    echo json_encode(["status" => "ok", "pontos" => $pontos]);
} else {
    echo json_encode(["status" => "erro", "mensagem" => "Erro ao registrar resposta"]);
}
?>