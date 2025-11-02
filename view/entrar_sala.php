<?php
session_start();
header("Content-Type: application/json");
include "../db/conexao.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nome = trim($_POST["nome"] ?? "");
    $codigo_sala = strtoupper(trim($_POST["codigo_sala"] ?? ""));

    if (empty($nome) || empty($codigo_sala)) {
        echo json_encode(["status" => "erro", "mensagem" => "Preencha todos os campos."]);
        exit;
    }

    $sql = "SELECT id_sala FROM salas WHERE codigo_sala = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $codigo_sala);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode(["status" => "erro", "mensagem" => "Sala não encontrada. Verifique o código."]);
        exit;
    }

    $sala = $result->fetch_assoc();
    $id_sala = $sala['id_sala'];

    $sql_status = "SELECT status FROM salas WHERE id_sala = ?";
    $stmt_status = $conn->prepare($sql_status);
    $stmt_status->bind_param("i", $id_sala);
    $stmt_status->execute();
    $result_status = $stmt_status->get_result();
    $status_sala = $result_status->fetch_assoc();

    if ($status_sala['status'] !== 'aberta') {
        echo json_encode(["status" => "erro", "mensagem" => "Esta sala não está mais aceitando jogadores."]);
        exit;
    }

    $sql_jogador = "INSERT INTO jogadores (nome, id_sala, is_host) VALUES (?, ?, 0)";
    $stmt_jogador = $conn->prepare($sql_jogador);
    $stmt_jogador->bind_param("si", $nome, $id_sala);

    if ($stmt_jogador->execute()) {
        $_SESSION['id_jogador'] = $stmt_jogador->insert_id;
        $_SESSION['nome_jogador'] = $nome;
        $_SESSION['id_sala'] = $id_sala;
        $_SESSION['eh_host'] = false;

        echo json_encode([
            "status" => "ok",
            "codigo" => $codigo_sala
        ]);
    } else {
        echo json_encode([
            "status" => "erro",
            "mensagem" => "Erro ao entrar na sala: " . $conn->error
        ]);
    }
}
?>