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

$stmt = $conn->prepare("SELECT id_sala FROM salas WHERE codigo_sala = ?");
$stmt->bind_param("s", $codigo_sala);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(["status" => "erro", "mensagem" => "Sala não encontrada"]);
    exit;
}

$sala = $result->fetch_assoc();
$id_sala = $sala['id_sala'];
$id_jogador = $_SESSION['id_jogador'] ?? 0;

switch ($acao) {
    case 'listar_jogadores':
        listarJogadores($conn, $id_sala);
        break;
        
    case 'iniciar_jogo':
        iniciarJogo($conn, $id_sala, $id_jogador);
        break;
        
    case 'fechar_sala':
        fecharSala($conn, $id_sala, $id_jogador);
        break;
        
    case 'sair_sala':
        sairSala($conn, $id_jogador);
        break;
        
    case 'verificar_status':
        verificarStatus($conn, $id_sala);
        break;
        
    default:
        echo json_encode(["status" => "erro", "mensagem" => "Ação desconhecida"]);
}


function listarJogadores($conn, $id_sala) {
    $stmt = $conn->prepare("SELECT id_jogador, nome, is_host FROM jogadores WHERE id_sala = ? ORDER BY is_host DESC, id_jogador ASC");
    $stmt->bind_param("i", $id_sala);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $jogadores = [];
    while ($row = $result->fetch_assoc()) {
        $jogadores[] = $row;
    }
    
    echo json_encode(["status" => "ok", "jogadores" => $jogadores]);
}

function iniciarJogo($conn, $id_sala, $id_jogador) {
    $stmt = $conn->prepare("SELECT is_host FROM jogadores WHERE id_jogador = ? AND id_sala = ?");
    $stmt->bind_param("ii", $id_jogador, $id_sala);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(["status" => "erro", "mensagem" => "Jogador não encontrado"]);
        return;
    }
    
    $jogador = $result->fetch_assoc();
    
    if ($jogador['is_host'] != 1) {
        echo json_encode(["status" => "erro", "mensagem" => "Apenas o host pode iniciar o jogo"]);
        return;
    }
    
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM jogadores WHERE id_sala = ?");
    $stmt->bind_param("i", $id_sala);
    $stmt->execute();
    $result = $stmt->get_result();
    $total = $result->fetch_assoc()['total'];
    
    if ($total < 2) {
        echo json_encode(["status" => "erro", "mensagem" => "Mínimo 2 jogadores para iniciar"]);
        return;
    }
    
    $stmt = $conn->prepare("UPDATE salas SET status = 'iniciada' WHERE id_sala = ?");
    $stmt->bind_param("i", $id_sala);
    
    if ($stmt->execute()) {
        echo json_encode(["status" => "ok", "mensagem" => "Jogo iniciado com sucesso"]);
    } else {
        echo json_encode(["status" => "erro", "mensagem" => "Erro ao iniciar jogo"]);
    }
}

function fecharSala($conn, $id_sala, $id_jogador) {
    $stmt = $conn->prepare("SELECT is_host FROM jogadores WHERE id_jogador = ? AND id_sala = ?");
    $stmt->bind_param("ii", $id_jogador, $id_sala);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(["status" => "erro", "mensagem" => "Jogador não encontrado"]);
        return;
    }
    
    $jogador = $result->fetch_assoc();
    
    if ($jogador['is_host'] != 1) {
        echo json_encode(["status" => "erro", "mensagem" => "Apenas o host pode fechar a sala"]);
        return;
    }
    
    $stmt = $conn->prepare("DELETE FROM salas WHERE id_sala = ?");
    $stmt->bind_param("i", $id_sala);
    
    if ($stmt->execute()) {
        echo json_encode(["status" => "ok"]);
    } else {
        echo json_encode(["status" => "erro", "mensagem" => "Erro ao fechar sala"]);
    }
}

function sairSala($conn, $id_jogador) {
    $stmt = $conn->prepare("DELETE FROM jogadores WHERE id_jogador = ?");
    $stmt->bind_param("i", $id_jogador);
    
    if ($stmt->execute()) {
        echo json_encode(["status" => "ok"]);
    } else {
        echo json_encode(["status" => "erro", "mensagem" => "Erro ao sair da sala"]);
    }
}

function verificarStatus($conn, $id_sala) {
    $stmt = $conn->prepare("SELECT status FROM salas WHERE id_sala = ?");
    $stmt->bind_param("i", $id_sala);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(["status" => "erro", "mensagem" => "Sala não encontrada"]);
        return;
    }
    
    $sala = $result->fetch_assoc();
    echo json_encode(["status" => $sala['status']]);
}
?>