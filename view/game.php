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

$stmt = $conn->prepare("SELECT id_jogador FROM jogadores WHERE id_jogador = ? AND id_sala = ?");
$stmt->bind_param("ii", $id_jogador, $sala['id_sala']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: ../index.php");
    exit;
}

$stmt = $conn->prepare("SELECT COUNT(*) as respondidas FROM respostas WHERE id_jogador = ?");
$stmt->bind_param("i", $id_jogador);
$stmt->execute();
$result = $stmt->get_result();
$progresso = $result->fetch_assoc();
$pergunta_atual = $progresso['respondidas'] + 1;

if ($pergunta_atual > $sala['rodadas']) {
    header("Location: ranking.php?codigo=" . $codigo);
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
                <span class="pergunta-atual">Pergunta <?= $pergunta_atual ?>/<?= $sala['rodadas'] ?></span>
            </div>
            <div class="player-info">
                <span class="nome-jogador"><?= htmlspecialchars($nome_jogador) ?></span>
            </div>
        </div>

        <div class="pergunta-area">
            <div class="pergunta-box" id="perguntaBox">
                <p class="pergunta-texto" id="perguntaTexto">Carregando pergunta...</p>
            </div>
            
            <div class="temporizador-container">
                <div class="temporizador" id="temporizador">
                    <div class="tempo-bar" id="tempoBar"></div>
                    <span class="tempo-texto" id="tempoTexto"><?= $sala['tempo_resposta'] ?>s</span>
                </div>
            </div>
        </div>

        <div class="alternativas-container" id="alternativasContainer">
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
                        <span class="alt-texto" id="alt2">Carregando...</span>
                    </button>
                </div>
                <div class="col-md-6">
                    <button class="btn-alternativa" data-alternativa="3" onclick="responder(3)">
                        <span class="alt-letra">C</span>
                        <span class="alt-texto" id="alt3">Carregando...</span>
                    </button>
                </div>
                <div class="col-md-6">
                    <button class="btn-alternativa" data-alternativa="4" onclick="responder(4)">
                        <span class="alt-letra">D</span>
                        <span class="alt-texto" id="alt4">Carregando...</span>
                    </button>
                </div>
            </div>
        </div>

        <div class="feedback-container" id="feedbackContainer" style="display: none;">
            <div class="feedback-box" id="feedbackBox">
                <h3 id="feedbackTitulo"></h3>
                <p id="feedbackTexto"></p>
                <div class="proxima-pergunta" id="proximaPergunta">
                    Próxima pergunta em <span id="contadorProxima">5</span>s
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const codigoSala = "<?= $codigo ?>";
        const tempoTotal = <?= $sala['tempo_resposta'] ?>;
        const idJogador = <?= $id_jogador ?>;
        const perguntaAtual = <?= $pergunta_atual ?>;
        const totalRodadas = <?= $sala['rodadas'] ?>;

        let tempoRestante = tempoTotal;
        let temporizadorInterval;
        let perguntaAtualData = null;
        let respostaEnviada = false;

        async function carregarPergunta() {
            try {
                const response = await fetch('../utils/game_logic.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `acao=carregar_pergunta&codigo_sala=${codigoSala}&id_jogador=${idJogador}`
                });
                
                const data = await response.json();
                
                if (data.status === 'ok') {
                    perguntaAtualData = data.pergunta;
                    exibirPergunta(data.pergunta);
                    iniciarTemporizador();
                } else if (data.status === 'fim') {
                    window.location.href = 'ranking.php?codigo=' + codigoSala;
                }
            } catch (error) {
                console.error('Erro ao carregar pergunta:', error);
            }
        }

        function exibirPergunta(pergunta) {
            document.getElementById('perguntaTexto').textContent = pergunta.pergunta;
            document.getElementById('alt1').textContent = pergunta.alternativas[0];
            document.getElementById('alt2').textContent = pergunta.alternativas[1];
            document.getElementById('alt3').textContent = pergunta.alternativas[2];
            document.getElementById('alt4').textContent = pergunta.alternativas[3];
            
            document.querySelector('.pergunta-atual').textContent = `Pergunta ${pergunta.numero}/${totalRodadas}`;
            
            document.querySelectorAll('.btn-alternativa').forEach(btn => {
                btn.disabled = false;
                btn.classList.remove('correta', 'incorreta', 'selecionada');
            });
            
            respostaEnviada = false;
        }

        function iniciarTemporizador() {
            tempoRestante = tempoTotal;
            atualizarTemporizador();
            
            clearInterval(temporizadorInterval);
            temporizadorInterval = setInterval(() => {
                tempoRestante--;
                atualizarTemporizador();
                
                if (tempoRestante <= 0) {
                    clearInterval(temporizadorInterval);
                    if (!respostaEnviada) {
                        responder(0);
                    }
                }
            }, 1000);
        }

        function atualizarTemporizador() {
            const tempoBar = document.getElementById('tempoBar');
            const tempoTexto = document.getElementById('tempoTexto');
            
            const porcentagem = (tempoRestante / tempoTotal) * 100;
            tempoBar.style.width = porcentagem + '%';
            tempoTexto.textContent = tempoRestante + 's';
            
            if (porcentagem <= 25) {
                tempoBar.style.backgroundColor = 'var(--cor-rosa)';
            } else if (porcentagem <= 50) {
                tempoBar.style.backgroundColor = 'var(--cor-amarela)';
            } else {
                tempoBar.style.backgroundColor = 'var(--cor-azul)';
            }
        }

        async function responder(alternativaEscolhida) {
            if (respostaEnviada) return;
            
            respostaEnviada = true;
            clearInterval(temporizadorInterval);
            
            document.querySelectorAll('.btn-alternativa').forEach(btn => {
                btn.disabled = true;
            });
            
            if (alternativaEscolhida > 0) {
                const btnSelecionado = document.querySelector(`[data-alternativa="${alternativaEscolhida}"]`);
                btnSelecionado.classList.add('selecionada');
            }

            try {
                const response = await fetch('../utils/game_logic.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `acao=responder&codigo_sala=${codigoSala}&id_jogador=${idJogador}&alternativa=${alternativaEscolhida}&tempo_restante=${tempoRestante}`
                });
                
                const data = await response.json();
                mostrarFeedback(data);
                
            } catch (error) {
                console.error('Erro ao enviar resposta:', error);
            }
        }

        function mostrarFeedback(data) {
            const feedbackContainer = document.getElementById('feedbackContainer');
            const feedbackBox = document.getElementById('feedbackBox');
            const feedbackTitulo = document.getElementById('feedbackTitulo');
            const feedbackTexto = document.getElementById('feedbackTexto');
            const alternativasContainer = document.getElementById('alternativasContainer');

            if (perguntaAtualData && data.resposta_correta > 0) {
                const btnCorreta = document.querySelector(`[data-alternativa="${data.resposta_correta}"]`);
                btnCorreta.classList.add('correta');
            }

            if (data.acertou) {
                feedbackTitulo.textContent = '✅ Resposta Correta!';
                feedbackTitulo.style.color = 'var(--cor-azul)';
                feedbackTexto.textContent = `Você ganhou ${data.pontos} pontos!`;
            } else {
                if (data.alternativa_escolhida === 0) {
                    feedbackTitulo.textContent = '⏰ Tempo Esgotado!';
                    feedbackTexto.textContent = 'O tempo acabou antes de você responder.';
                } else {
                    feedbackTitulo.textContent = '❌ Resposta Incorreta';
                    feedbackTexto.textContent = 'Não foi dessa vez!';
                }
                feedbackTitulo.style.color = 'var(--cor-rosa)';
            }

            feedbackContainer.style.display = 'block';
            alternativasContainer.style.opacity = '0.6';

            let contador = 5;
            const contadorElement = document.getElementById('contadorProxima');
            
            const contadorInterval = setInterval(() => {
                contador--;
                contadorElement.textContent = contador;
                
                if (contador <= 0) {
                    clearInterval(contadorInterval);
                    avancarPergunta();
                }
            }, 1000);
        }

        function avancarPergunta() {
            if (perguntaAtual >= totalRodadas) {
                window.location.href = 'ranking.php?codigo=' + codigoSala;
            } else {
                window.location.reload();
            }
        }

        carregarPergunta();
    </script>
</body>
</html>