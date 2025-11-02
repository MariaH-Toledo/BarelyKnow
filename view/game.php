<?php
session_start();
include "../db/conexao.php";

$codigo = $_GET['codigo'] ?? null;

if (!$codigo) {
    die("<h2>Sala não encontrada.</h2>");
}

if (!isset($_SESSION['id_jogador'])) {
    die("<h2>Acesso negado.</h2>");
}

$sql_sala = "SELECT s.*, c.nome_categoria FROM salas s 
             JOIN categorias c ON s.id_categoria = c.id_categoria 
             WHERE s.codigo_sala = ?";
$stmt = $conn->prepare($sql_sala);
$stmt->bind_param("s", $codigo);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("<h2>Sala não encontrada.</h2>");
}

$sala = $result->fetch_assoc();

if ($sala['status'] !== 'iniciada') {
    header("Location: lobby.php?codigo=" . $codigo);
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jogo - BarelyKnow</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../public/style/index.css">
    <style>
        .pergunta-container {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            color: white;
        }
        .alternativa {
            background: var(--cor-fundo2);
            border: 2px solid #444;
            border-radius: 10px;
            padding: 1rem;
            margin: 0.5rem 0;
            cursor: pointer;
            transition: all 0.3s ease;
            color: white;
        }
        .alternativa:hover {
            border-color: var(--cor-azul);
            transform: translateY(-2px);
        }
        .alternativa.selecionada {
            border-color: var(--cor-azul);
            background: var(--cor-azul2);
        }
        .timer-container {
            position: fixed;
            top: 20px;
            right: 20px;
            background: var(--cor-fundo2);
            border-radius: 50%;
            width: 80px;
            height: 80px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            font-weight: bold;
            border: 3px solid var(--cor-azul);
        }
        .rodada-info {
            position: fixed;
            top: 20px;
            left: 20px;
            background: var(--cor-fundo2);
            padding: 10px 20px;
            border-radius: 10px;
            font-size: 1.1rem;
        }
    </style>
</head>
<body class="body-index">
    <div class="timer-container">
        <span id="timer">--</span>
    </div>
    
    <div class="rodada-info">
        Rodada: <span id="numeroRodada">1</span>/<span id="totalRodadas"><?= $sala['rodadas'] ?></span>
    </div>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="pergunta-container text-center">
                    <h2 id="perguntaTexto">Carregando pergunta...</h2>
                </div>
                
                <div class="alternativas-container">
                    <div class="alternativa" data-alternativa="1">
                        <span id="textoAlternativa1">Carregando...</span>
                    </div>
                    <div class="alternativa" data-alternativa="2">
                        <span id="textoAlternativa2">Carregando...</span>
                    </div>
                    <div class="alternativa" data-alternativa="3">
                        <span id="textoAlternativa3">Carregando...</span>
                    </div>
                    <div class="alternativa" data-alternativa="4">
                        <span id="textoAlternativa4">Carregando...</span>
                    </div>
                </div>
                
                <div id="feedback" class="mt-3 text-center"></div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../public/js/game.js"></script>
    
    <script>
        const codigoSala = "<?= $codigo ?>";
        const tempoResposta = <?= $sala['tempo_resposta'] ?>;
        const totalRodadas = <?= $sala['rodadas'] ?>;
        const idJogador = <?= $_SESSION['id_jogador'] ?>;
    </script>
</body>
</html>