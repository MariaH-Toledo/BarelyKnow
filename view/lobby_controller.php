<?php
session_start();
require_once '../db/conexao.php';

if (!isset($_SESSION['id_jogador']) || !isset($_POST['codigo_sala'])) {
    echo json_encode(['status' => 'erro', 'mensagem' => 'Sessão inválida']);
    exit;
}

$acao = $_POST['acao'];
$codigo_sala = $_POST['codigo_sala'];
$id_jogador = $_SESSION['id_jogador'];

switch ($acao) {
    case 'listar_jogadores':
        listarJogadores($conn, $codigo_sala, $id_jogador);
        break;
    case 'iniciar_jogo':
        iniciarJogo($conn, $codigo_sala, $id_jogador);
        break;
    case 'encerrar_sala':
        encerrarSala($conn, $codigo_sala, $id_jogador);
        break;
    case 'sair_sala':
        sairSala($conn, $id_jogador);
        break;
    default:
        echo json_encode(['status' => 'erro', 'mensagem' => 'Ação inválida']);
}

function listarJogadores($conn, $codigo_sala, $id_jogador) {
    $stmt = $conn->prepare("
        SELECT j.id_jogador, j.nome, j.is_host,
               (j.id_jogador = ?) as is_vc
        FROM jogadores j
        INNER JOIN salas s ON j.id_sala = s.id_sala
        WHERE s.codigo_sala = ?
        ORDER BY j.is_host DESC, j.id_jogador ASC
    ");
    $stmt->bind_param("is", $id_jogador, $codigo_sala);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $jogadores = [];
    while ($row = $result->fetch_assoc()) {
        $jogadores[] = $row;
    }

    $is_host = false;
    foreach ($jogadores as $jogador) {
        if ($jogador['id_jogador'] == $id_jogador && $jogador['is_host']) {
            $is_host = true;
            break;
        }
    }

    echo json_encode([
        'status' => 'ok',
        'jogadores' => $jogadores,
        'is_host' => $is_host
    ]);
}

function iniciarJogo($conn, $codigo_sala, $id_jogador) {
    $stmt = $conn->prepare("
        SELECT j.is_host 
        FROM jogadores j 
        INNER JOIN salas s ON j.id_sala = s.id_sala 
        WHERE j.id_jogador = ? AND s.codigo_sala = ?
    ");
    $stmt->bind_param("is", $id_jogador, $codigo_sala);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0 || !$result->fetch_assoc()['is_host']) {
        echo json_encode(['status' => 'erro', 'mensagem' => 'Apenas o host pode iniciar o jogo']);
        return;
    }

    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM jogadores j INNER JOIN salas s ON j.id_sala = s.id_sala WHERE s.codigo_sala = ?");
    $stmt->bind_param("s", $codigo_sala);
    $stmt->execute();
    $result = $stmt->get_result();
    $total_jogadores = $result->fetch_assoc()['total'];

    if ($total_jogadores < 2) {
        echo json_encode(['status' => 'erro', 'mensagem' => 'É necessário pelo menos 2 jogadores para iniciar']);
        return;
    }

    $stmt = $conn->prepare("UPDATE salas SET status = 'iniciada' WHERE codigo_sala = ?");
    $stmt->bind_param("s", $codigo_sala);
    
    if ($stmt->execute()) {
        echo json_encode(['status' => 'ok']);
    } else {
        echo json_encode(['status' => 'erro', 'mensagem' => 'Erro ao iniciar jogo']);
    }
}

function encerrarSala($conn, $codigo_sala, $id_jogador) {
    $stmt = $conn->prepare("
        SELECT j.is_host 
        FROM jogadores j 
        INNER JOIN salas s ON j.id_sala = s.id_sala 
        WHERE j.id_jogador = ? AND s.codigo_sala = ?
    ");
    $stmt->bind_param("is", $id_jogador, $codigo_sala);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0 || !$result->fetch_assoc()['is_host']) {
        echo json_encode(['status' => 'erro', 'mensagem' => 'Apenas o host pode encerrar a sala']);
        return;
    }

    $stmt = $conn->prepare("DELETE FROM salas WHERE codigo_sala = ?");
    $stmt->bind_param("s", $codigo_sala);
    
    if ($stmt->execute()) {
        session_destroy();
        echo json_encode(['status' => 'ok']);
    } else {
        echo json_encode(['status' => 'erro', 'mensagem' => 'Erro ao encerrar sala']);
    }
}

function sairSala($conn, $id_jogador) {
    $stmt = $conn->prepare("DELETE FROM jogadores WHERE id_jogador = ?");
    $stmt->bind_param("i", $id_jogador);
    
    if ($stmt->execute()) {
        session_destroy();
        echo json_encode(['status' => 'ok']);
    } else {
        echo json_encode(['status' => 'erro', 'mensagem' => 'Erro ao sair da sala']);
    }
}
?>