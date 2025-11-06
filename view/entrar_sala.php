<?php
session_start();
require_once '../db/conexao.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome']);
    $codigo_sala = strtoupper(trim($_POST['codigo_sala']));

    if (empty($nome) || empty($codigo_sala)) {
        echo json_encode(['status' => 'erro', 'mensagem' => 'Preencha todos os campos']);
        exit;
    }

    if (strlen($nome) > 100) {
        echo json_encode(['status' => 'erro', 'mensagem' => 'Nome muito longo']);
        exit;
    }

    $stmt = $conn->prepare("SELECT id_sala, status FROM salas WHERE codigo_sala = ?");
    $stmt->bind_param("s", $codigo_sala);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode(['status' => 'erro', 'mensagem' => 'Sala não encontrada']);
        exit;
    }

    $sala = $result->fetch_assoc();

    if ($sala['status'] !== 'aberta') {
        echo json_encode(['status' => 'erro', 'mensagem' => 'Esta sala não está mais aberta']);
        exit;
    }

    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM jogadores WHERE id_sala = ?");
    $stmt->bind_param("i", $sala['id_sala']);
    $stmt->execute();
    $result = $stmt->get_result();
    $total_jogadores = $result->fetch_assoc()['total'];

    if ($total_jogadores >= 8) {
        echo json_encode(['status' => 'erro', 'mensagem' => 'Sala lotada (máximo 8 jogadores)']);
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO jogadores (nome, id_sala) VALUES (?, ?)");
    $stmt->bind_param("si", $nome, $sala['id_sala']);
    
    if ($stmt->execute()) {
        $_SESSION['id_jogador'] = $conn->insert_id;
        $_SESSION['nome_jogador'] = $nome;
        $_SESSION['id_sala'] = $sala['id_sala'];
        $_SESSION['codigo_sala'] = $codigo_sala;
        $_SESSION['is_host'] = false;

        echo json_encode(['status' => 'ok', 'codigo' => $codigo_sala]);
    } else {
        echo json_encode(['status' => 'erro', 'mensagem' => 'Erro ao entrar na sala']);
    }
}
?>