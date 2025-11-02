<?php
session_start();
header("Content-Type: application/json");
include "../db/conexao.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $codigo = $_POST['codigo'] ?? '';
    
    if (empty($codigo)) {
        echo json_encode(["status" => "erro", "mensagem" => "Código da sala não informado."]);
        exit;
    }

    $sql_sala = "SELECT s.*, c.nome_categoria FROM salas s 
                 JOIN categorias c ON s.id_categoria = c.id_categoria 
                 WHERE s.codigo_sala = ?";
    $stmt = $conn->prepare($sql_sala);
    $stmt->bind_param("s", $codigo);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(["status" => "erro", "mensagem" => "Sala não encontrada."]);
        exit;
    }
    
    $sala = $result->fetch_assoc();
    
    $sql_host = "SELECT id_jogador FROM jogadores WHERE id_sala = ? AND is_host = 1";
    $stmt_host = $conn->prepare($sql_host);
    $stmt_host->bind_param("i", $sala['id_sala']);
    $stmt_host->execute();
    $result_host = $stmt_host->get_result();
    $host = $result_host->fetch_assoc();
    
    if (!isset($_SESSION['id_jogador']) || $_SESSION['id_jogador'] != $host['id_jogador']) {
        echo json_encode(["status" => "erro", "mensagem" => "Apenas o host pode iniciar o jogo."]);
        exit;
    }
    
    $sql_count = "SELECT COUNT(*) as total FROM jogadores WHERE id_sala = ?";
    $stmt_count = $conn->prepare($sql_count);
    $stmt_count->bind_param("i", $sala['id_sala']);
    $stmt_count->execute();
    $result_count = $stmt_count->get_result();
    $total_jogadores = $result_count->fetch_assoc()['total'];
    
    if ($total_jogadores < 2) {
        echo json_encode(["status" => "erro", "mensagem" => "Mínimo 2 jogadores para iniciar."]);
        exit;
    }
    
    $sql_update = "UPDATE salas SET status = 'iniciada' WHERE id_sala = ?";
    $stmt_update = $conn->prepare($sql_update);
    $stmt_update->bind_param("i", $sala['id_sala']);
    
    if ($stmt_update->execute()) {
        echo json_encode(["status" => "ok", "mensagem" => "Jogo iniciado com sucesso!"]);
    } else {
        echo json_encode(["status" => "erro", "mensagem" => "Erro ao iniciar jogo: " . $conn->error]);
    }
}
?>