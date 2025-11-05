<?php
header("Content-Type: application/json");
include "../db/conexao.php";

$codigo = $_GET['codigo'] ?? '';
$rodada = $_GET['rodada'] ?? 1;

if (empty($codigo)) {
    echo json_encode(["status" => "erro", "mensagem" => "Código não informado"]);
    exit;
}

// Buscar sala e categoria
$sql_sala = "SELECT s.id_categoria, s.rodadas, c.nome_categoria 
             FROM salas s 
             JOIN categorias c ON s.id_categoria = c.id_categoria 
             WHERE s.codigo_sala = ?";
$stmt_sala = $conn->prepare($sql_sala);
$stmt_sala->bind_param("s", $codigo);
$stmt_sala->execute();
$result_sala = $stmt_sala->get_result();

if ($result_sala->num_rows === 0) {
    echo json_encode(["status" => "erro", "mensagem" => "Sala não encontrada"]);
    exit;
}

$sala = $result_sala->fetch_assoc();
$id_categoria = $sala['id_categoria'];
$total_rodadas = $sala['rodadas'];

// Verificar se acabaram as rodadas
if ($rodada > $total_rodadas) {
    echo json_encode(["status" => "fim"]);
    exit;
}

// 🚨 CORREÇÃO: Buscar pergunta aleatória DIRETO do banco
$sql_pergunta = "SELECT * FROM perguntas WHERE id_categoria = ? ORDER BY RAND() LIMIT 1";
$stmt_pergunta = $conn->prepare($sql_pergunta);
$stmt_pergunta->bind_param("i", $id_categoria);
$stmt_pergunta->execute();
$result_pergunta = $stmt_pergunta->get_result();

if ($result_pergunta->num_rows === 0) {
    echo json_encode(["status" => "erro", "mensagem" => "Nenhuma pergunta encontrada para esta categoria"]);
    exit;
}

$pergunta = $result_pergunta->fetch_assoc();

echo json_encode([
    "status" => "ok",
    "pergunta" => $pergunta,
    "rodada_atual" => $rodada,
    "total_rodadas" => $total_rodadas
]);
?>