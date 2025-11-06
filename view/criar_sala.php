<?php
session_start();
require_once '../db/conexao.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome']);
    $categoria = $_POST['categoria'];
    $rodadas = $_POST['rodadas'];
    $tempo_resposta = $_POST['tempo_resposta'];

    if (empty($nome)) {
        echo json_encode(['status' => 'erro', 'mensagem' => 'Digite seu nome']);
        exit;
    }

    if (strlen($nome) > 100) {
        echo json_encode(['status' => 'erro', 'mensagem' => 'Nome muito longo']);
        exit;
    }

    $codigo_sala = substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 6);

    $conn->begin_transaction();

    try {
        $stmt = $conn->prepare("INSERT INTO salas (codigo_sala, id_categoria, rodadas, tempo_resposta) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("siii", $codigo_sala, $categoria, $rodadas, $tempo_resposta);
        $stmt->execute();
        $id_sala = $conn->insert_id;

        $stmt = $conn->prepare("INSERT INTO jogadores (nome, id_sala, is_host) VALUES (?, ?, 1)");
        $stmt->bind_param("si", $nome, $id_sala);
        $stmt->execute();
        $id_jogador = $conn->insert_id;

        $conn->commit();

        $_SESSION['id_jogador'] = $id_jogador;
        $_SESSION['nome_jogador'] = $nome;
        $_SESSION['id_sala'] = $id_sala;
        $_SESSION['codigo_sala'] = $codigo_sala;
        $_SESSION['is_host'] = true;

        echo json_encode(['status' => 'ok', 'codigo' => $codigo_sala]);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['status' => 'erro', 'mensagem' => 'Erro ao criar sala: ' . $e->getMessage()]);
    }
}
?>