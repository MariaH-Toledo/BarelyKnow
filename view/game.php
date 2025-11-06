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
             WHERE s.codigo_sala = ? AND s.status = 'iniciada'";
$stmt = $conn->prepare($sql_sala);
$stmt->bind_param("s", $codigo);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: lobby.php?codigo=" . $codigo);
    exit;
}

$sala = $result->fetch_assoc();
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
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../public/js/game.js"></script>
    
    <script>
        const codigoSala = "<?= $codigo ?>";
        const tempoResposta = <?= $sala['tempo_resposta'] ?>;
        const totalRodadas = <?= $sala['rodadas'] ?>;
        const idJogador = <?= $_SESSION['id_jogador'] ?>;
    </script>
</body>
</html>