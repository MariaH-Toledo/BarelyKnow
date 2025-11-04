<?php
session_start();
header("Content-Type: application/json");
include "../db/conexao.php";

$acao = $_POST['acao'] ?? '';

$codigo = $_POST['codigo'] ?? '';
if (empty($codigo)) {
    echo json_encode(["status" => "erro", "mensagem" => "Código da sala não informado"]);
    exit;
}

$sql_sala = "SELECT id_sala FROM salas WHERE codigo_sala = ?";
$stmt_sala = $conn->prepare($sql_sala);
$stmt_sala->bind_param("s", $codigo);
$stmt_sala->execute();
$result_sala = $stmt_sala->get_result();

if ($result_sala->num_rows === 0) {
    echo json_encode(["status" => "erro", "mensagem" => "Sala não encontrada"]);
    exit;
}

$sala = $result_sala->fetch_assoc();
$id_sala = $sala['id_sala'];

switch ($acao) {
    
    case 'listar_jogadores':
        $sql = "SELECT id_jogador, nome, is_host FROM jogadores WHERE id_sala = ? ORDER BY is_host DESC, id_jogador ASC";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id_sala);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $jogadores = [];
        while ($row = $result->fetch_assoc()) {
            $jogadores[] = $row;
        }
        
        // DEBUG temporário
        error_log("Jogadores na sala $id_sala: " . json_encode($jogadores));
        
        echo json_encode($jogadores);
        break;

    case 'verificar_status':
        $sql = "SELECT status FROM salas WHERE id_sala = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id_sala);
        $stmt->execute();
        $result = $stmt->get_result();
        $status = $result->fetch_assoc()['status'];
        echo json_encode(["status" => $status]);
        break;

    case 'iniciar_jogo':
        $sql_host = "SELECT id_jogador FROM jogadores WHERE id_sala = ? AND is_host = 1";
        $stmt_host = $conn->prepare($sql_host);
        $stmt_host->bind_param("i", $id_sala);
        $stmt_host->execute();
        $host = $stmt_host->get_result()->fetch_assoc();
        
        if ($_SESSION['id_jogador'] != $host['id_jogador']) {
            echo json_encode(["status" => "erro", "mensagem" => "Apenas o host pode iniciar"]);
            exit;
        }

        $sql_count = "SELECT COUNT(*) as total FROM jogadores WHERE id_sala = ?";
        $stmt_count = $conn->prepare($sql_count);
        $stmt_count->bind_param("i", $id_sala);
        $stmt_count->execute();
        $total = $stmt_count->get_result()->fetch_assoc()['total'];
        
        if ($total < 2) {
            echo json_encode(["status" => "erro", "mensagem" => "Precisa de pelo menos 2 jogadores"]);
            exit;
        }

        $sql_update = "UPDATE salas SET status = 'iniciada' WHERE id_sala = ?";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param("i", $id_sala);
        
        if ($stmt_update->execute()) {
            echo json_encode(["status" => "ok"]);
        } else {
            echo json_encode(["status" => "erro", "mensagem" => "Erro ao iniciar jogo"]);
        }
        break;

    case 'sair_sala':
        $sql = "DELETE FROM jogadores WHERE id_jogador = ? AND id_sala = ? AND is_host = 0";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $_SESSION['id_jogador'], $id_sala);
        
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            session_destroy();
            echo json_encode(["status" => "ok"]);
        } else {
            echo json_encode(["status" => "erro", "mensagem" => "Host deve fechar a sala"]);
        }
        break;

    case 'fechar_sala':
        $sql_host = "SELECT id_jogador FROM jogadores WHERE id_sala = ? AND is_host = 1";
        $stmt_host = $conn->prepare($sql_host);
        $stmt_host->bind_param("i", $id_sala);
        $stmt_host->execute();
        $host = $stmt_host->get_result()->fetch_assoc();
        
        if ($_SESSION['id_jogador'] != $host['id_jogador']) {
            echo json_encode(["status" => "erro", "mensagem" => "Apenas o host pode fechar"]);
            exit;
        }

        $sql_delete = "DELETE FROM salas WHERE id_sala = ?";
        $stmt_delete = $conn->prepare($sql_delete);
        $stmt_delete->bind_param("i", $id_sala);

        if ($stmt_delete->execute()) {
            echo json_encode(["status" => "ok"]);
        } else {
            echo json_encode(["status" => "erro", "mensagem" => "Erro ao fechar sala"]);
        }
        break;
    case 'remover_jogador':
        $sql_host = "SELECT id_jogador FROM jogadores WHERE id_sala = ? AND is_host = 1";
        $stmt_host = $conn->prepare($sql_host);
        $stmt_host->bind_param("i", $id_sala);
        $stmt_host->execute();
        $host = $stmt_host->get_result()->fetch_assoc();
        
        if ($_SESSION['id_jogador'] != $host['id_jogador']) {
            echo json_encode(["status" => "erro", "mensagem" => "Apenas o host pode remover"]);
            exit;
        }

        $id_remover = $_POST['id_jogador'] ?? 0;
        if ($id_remover == $_SESSION['id_jogador']) {
            echo json_encode(["status" => "erro", "mensagem" => "Não pode se remover"]);
            exit;
        }

        $sql_remove = "DELETE FROM jogadores WHERE id_jogador = ? AND id_sala = ?";
        $stmt_remove = $conn->prepare($sql_remove);
        $stmt_remove->bind_param("ii", $id_remover, $id_sala);
        
        if ($stmt_remove->execute()) {
            echo json_encode(["status" => "ok"]);
        } else {
            echo json_encode(["status" => "erro", "mensagem" => "Erro ao remover"]);
        }
        break;

    default:
        echo json_encode(["status" => "erro", "mensagem" => "Ação desconhecida"]);
}
?>