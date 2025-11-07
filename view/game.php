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
            <div class="feedback-box">
                <h3 id="feedbackTitulo"></h3>
                <p id="feedbackTexto"></p>
                <div class="proxima-pergunta">
                    Aguardando próxima pergunta...
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const codigoSala = "<?= $codigo ?>";
        const idJogador = <?= $id_jogador ?>;
        
        let tempoRestante = 0;
        let tempoTotal = 0;
        let temporizadorInterval = null;
        let respostaEnviada = false;
        let perguntaAtualData = null;
        let momentoClique = 0;
        
        async function carregarPergunta() {
            try {
                const formData = new FormData();
                formData.append('acao', 'carregar_pergunta');
                formData.append('codigo_sala', codigoSala);
                formData.append('id_jogador', idJogador);

                const response = await fetch('../utils/game_logic.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.status === 'ok') {
                    perguntaAtualData = data.pergunta;
                    exibirPergunta(data.pergunta);
                    iniciarTemporizador(data.pergunta.tempo_restante);
                } else if (data.status === 'fim') {
                    window.location.href = 'ranking.php?codigo=' + codigoSala;
                } else if (data.status === 'aguardando') {
                    setTimeout(carregarPergunta, 1000);
                }
            } catch (error) {
                console.error('Erro:', error);
                setTimeout(carregarPergunta, 2000);
            }
        }

        function exibirPergunta(pergunta) {
            document.getElementById('perguntaTexto').textContent = pergunta.pergunta;
            document.getElementById('alt1').textContent = pergunta.alternativas[0];
            document.getElementById('alt2').textContent = pergunta.alternativas[1];
            document.getElementById('alt3').textContent = pergunta.alternativas[2];
            document.getElementById('alt4').textContent = pergunta.alternativas[3];
            document.getElementById('perguntaAtual').textContent = `Pergunta ${pergunta.numero}/${pergunta.total}`;
            
            document.querySelectorAll('.btn-alternativa').forEach(btn => {
                btn.disabled = false;
                btn.classList.remove('correta', 'incorreta', 'selecionada');
            });
            
            if (pergunta.ja_respondeu) {
                document.querySelectorAll('.btn-alternativa').forEach(btn => btn.disabled = true);
            }
            
            respostaEnviada = pergunta.ja_respondeu;
        }

        function iniciarTemporizador(tempoInicial) {
            tempoRestante = tempoInicial;
            tempoTotal = tempoInicial;
            atualizarTemporizador();
            
            if (temporizadorInterval) clearInterval(temporizadorInterval);
            
            temporizadorInterval = setInterval(() => {
                tempoRestante -= 0.1;
                
                if (tempoRestante <= 0) {
                    tempoRestante = 0;
                    clearInterval(temporizadorInterval);
                    if (!respostaEnviada) responder(0);
                }
                
                atualizarTemporizador();
            }, 100);
        }

        function atualizarTemporizador() {
            const tempoBar = document.getElementById('tempoBar');
            const tempoTexto = document.getElementById('tempoTexto');
            const porcentagem = (tempoRestante / tempoTotal) * 100;
            
            tempoBar.style.width = porcentagem + '%';
            tempoTexto.textContent = Math.ceil(tempoRestante) + 's';
            
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
            momentoClique = tempoTotal - tempoRestante;
            clearInterval(temporizadorInterval);
            
            document.querySelectorAll('.btn-alternativa').forEach(btn => btn.disabled = true);
            
            if (alternativaEscolhida > 0) {
                document.querySelector(`[data-alternativa="${alternativaEscolhida}"]`).classList.add('selecionada');
            }

            try {
                const formData = new FormData();
                formData.append('acao', 'responder');
                formData.append('codigo_sala', codigoSala);
                formData.append('id_jogador', idJogador);
                formData.append('alternativa', alternativaEscolhida);
                formData.append('tempo_clique', Math.floor(momentoClique));

                await fetch('../utils/game_logic.php', {
                    method: 'POST',
                    body: formData
                });
                
                aguardarFimTempo();
            } catch (error) {
                console.error('Erro:', error);
            }
        }

        function aguardarFimTempo() {
            const checkInterval = setInterval(() => {
                if (tempoRestante <= 0) {
                    clearInterval(checkInterval);
                    mostrarResultado();
                }
            }, 100);
        }

        async function mostrarResultado() {
            try {
                const formData = new FormData();
                formData.append('acao', 'verificar_resultado');
                formData.append('codigo_sala', codigoSala);
                formData.append('id_jogador', idJogador);

                const response = await fetch('../utils/game_logic.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                if (data.status === 'ok') mostrarFeedback(data);
            } catch (error) {
                console.error('Erro:', error);
            }
        }

        function mostrarFeedback(data) {
            const feedbackContainer = document.getElementById('feedbackContainer');
            const feedbackTitulo = document.getElementById('feedbackTitulo');
            const feedbackTexto = document.getElementById('feedbackTexto');

            if (data.resposta_correta > 0) {
                document.querySelector(`[data-alternativa="${data.resposta_correta}"]`).classList.add('correta');
            }

            if (!data.acertou && data.alternativa_escolhida > 0) {
                document.querySelector(`[data-alternativa="${data.alternativa_escolhida}"]`).classList.add('incorreta');
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
            setTimeout(verificarProximaPergunta, 2000);
        }

        async function verificarProximaPergunta() {
            try {
                const formData = new FormData();
                formData.append('acao', 'carregar_pergunta');
                formData.append('codigo_sala', codigoSala);
                formData.append('id_jogador', idJogador);

                const response = await fetch('../utils/game_logic.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.status === 'ok' && data.pergunta.numero != perguntaAtualData.numero) {
                    window.location.reload();
                } else if (data.status === 'fim') {
                    window.location.href = 'ranking.php?codigo=' + codigoSala;
                } else {
                    setTimeout(verificarProximaPergunta, 2000);
                }
            } catch (error) {
                console.error('Erro:', error);
                setTimeout(verificarProximaPergunta, 2000);
            }
        }

        carregarPergunta();
    </script>
</body>
</html>