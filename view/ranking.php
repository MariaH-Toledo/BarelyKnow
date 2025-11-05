<?php
session_start();
include "../db/conexao.php";

$codigo = $_GET['codigo'] ?? null;

if (!$codigo) {
    die("<h2>Sala não encontrada.</h2>");
}

if (!isset($_SESSION['id_jogador'])) {
    die("<h2>Acesso negado. Faça login novamente.</h2>");
}

// Buscar jogadores ordenados por pontos
$sql_jogadores = "SELECT j.nome, j.pontos, j.is_host 
                  FROM jogadores j 
                  JOIN salas s ON j.id_sala = s.id_sala 
                  WHERE s.codigo_sala = ? 
                  ORDER BY j.pontos DESC 
                  LIMIT 15";
$stmt = $conn->prepare($sql_jogadores);
$stmt->bind_param("s", $codigo);
$stmt->execute();
$result = $stmt->get_result();

$jogadores = [];
while ($row = $result->fetch_assoc()) {
    $jogadores[] = $row;
}

// Verificar se é host
$ehHost = false;
$sql_host = "SELECT j.is_host FROM jogadores j 
             JOIN salas s ON j.id_sala = s.id_sala 
             WHERE s.codigo_sala = ? AND j.id_jogador = ?";
$stmt_host = $conn->prepare($sql_host);
$stmt_host->bind_param("si", $codigo, $_SESSION['id_jogador']);
$stmt_host->execute();
$result_host = $stmt_host->get_result();

if ($result_host->num_rows > 0) {
    $host_data = $result_host->fetch_assoc();
    $ehHost = ($host_data['is_host'] == 1);
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ranking - BarelyKnow</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../public/style/lobby.css">
    <style>
        .ranking-item {
            background: var(--cor-fundo);
            padding: 15px;
            border-radius: 8px;
            border: 1px solid var(--cor-azul2);
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .posicao {
            font-size: 1.5em;
            font-weight: bold;
            color: var(--cor-amarela);
            min-width: 50px;
        }
        .primeiro { color: gold; }
        .segundo { color: silver; }
        .terceiro { color: #cd7f32; }
    </style>
</head>
<body class="lobby-body">
    <div class="lobby-header">
        <h1 class="lobby-codigo">🏆 Ranking Final</h1>
        <p class="lobby-info">Sala: <?= htmlspecialchars($codigo) ?></p>
    </div>

    <div class="jogadores-section">
        <div class="jogadores-titulo">
            <i class="bi bi-trophy"></i> Classificação
        </div>
        <div class="lista-jogadores">
            <?php if (empty($jogadores)): ?>
                <div style="color: var(--cor-branco2); text-align: center;">
                    Nenhum jogador encontrado
                </div>
            <?php else: ?>
                <?php foreach ($jogadores as $index => $jogador): ?>
                    <div class="ranking-item">
                        <div style="display: flex; align-items: center; gap: 15px;">
                            <div class="posicao 
                                <?= $index == 0 ? 'primeiro' : '' ?>
                                <?= $index == 1 ? 'segundo' : '' ?>
                                <?= $index == 2 ? 'terceiro' : '' ?>">
                                <?= $index + 1 ?>º
                            </div>
                            <div class="jogador-nome">
                                <?= htmlspecialchars($jogador['nome']) ?>
                                <?= $jogador['is_host'] ? ' 👑' : '' ?>
                            </div>
                        </div>
                        <div style="color: var(--cor-amarela); font-weight: bold;">
                            <?= $jogador['pontos'] ?> pts
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <div class="botoes-lobby">
        <?php if ($ehHost): ?>
            <button class="btn-lobby btn-iniciar" onclick="reiniciarSala()">
                <i class="bi bi-arrow-repeat"></i> Reiniciar Sala
            </button>
            <button class="btn-lobby btn-fechar" onclick="fecharSala()">
                <i class="bi bi-x-circle"></i> Fechar Sala
            </button>
        <?php else: ?>
            <button class="btn-lobby btn-sair" onclick="sairSala()">
                <i class="bi bi-box-arrow-left"></i> Sair
            </button>
        <?php endif; ?>
    </div>

    <script>
        function reiniciarSala() {
            if (confirm('Reiniciar a sala? Todos voltarão ao lobby.')) {
                // Implementar reinício se necessário
                window.location.href = `lobby.php?codigo=<?= $codigo ?>`;
            }
        }
        
        function fecharSala() {
            if (confirm('Fechar a sala? Todos serão desconectados.')) {
                window.location.href = `../utils/lobby_actions.php?acao=fechar_sala&codigo=<?= $codigo ?>`;
            }
        }
        
        function sairSala() {
            window.location.href = '../index.php';
        }
    </script>
</body>
</html>