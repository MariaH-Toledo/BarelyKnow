<?php
session_start();
include "../db/conexao.php";

$codigo = $_GET['codigo'] ?? '';

if (empty($codigo)) {
    header("Location: ../index.php");
    exit;
}

$stmt = $conn->prepare("SELECT s.*, c.nome_categoria FROM salas s JOIN categorias c ON s.id_categoria = c.id_categoria WHERE s.codigo_sala = ?");
$stmt->bind_param("s", $codigo);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: ../index.php");
    exit;
}

$sala = $result->fetch_assoc();

if ($sala['status'] !== 'iniciada') {
    header("Location: lobby.php?codigo=" . $codigo);
    exit;
}

$id_jogador = $_SESSION['id_jogador'] ?? 0;
$nome_jogador = $_SESSION['nome_jogador'] ?? '';

$stmt = $conn->prepare("SELECT id_jogador, is_host FROM jogadores WHERE id_jogador = ? AND id_sala = ?");
$stmt->bind_param("ii", $id_jogador, $sala['id_sala']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: ../index.php");
    exit;
}

$jogador = $result->fetch_assoc();
$eh_host = ($jogador['is_host'] == 1);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jogo - BarelyKnow</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../public/style/game.css">
</head>
<body class="body-game">
    <div class="container-game">
        <div class="game-header">
            <div class="sala-info">
                <span class="codigo-sala">Sala: <?= $codigo ?></span>
                <span class="categoria"><?= $sala['nome_categoria'] ?></span>
            </div>
            <div class="progresso">
                <span class="pergunta-atual" id="perguntaAtual">Carregando...</span>
            </div>
            <div class="player-info">
                <span class="nome-jogador"><?= htmlspecialchars($nome_jogador) ?></span>
            </div>
        </div>

        <div class="pergunta-area">
            <div class="pergunta-box">
                <p class="pergunta-texto" id="perguntaTexto">Carregando pergunta...</p>
            </div>
            
            <div class="temporizador-container">
                <div class="temporizador">
                    <div class="tempo-bar" id="tempoBar"></div>
                    <span class="tempo-texto" id="tempoTexto">0s</span>
                </div>
            </div>
        </div>

        <div class="alternativas-container">
            <div class="row g-3">
                <div class="col-md-6">
                    <button class="btn-alternativa" data-alternativa="1" onclick="responder(1)">
                        <span class="alt-letra">A</span>
                        <span class="alt-texto" id="alt1">Carregando...</span>
                    </button>
                </div>
                <div class="col-md-6">
                    <button class="btn-alternativa" data-alternativa="2" onclick="responder(2)">
                        <span class="alt-letra">B</span>