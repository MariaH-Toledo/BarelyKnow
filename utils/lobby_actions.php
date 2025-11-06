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

$sql = "SELECT id_sala FROM salas WHERE codigo_sala = '$codigo'";
$result = $conn->query($sql);

if ($result->num_rows === 0) {
    echo json_encode(["status" => "erro", "mensagem" => "Sala não encontrada"]);
    exit;
}

$sala = $result->fetch_assoc();
$id_sala = $sala['id_sala'];

$id_jogador = $_SESSION['id_jogador'] ?? 0;
$sql_jogador = "SELECT is_host FROM jogadores WHERE id_jogador = $id_jogador AND id_sala = $id_sala";
$result_jogador = $conn->query($sql_jogador);
$jogador_atual = $result_jogador->fetch_assoc();

$eh_host = ($jogador_atual && $jogador_atual['is_host'] == 1);

switch ($acao) {
    case 'listar_jogadores':
        $sql = "SELECT DISTINCT id_jogador, nome, is_host FROM jogadores 
                WHERE id_sala = $id_sala 
                ORDER BY is_host DESC, id_jogador ASC";
        $result = $conn->query($sql);
        
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
        }

        $sql_count = "SELECT COUNT(DISTINCT id_jogador) as total FROM jogadores WHERE id_sala = $id_sala";
        $result_count = $conn->query($sql_count);
        $total = $result_count->fetch_assoc()['total'];
        
        if ($total < 2) {
            echo json_encode(["status" => "erro", "mensagem" => "Mínimo 2 jogadores"]);
            exit;
        }

        $conn->query("UPDATE salas SET status = 'iniciada' WHERE id_sala = $id_sala");
        echo json_encode(["status" => "ok"]);
        break;

    case 'sair_sala':
        if ($eh_host) {
            echo json_encode(["status" => "erro", "mensagem" => "Host deve fechar a sala em vez de sair"]);
            exit;
        }

        $conn->query("DELETE FROM jogadores WHERE id_jogador = $id_jogador");
        session_destroy();
        echo json_encode(["status" => "ok"]);
        break;

    case 'fechar_sala':
        if (!$eh_host) {
            echo json_encode(["status" => "erro", "mensagem" => "Apenas o host pode fechar a sala"]);
            exit;
        }

        $conn->query("DELETE FROM salas WHERE id_sala = $id_sala");
        session_destroy();
        echo json_encode(["status" => "ok"]);
        break;

    case 'verificar_status':
        $sql_status = "SELECT status FROM salas WHERE id_sala = $id_sala";
        $result_status = $conn->query($sql_status);
        
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