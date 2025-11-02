<?php
session_start();
include "../db/conexao.php";
include "../utils/categorias.php";

$codigo = $_GET['codigo'] ?? null;

if (!$codigo) {
    die("<h2>Sala não encontrada.</h2>");
}

if (!isset($_SESSION['id_jogador']) || !isset($_SESSION['id_sala'])) {
    die("<h2>Acesso negado. Entre na sala novamente.</h2>");
}

$sql = "SELECT s.*, c.nome_categoria 
        FROM salas s 
        JOIN categorias c ON s.id_categoria = c.id_categoria
        WHERE s.codigo_sala = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $codigo);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("<h2>Sala não encontrada.</h2>");
}

$sala = $result->fetch_assoc();

$sql_verificar_jogador = "SELECT id_jogador, is_host FROM jogadores WHERE id_jogador = ? AND id_sala = ?";
$stmt_verificar = $conn->prepare($sql_verificar_jogador);
$stmt_verificar->bind_param("ii", $_SESSION['id_jogador'], $sala['id_sala']);
$stmt_verificar->execute();
$result_verificar = $stmt_verificar->get_result();

if ($result_verificar->num_rows === 0) {
    die("<h2>Você não pertence a esta sala.</h2>");
}

$dados_jogador = $result_verificar->fetch_assoc();
$ehHost = ($dados_jogador['is_host'] == 1);

$sql_jogadores_count = "SELECT COUNT(*) as total FROM jogadores WHERE id_sala = ?";
$stmt_count = $conn->prepare($sql_jogadores_count);
$stmt_count->bind_param("i", $sala['id_sala']);
$stmt_count->execute();
$result_count = $stmt_count->get_result();
$total_jogadores = $result_count->fetch_assoc()['total'];

$podeIniciar = ($total_jogadores >= 2);

if ($sala['status'] === 'iniciada') {
    header("Location: game.php?codigo=" . $codigo);
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lobby - <?= htmlspecialchars($codigo) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../public/style/index.css">
    <link rel="icon" href="public/img/icon.png">
</head>
<body class="body-index text-center">
    <div class="container py-5">
        <h1 class="titulo-index">Lobby - <?= htmlspecialchars($codigo) ?></h1>
        <p class="text-light">Categoria: <strong><?= htmlspecialchars($sala['nome_categoria']) ?></strong></p>
        <p class="text-light">Rodadas: <strong><?= $sala['rodadas'] ?></strong> | Tempo: <strong><?= $sala['tempo_resposta'] ?>s</strong></p>

        <?php if ($ehHost): ?>
            <div class="alert alert-info mb-3">
                <i class="bi bi-star-fill"></i> <strong>Você é o Host - Controle total da sala</strong>
            </div>
        <?php else: ?>
            <div class="alert alert-secondary mb-3">
                <i class="bi bi-person"></i> <strong>Jogador Convidado</strong> - Aguardando host iniciar o jogo
            </div>
        <?php endif; ?>

        <div class="card mt-4 bg-dark text-white">
            <div class="card-body">
                <h5 class="card-title">
                    <i class="bi bi-people-fill"></i> Jogadores na Sala 
                    <span class="badge bg-primary"><?= $total_jogadores ?></span>
                </h5>
                <div id="listaJogadores" class="mt-3">
                    <div class="text-muted">Carregando jogadores...</div>
                </div>
            </div>
        </div>

        <div class="mt-4 d-flex justify-content-center gap-3 flex-wrap">
            <?php if ($ehHost): ?>
                <button id="btnIniciar" class="btn btn-success btn-lg" <?= !$podeIniciar ? 'disabled' : '' ?>>
                    <i class="bi bi-play-circle"></i> 
                    <?= $podeIniciar ? 'Iniciar Jogo' : 'Aguardando Jogadores' ?>
                </button>
                <button id="btnFechar" class="btn btn-danger btn-lg">
                    <i class="bi bi-x-circle"></i> Fechar Sala
                </button>
            <?php else: ?>
                <button id="btnSair" class="btn btn-warning btn-lg">
                    <i class="bi bi-box-arrow-left"></i> Sair da Sala
                </button>
            <?php endif; ?>
        </div>

        <?php if ($ehHost && !$podeIniciar): ?>
            <div class="mt-3 text-info">
                <small><i class="bi bi-info-circle"></i> Aguardando mais jogadores... (mínimo 2)</small>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../public/js/lobby.js"></script>

    <script>
        const codigoSala = "<?= $codigo ?>";
        const ehHost = <?= $ehHost ? 'true' : 'false'; ?>;
        const idJogadorAtual = <?= $_SESSION['id_jogador'] ?>;
    </script>
</body>
</html>