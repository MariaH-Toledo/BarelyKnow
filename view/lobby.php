<?php
session_start();
include "../db/conexao.php";

$codigo = $_GET['codigo'] ?? header("Location: ../index.php");

// DEBUG TEMPORÁRIO
error_log("=== DEBUG LOBBY ===");
error_log("Código da sala: " . $codigo);
error_log("Session ID: " . ($_SESSION['id_jogador'] ?? 'NÃO SETADO'));
error_log("===================");

$sql = "SELECT s.*, c.nome_categoria, j.is_host 
        FROM salas s 
        JOIN categorias c ON s.id_categoria = c.id_categoria
        JOIN jogadores j ON s.id_sala = j.id_sala AND j.id_jogador = ?
        WHERE s.codigo_sala = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("is", $_SESSION['id_jogador'], $codigo);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    error_log("ERRO: Acesso negado - sala não encontrada ou jogador não pertence à sala");
    die("<h2>Acesso negado. Entre na sala novamente.</h2>");
}

$dados = $result->fetch_assoc();
$ehHost = ($dados['is_host'] == 1);

// DEBUG
error_log("É host: " . ($ehHost ? 'SIM' : 'NÃO'));
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lobby - <?= $codigo ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../public/style/lobby.css">
</head>
<body class="lobby-body">
    <div class="lobby-header">
        <h1 class="lobby-codigo">Sala: <?= $codigo ?></h1>
        <p class="lobby-info">
            <?= $dados['nome_categoria'] ?> • 
            <?= $dados['rodadas'] ?> rodadas • 
            <?= $dados['tempo_resposta'] ?> segundos por pergunta
        </p>
        
        <?php if ($ehHost): ?>
            <p style="color: var(--cor-amarela);">
                <i class="bi bi-star-fill"></i> Você é o Host
            </p>
        <?php else: ?>
            <p style="color: var(--cor-azul);">
                <i class="bi bi-person"></i> Aguardando host iniciar o jogo...
            </p>
        <?php endif; ?>
    </div>

    <div class="jogadores-section">
        <div class="jogadores-titulo">
            <i class="bi bi-people"></i> Jogadores
            <span class="contador-jogadores" id="contadorJogadores">0</span>
        </div>
        <div id="listaJogadores" class="lista-jogadores">
            <div style="color: var(--cor-branco2); text-align: center;">
                Carregando jogadores...
            </div>
        </div>
    </div>

    <div class="botoes-lobby">
        <?php if ($ehHost): ?>
            <button id="btnIniciar" class="btn-lobby btn-iniciar" disabled>
                <i class="bi bi-play-circle"></i> Iniciar Jogo
            </button>
            <button id="btnFechar" class="btn-lobby btn-fechar">
                <i class="bi bi-x-circle"></i> Fechar Sala
            </button>
        <?php else: ?>
            <button id="btnSair" class="btn-lobby btn-sair">
                <i class="bi bi-box-arrow-left"></i> Sair da Sala
            </button>
        <?php endif; ?>
    </div>

    <div class="botoes-lobby">
        <?php if ($ehHost): ?>
            <button id="btnIniciar" class="btn-lobby btn-iniciar" disabled>
                <i class="bi bi-play-circle"></i> Iniciar Jogo
            </button>
            <button id="btnFechar" class="btn-lobby btn-fechar">
                <i class="bi bi-x-circle"></i> Fechar Sala
            </button>
        <?php else: ?>
            <button id="btnSair" class="btn-lobby btn-sair">
                <i class="bi bi-box-arrow-left"></i> Sair
            </button>
        <?php endif; ?>
    </div>

    <script>
        // 🚨 CORREÇÃO: Verificar se as variáveis PHP estão definidas
        const codigoSala = "<?= isset($codigo) ? $codigo : '' ?>";
        const ehHost = <?= isset($ehHost) && $ehHost ? 'true' : 'false'; ?>;
        const idJogador = <?= isset($_SESSION['id_jogador']) ? $_SESSION['id_jogador'] : 'null'; ?>;
        
        console.log('🎯 Debug Lobby:');
        console.log('Código:', codigoSala);
        console.log('É Host:', ehHost);
        console.log('ID Jogador:', idJogador);
        
        // 🚨 VERIFICAÇÃO EXTRA: Se codigoSala está vazio
        if (!codigoSala) {
            console.error('❌ ERRO: codigoSala está vazio!');
            alert('Erro: Código da sala não encontrado. Volte para a página inicial.');
            window.location.href = '../index.php';
        }
    </script>

    <script src="../public/js/lobby.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</body>
</html>