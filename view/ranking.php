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

$id_jogador = $_SESSION['id_jogador'] ?? 0;

$stmt = $conn->prepare("
    SELECT j.nome, j.pontos, j.is_host 
    FROM jogadores j 
    WHERE j.id_sala = ? 
    ORDER BY j.pontos DESC, j.id_jogador ASC 
    LIMIT 10
");
$stmt->bind_param("i", $sala['id_sala']);
$stmt->execute();
$result = $stmt->get_result();

$ranking = [];
$posicao = 1;
while ($row = $result->fetch_assoc()) {
    $ranking[] = [
        'posicao' => $posicao++,
        'nome' => $row['nome'],
        'pontos' => $row['pontos'],
        'is_host' => $row['is_host']
    ];
}

$stmt = $conn->prepare("UPDATE salas SET status = 'encerrada' WHERE id_sala = ?");
$stmt->bind_param("i", $sala['id_sala']);
$stmt->execute();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ranking - BarelyKnow</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../public/style/ranking.css">
</head>
<body class="body-ranking">
    <div class="container-ranking">
        <div class="header-ranking">
            <h1 class="titulo-ranking">🏆 Ranking Final</h1>
            <p class="info-ranking">
                Sala: <strong><?= $codigo ?></strong> • 
                <?= $sala['nome_categoria'] ?> • 
                <?= $sala['rodadas'] ?> rodadas
            </p>
        </div>

        <div class="ranking-box">
            <div class="ranking-header">
                <span>Posição</span>
                <span>Jogador</span>
                <span>Pontuação</span>
            </div>
            
            <div class="ranking-list">
                <?php foreach ($ranking as $jogador): ?>
                    <div class="ranking-item <?= $jogador['posicao'] === 1 ? 'primeiro' : '' ?>">
                        <span class="posicao">
                            <?= $jogador['posicao'] ?>º
                            <?php if ($jogador['posicao'] === 1): ?>
                                👑
                            <?php endif; ?>
                        </span>
                        <span class="nome-jogador">
                            <?= htmlspecialchars($jogador['nome']) ?>
                            <?php if ($jogador['is_host']): ?>
                                <small class="badge-host">HOST</small>
                            <?php endif; ?>
                        </span>
                        <span class="pontuacao"><?= $jogador['pontos'] ?> pts</span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="botoes-ranking">
            <button class="btn btn-voltar-inicio" onclick="window.location.href='../index.php'">
                <i class="bi bi-house"></i> Voltar ao Início
            </button>
            
            <?php if ($_SESSION['is_host'] ?? false): ?>
                <button class="btn btn-nova-sala" onclick="window.location.href='../index.php'">
                    <i class="bi bi-plus-circle"></i> Criar Nova Sala
                </button>
            <?php endif; ?>
        </div>

        <div class="footer-ranking">
            <p>Obrigado por jogar BarelyKnow! 🎮</p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>