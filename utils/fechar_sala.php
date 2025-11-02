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

    // Buscar a sala
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
    
    // Iniciar transação para garantir que tudo seja excluído
    $conn->begin_transaction();
    
    try {
        // 1. Primeiro, buscar os IDs dos jogadores para excluir suas respostas
        $sql_jogadores_sala = "SELECT id_jogador FROM jogadores WHERE id_sala = ?";
        $stmt_jogadores = $conn->prepare($sql_jogadores_sala);
        $stmt_jogadores->bind_param("i", $id_sala);
        $stmt_jogadores->execute();
        $result_jogadores = $stmt_jogadores->get_result();
        
        $ids_jogadores = [];
        while ($jogador = $result_jogadores->fetch_assoc()) {
            $ids_jogadores[] = $jogador['id_jogador'];
        }
        
        // 2. Se houver jogadores, excluir suas respostas
        if (!empty($ids_jogadores)) {
            $placeholders = str_repeat('?,', count($ids_jogadores) - 1) . '?';
            $sql_delete_respostas = "DELETE FROM respostas WHERE id_jogador IN ($placeholders)";
            $stmt_respostas = $conn->prepare($sql_delete_respostas);
            $stmt_respostas->bind_param(str_repeat('i', count($ids_jogadores)), ...$ids_jogadores);
            $stmt_respostas->execute();
        }
        
        // 3. Excluir a sala (os jogadores serão excluídos automaticamente por CASCADE)
        $sql_delete_sala = "DELETE FROM salas WHERE id_sala = ?";
        $stmt_sala = $conn->prepare($sql_delete_sala);
        $stmt_sala->bind_param("i", $id_sala);
        $stmt_sala->execute();
        
        // Confirmar a transação
        $conn->commit();
        
        // Limpar a sessão
        session_destroy();
        
        echo json_encode(["status" => "ok", "mensagem" => "Sala e todos os dados relacionados foram excluídos com sucesso."]);
        
    } catch (Exception $e) {
        // Em caso de erro, desfazer tudo
        $conn->rollback();
        echo json_encode(["status" => "erro", "mensagem" => "Erro ao fechar sala: " . $e->getMessage()]);
    }
}
?>