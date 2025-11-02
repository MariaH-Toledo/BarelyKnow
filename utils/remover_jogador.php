<?php
session_start();
header("Content-Type: application/json");
include "../db/conexao.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $codigo_sala = $_POST['codigo_sala'] ?? '';
    $id_jogador_remover = $_POST['id_jogador'] ?? '';
    
    if (empty($codigo_sala) || empty($id_jogador_remover)) {
        echo json_encode(["status" => "erro", "mensagem" => "Dados incompletos."]);
        exit;
    }

    $sql_verificar_host = "SELECT j.id_jogador 
                          FROM jogadores j 
                          JOIN salas s ON j.id_sala = s.id_sala 
                          WHERE j.id_jogador = ? AND s.codigo_sala = ? AND j.is_host = 1";
    $stmt_host = $conn->prepare($sql_verificar_host);
    $stmt_host->bind_param("is", $_SESSION['id_jogador'], $codigo_sala);
    $stmt_host->execute();
    $result_host = $stmt_host->get_result();
    
    if ($result_host->num_rows === 0) {
        echo json_encode(["status" => "erro", "mensagem" => "Apenas o host pode remover jogadores."]);
        exit;
    }

    // Verificar se não está tentando remover a si mesmo
    if ($id_jogador_remover == $_SESSION['id_jogador']) {
        echo json_encode(["status" => "erro", "mensagem" => "Use 'Fechar Sala' para encerrar a sala."]);
        exit;
    }

    // Remover o jogador
    $sql_remover = "DELETE FROM jogadores WHERE id_jogador = ?";
    $stmt_remover = $conn->prepare($sql_remover);
    $stmt_remover->bind_param("i", $id_jogador_remover);
    
    if ($stmt_remover->execute()) {
        echo json_encode(["status" => "ok", "mensagem" => "Jogador removido com sucesso."]);
    } else {
        echo json_encode(["status" => "erro", "mensagem" => "Erro ao remover jogador: " . $conn->error]);
    }
}
?>