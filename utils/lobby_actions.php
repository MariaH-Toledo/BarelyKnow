<?php
session_start();
header("Content-Type: application/json");
include "../db/conexao.php";

$acao = $_POST['acao'] ?? '';
$codigo_sala = $_POST['codigo_sala'] ?? '';

if (empty($codigo_sala)) {
    echo json_encode(["status" => "erro", "mensagem" => "Código não informado"]);
    exit;
}

$sql_sala = "SELECT id_sala FROM salas WHERE codigo_sala = ?";
$stmt_sala = $conn->prepare($sql_sala);
$stmt_sala->bind_param("s", $codigo_sala);
$stmt_sala->execute();
$result_sala = $stmt_sala->get_result();

if ($result_sala->num_rows === 0) {
    echo json_encode(["status" => "erro", "mensagem" => "Sala não encontrada"]);
    exit;
}

$sala = $result_sala->fetch_assoc();
$id_sala = $sala['id_sala'];

$id_jogador = $_SESSION['id_jogador'] ?? 0;
$sql_jogador = "SELECT is_host FROM jogadores WHERE id_jogador = ? AND id_sala = ?";
$stmt_jogador = $conn->prepare($sql_jogador);
$stmt_jogador->bind_param("ii", $id_jogador, $id_sala);
$stmt_jogador->execute();
$result_jogador = $stmt_jogador->get_result();

if ($result_jogador->num_rows === 0) {
    echo json_encode(["status" => "erro", "mensagem" => "Jogador não encontrado na sala"]);
    exit;
}

$jogador = $result_jogador->fetch_assoc();
$eh_host = ($jogador['is_host'] == 1);

switch ($acao) {
    case 'listar_jogadores':
        $sql = "SELECT id_jogador, nome, is_host FROM jogadores 
                WHERE id_sala = ? 
                ORDER BY is_host DESC, id_jogador ASC";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id_sala);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $jogadores = [];
        while ($row = $result->fetch_assoc()) {
            $jogadores[] = $row;
        }
        
        echo json_encode(["status" => "ok", "jogadores" => $jogadores]);
        break;

    case 'iniciar_jogo':
        if (!$eh_host) {
            echo json_encode(["status" => "erro", "mensagem" => "Apenas o host pode iniciar o jogo"]);
            exit;
        } else {
            $sql_count = "SELECT COUNT(*) as total FROM jogadores WHERE id_sala = ?";
            $stmt_count = $conn->prepare($sql_count);
            $stmt_count->bind_param("i", $id_sala);
            $stmt_count->execute();
            $result_count = $stmt_count->get_result();
            $total = $result_count->fetch_assoc()['total'];
            
            if ($total < 2) {
                echo json_encode(["status" => "erro", "mensagem" => "Mínimo 2 jogadores para iniciar"]);
                exit;
            } else {
                $sql_update = "UPDATE salas SET status = 'iniciada' WHERE id_sala = ?";
                $stmt_update = $conn->prepare($sql_update);
                $stmt_update->bind_param("i", $id_sala);
                
                if ($stmt_update->execute()) {
                    echo json_encode(["status" => "ok"]);
                } else {
                    echo json_encode(["status" => "erro", "mensagem" => "Erro ao iniciar jogo"]);
                }
            }
        }
        break;

    case 'sair_sala':
        if ($eh_host) {
            echo json_encode(["status" => "erro", "mensagem" => "Host deve fechar a sala em vez de sair"]);
            exit;
        }

        $sql_delete = "DELETE FROM jogadores WHERE id_jogador = ?";
        $stmt_delete = $conn->prepare($sql_delete);
        $stmt_delete->bind_param("i", $id_jogador);
        
        if ($stmt_delete->execute()) {
            session_destroy();
            echo json_encode(["status" => "ok"]);
        } else {
            echo json_encode(["status" => "erro", "mensagem" => "Erro ao sair da sala"]);
        }
        break;

    case 'fechar_sala':
        if (!$eh_host) {
            echo json_encode(["status" => "erro", "mensagem" => "Apenas o host pode fechar a sala"]);
            exit;
        }

        $sql_delete = "DELETE FROM salas WHERE id_sala = ?";
        $stmt_delete = $conn->prepare($sql_delete);
        $stmt_delete->bind_param("i", $id_sala);
        
        if ($stmt_delete->execute()) {
            session_destroy();
            echo json_encode(["status" => "ok"]);
        } else {
            echo json_encode(["status" => "erro", "mensagem" => "Erro ao fechar sala"]);
        }
        break;

    case 'verificar_status':
        $sql_status = "SELECT status FROM salas WHERE id_sala = ?";
        $stmt_status = $conn->prepare($sql_status);
        $stmt_status->bind_param("i", $id_sala);
        $stmt_status->execute();
        $result_status = $stmt_status->get_result();
        
        if ($result_status->num_rows === 0) {
            echo json_encode(["status" => "erro", "mensagem" => "Sala não encontrada"]);
            exit;
        }
        
        $status = $result_status->fetch_assoc();
        echo json_encode(["status" => $status['status']]);
        break;

    default:
        echo json_encode(["status" => "erro", "mensagem" => "Ação desconhecida"]);
}
?>