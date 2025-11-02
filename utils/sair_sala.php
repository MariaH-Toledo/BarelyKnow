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

    $sql_sala = "SELECT id_sala FROM salas WHERE codigo_sala = ?";
    $stmt = $conn->prepare($sql_sala);
    $stmt->bind_param("s", $codigo);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(["status" => "erro", "mensagem" => "Sala não encontrada."]);
        exit;
    }
    
    $sala = $result->fetch_assoc();
    $id_sala = $sala['id_sala'];
    
    if (!isset($_SESSION['id_jogador'])) {
        echo json_encode(["status" => "erro", "mensagem" => "Jogador não identificado."]);
        exit;
    }
    
    $id_jogador = $_SESSION['id_jogador'];
    
    $conn->begin_transaction();
    
    try {
        $sql_delete_respostas = "DELETE FROM respostas WHERE id_jogador = ?";
        $stmt_respostas = $conn->prepare($sql_delete_respostas);
        $stmt_respostas->bind_param("i", $id_jogador);
        $stmt_respostas->execute();
        
        $sql_remover_jogador = "DELETE FROM jogadores WHERE id_jogador = ? AND id_sala = ? AND is_host = 0";
        $stmt_jogador = $conn->prepare($sql_remover_jogador);
        $stmt_jogador->bind_param("ii", $id_jogador, $id_sala);
        $stmt_jogador->execute();
        
        if ($stmt_jogador->affected_rows > 0) {
            $sql_contar_jogadores = "SELECT COUNT(*) as total FROM jogadores WHERE id_sala = ?";
            $stmt_contar = $conn->prepare($sql_contar_jogadores);
            $stmt_contar->bind_param("i", $id_sala);
            $stmt_contar->execute();
            $result_contar = $stmt_contar->get_result();
            $total_jogadores = $result_contar->fetch_assoc()['total'];
            
            if ($total_jogadores == 0) {
                $sql_excluir_sala = "DELETE FROM salas WHERE id_sala = ?";
                $stmt_excluir_sala = $conn->prepare($sql_excluir_sala);
                $stmt_excluir_sala->bind_param("i", $id_sala);
                $stmt_excluir_sala->execute();
            }
            
            $conn->commit();
            
            session_destroy();
            
            echo json_encode(["status" => "ok", "mensagem" => "Você saiu da sala com sucesso."]);
        } else {
            $conn->rollback();
            echo json_encode(["status" => "erro", "mensagem" => "Você é o host desta sala. Use 'Fechar Sala' para encerrá-la."]);
        }
        
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(["status" => "erro", "mensagem" => "Erro ao sair da sala: " . $e->getMessage()]);
    }
}
?>