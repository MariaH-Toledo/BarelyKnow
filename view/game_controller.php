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
    case 'carregar_pergunta':
        carregarPergunta($conn, $codigo_sala, $id_jogador);
        break;
    case 'enviar_resposta':
        enviarResposta($conn, $id_jogador);
        break;
    default:
        echo json_encode(['status' => 'erro', 'mensagem' => 'Ação inválida']);
}

function carregarPergunta($conn, $codigo_sala, $id_jogador) {
    $stmt = $conn->prepare("
        SELECT s.id_sala, s.id_categoria, s.rodadas, s.tempo_resposta,
               (SELECT COUNT(DISTINCT id_pergunta) FROM respostas WHERE id_jogador = ?) as rodada_atual
        FROM salas s
        WHERE s.codigo_sala = ?
    ");
    $stmt->bind_param("is", $id_jogador, $codigo_sala);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['status' => 'erro', 'mensagem' => 'Sala não encontrada']);
        return;
    }

    $sala = $result->fetch_assoc();
    $rodada_atual = $sala['rodada_atual'] + 1;

    if ($rodada_atual > $sala['rodadas']) {
        $stmt = $conn->prepare("UPDATE salas SET status = 'encerrada' WHERE codigo_sala = ?");
        $stmt->bind_param("s", $codigo_sala);
        $stmt->execute();
        echo json_encode(['status' => 'finalizado']);
        return;
    }

    $stmt = $conn->prepare("
        SELECT p.* 
        FROM perguntas p 
        WHERE p.id_categoria = ? 
        AND p.id_pergunta NOT IN (
            SELECT r.id_pergunta 
            FROM respostas r 
            WHERE r.id_jogador = ?
        )
        ORDER BY RAND() 
        LIMIT 1
    ");
    $stmt->bind_param("ii", $sala['id_categoria'], $id_jogador);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode(['status' => 'aguardando']);
        return;
    }

    $pergunta = $result->fetch_assoc();

    echo json_encode([
        'status' => 'ok',
        'pergunta' => $pergunta,
        'rodada_atual' => $rodada_atual,
        'total_rodadas' => $sala['rodadas'],
        'tempo_restante' => $sala['tempo_resposta']
    ]);
}

function enviarResposta($conn, $id_jogador) {
    $id_pergunta = $_POST['id_pergunta'];
    $resposta_escolhida = $_POST['resposta_escolhida'];
    $numero_alternativa = $_POST['numero_alternativa'];
    $tempo_resposta = $_POST['tempo_resposta'];

    $stmt = $conn->prepare("
        INSERT INTO respostas (id_jogador, id_pergunta, resposta_escolhida, numero_alternativa, tempo_resposta)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("iisii", $id_jogador, $id_pergunta, $resposta_escolhida, $numero_alternativa, $tempo_resposta);
    
    if ($stmt->execute()) {
        echo json_encode(['status' => 'ok']);
    } else {
        echo json_encode(['status' => 'erro', 'mensagem' => 'Erro ao salvar resposta']);
    }
}
?>