<?php
session_start();
header("Content-Type: application/json");
include "../db/conexao.php";

$acao = $_POST['acao'] ?? '';
$codigo = $_POST['codigo'] ?? '';

if (empty($codigo)) {
    echo json_encode(["status" => "erro", "mensagem" => "Código não informado"]);
    exit;
}

// Buscar sala
$sql_sala = "SELECT id_sala, status FROM salas WHERE codigo_sala = ?";
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

// Verificar se é host
$id_jogador = $_SESSION['id_jogador'] ?? 0;
$sql_host = "SELECT is_host FROM jogadores WHERE id_jogador = ? AND id_sala = ?";
$stmt_host = $conn->prepare($sql_host);
$stmt_host->bind_param("ii", $id_jogador, $id_sala);
$stmt_host->execute();
$host_data = $stmt_host->get_result()->fetch_assoc();
$eh_host = ($host_data && $host_data['is_host'] == 1);

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
            echo json_encode(["status" => "erro", "mensagem" => "Apenas o host pode iniciar"]);
            exit;
        }

        // Verificar mínimo de jogadores
        $sql_count = "SELECT COUNT(*) as total FROM jogadores WHERE id_sala = ?";
        $stmt_count = $conn->prepare($sql_count);
        $stmt_count->bind_param("i", $id_sala);
        $stmt_count->execute();
        $total = $stmt_count->get_result()->fetch_assoc()['total'];
        
        if ($total < 2) {
            echo json_encode(["status" => "erro", "mensagem" => "Mínimo 2 jogadores"]);
            exit;
        }

        // Iniciar jogo
        $sql_update = "UPDATE salas SET status = 'iniciada' WHERE id_sala = ?";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param("i", $id_sala);
        
        if ($stmt_update->execute()) {
            echo json_encode(["status" => "ok"]);
        } else {
            echo json_encode(["status" => "erro", "mensagem" => "Erro ao iniciar"]);
        }
        break;

    case 'sair_sala':
        if ($eh_host) {
            echo json_encode(["status" => "erro", "mensagem" => "Host deve fechar a sala"]);
            exit;
        }

        $sql_delete = "DELETE FROM jogadores WHERE id_jogador = ?";
        $stmt_delete = $conn->prepare($sql_delete);
        $stmt_delete->bind_param("i", $id_jogador);
        
        if ($stmt_delete->execute()) {
            session_destroy();
            echo json_encode(["status" => "ok"]);
        } else {
            echo json_encode(["status" => "erro", "mensagem" => "Erro ao sair"]);
        }
        break;

    default:
        echo json_encode(["status" => "erro", "mensagem" => "Ação desconhecida"]);
}
?>