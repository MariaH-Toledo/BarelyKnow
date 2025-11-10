<?php
session_start();
include "../db/conexao.php";

$codigo = $_GET['codigo'] ?? '';

if (empty($codigo)) {
    header("Location: ../index.php");
    exit;
}

$stmt = $conn->prepare("
    SELECT s.*, c.nome_categoria 
    FROM salas s 
    JOIN categorias c ON s.id_categoria = c.id_categoria 
    WHERE s.codigo_sala = ?
");
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

$stmt = $conn->prepare("SELECT is_host FROM jogadores WHERE id_jogador = ? AND id_sala = ?");
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
        <link rel="icon" href="../public/img/icon.png">

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
                <p class="pergunta-texto" id="perguntaTexto">Aguardando pergunta...</p>
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
                        <span class="alt-texto" id="alt1">-</span>
                    </button>
                </div>
                <div class="col-md-6">
                    <button class="btn-alternativa" data-alternativa="2" onclick="responder(2)">
                        <span class="alt-letra">B</span>
                        <span class="alt-texto" id="alt2">-</span>
                    </button>
                </div>
                <div class="col-md-6">
                    <button class="btn-alternativa" data-alternativa="3" onclick="responder(3)">
                        <span class="alt-letra">C</span>
                        <span class="alt-texto" id="alt3">-</span>
                    </button>
                </div>
                <div class="col-md-6">
                    <button class="btn-alternativa" data-alternativa="4" onclick="responder(4)">
                        <span class="alt-letra">D</span>
                        <span class="alt-texto" id="alt4">-</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="feedback-container" id="feedbackContainer" style="display: none;">
        <div class="feedback-box">
            <h3 id="feedbackTitulo">🎉 Correto!</h3>
            <p id="feedbackMensagem">Você ganhou <strong>850 pontos</strong>!</p>
            <button class="proxima-pergunta" id="btnProxima" onclick="proximaPergunta()">
                Próxima Pergunta
            </button>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const codigoSala = "<?= $codigo ?>";
        const idJogador = <?= $id_jogador ?>;
        const ehHost = <?= $eh_host ? 'true' : 'false' ?>;
        
        let perguntaAtual = null;
        let tempoInicio = null;
        let respostaEscolhida = null;
        let intervaloBuscar = null;
        let intervaloTempo = null;
        let aguardandoProxima = false;
        let tempoTotalPergunta = 0;

        async function buscarPergunta() {
            try {
                const formData = new FormData();
                formData.append('acao', 'buscar_pergunta');
                formData.append('codigo_sala', codigoSala);
                formData.append('id_jogador', idJogador);

                const response = await fetch('../utils/game_logic.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.status === 'ok') {
                    if (intervaloBuscar) {
                        clearInterval(intervaloBuscar);
                        intervaloBuscar = null;
                    }
                    mostrarPergunta(data);
                } else if (data.status === 'aguardando') {
                    document.getElementById('perguntaTexto').textContent = 'Aguardando host carregar a próxima pergunta...';
                    document.getElementById('perguntaAtual').textContent = 'Aguardando...';
                    
                    if (!intervaloBuscar) {
                        intervaloBuscar = setInterval(() => buscarPergunta(), 2000);
                    }
                } else if (data.status === 'fim_jogo') {
                    window.location.href = `ranking.php?codigo=${codigoSala}`;
                }
            } catch (error) {
                console.error('Erro ao buscar pergunta:', error);
            }
        }


        function mostrarPergunta(data) {
            perguntaAtual = data;
            respostaEscolhida = null;
            aguardandoProxima = false;
            tempoTotalPergunta = data.tempo_total;

            document.getElementById('feedbackContainer').style.display = 'none';

            document.getElementById('perguntaAtual').textContent = 
                `Pergunta ${data.rodada_atual} de ${data.total_rodadas}`;

            document.getElementById('perguntaTexto').textContent = data.pergunta;

            for (let i = 0; i < 4; i++) {
                document.getElementById(`alt${i + 1}`).textContent = data.alternativas[i];
            }

            const botoes = document.querySelectorAll('.btn-alternativa');
            botoes.forEach(btn => {
                btn.disabled = data.ja_respondeu;
                btn.classList.remove('correta', 'incorreta', 'selecionada');
            });

            if (data.ja_respondeu) respostaEscolhida = -1;

            const timestampInicio = data.timestamp_inicio * 1000;
            const timestampServidor = data.timestamp_servidor * 1000;
            const timestampCliente = Date.now();

            const diferencaRelogio = timestampCliente - timestampServidor;
            tempoInicio = timestampInicio + diferencaRelogio;

            // Ajustado: passa também o tempo restante
            atualizarTemporizador(data.tempo_total, data.tempo_restante);

            if (intervaloTempo) clearInterval(intervaloTempo);
            intervaloTempo = setInterval(() => verificarTempoAcabou(), 1000);
        }

        function atualizarTemporizador(tempoTotal, tempoRestanteInicial) {
            const tempoBar = document.getElementById('tempoBar');
            const tempoTexto = document.getElementById('tempoTexto');

            let tempoRestante = tempoRestanteInicial;

            // Limpa qualquer temporizador antigo
            if (intervaloTempo) clearInterval(intervaloTempo);

            tempoBar.style.width = "100%";
            tempoTexto.textContent = `${tempoRestante}s`;

            intervaloTempo = setInterval(() => {
                tempoRestante--;

                if (tempoRestante <= 0) {
                    tempoRestante = 0;
                    tempoBar.style.width = "0%";
                    tempoTexto.textContent = `0s`;
                    clearInterval(intervaloTempo);
                    verificarTempoAcabou();
                    return;
                }

                const porcentagem = (tempoRestante / tempoTotal) * 100;
                tempoBar.style.width = `${porcentagem}%`;
                tempoTexto.textContent = `${tempoRestante}s`;
            }, 1000);
        }

        async function responder(alternativa) {
            if (respostaEscolhida !== null) return;
            if (!perguntaAtual) return;
            
            respostaEscolhida = alternativa;
            
            const botaoEscolhido = document.querySelector(`[data-alternativa="${alternativa}"]`);
            botaoEscolhido.classList.add('selecionada');
            
            const botoes = document.querySelectorAll('.btn-alternativa');
            botoes.forEach(btn => btn.disabled = true);
            
            const tempoResposta = Math.floor((Date.now() - tempoInicio) / 1000);
            
            try {
                const formData = new FormData();
                formData.append('acao', 'enviar_resposta');
                formData.append('codigo_sala', codigoSala);
                formData.append('id_jogador', idJogador);
                formData.append('alternativa', alternativa);
                formData.append('tempo_resposta', tempoResposta);

                const response = await fetch('../utils/game_logic.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.status === 'ok') {
                }
            } catch (error) {
                console.error('Erro ao enviar resposta:', error);
            }
        }

        async function verificarTempoAcabou() {
            if (aguardandoProxima) return;
            
            try {
                const formData = new FormData();
                formData.append('acao', 'verificar_tempo');
                formData.append('codigo_sala', codigoSala);
                formData.append('id_jogador', idJogador);

                const response = await fetch('../utils/game_logic.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.status === 'tempo_acabou') {
                    clearInterval(intervaloTempo);
                    intervaloTempo = null;
                    mostrarResultado(data);
                }
            } catch (error) {
                console.error('Erro ao verificar tempo:', error);
            }
        }

        function mostrarResultado(data) {
            aguardandoProxima = true;
            
            const botoes = document.querySelectorAll('.btn-alternativa');
            botoes.forEach((btn, index) => {
                const numeroAlternativa = index + 1;
                
                btn.disabled = true;
                
                if (numeroAlternativa === data.resposta_correta) {
                    btn.classList.add('correta');
                } else if (numeroAlternativa === data.sua_resposta && !data.acertou) {
                    btn.classList.add('incorreta');
                }
            });
            
            const feedbackContainer = document.getElementById('feedbackContainer');
            const feedbackTitulo = document.getElementById('feedbackTitulo');
            const feedbackMensagem = document.getElementById('feedbackMensagem');
            const btnProxima = document.getElementById('btnProxima');
            
            if (data.acertou) {
                feedbackTitulo.textContent = '🎉 Correto!';
                feedbackMensagem.innerHTML = `Você ganhou <strong>${data.pontos} pontos</strong>!`;
            } else if (data.sua_resposta === 0) {
                feedbackTitulo.textContent = '⏰ Tempo esgotado!';
                feedbackMensagem.textContent = 'Você não respondeu a tempo.';
            } else {
                feedbackTitulo.textContent = '❌ Incorreto!';
                feedbackMensagem.textContent = 'Você não ganhou pontos desta vez.';
            }
            
            if (ehHost) {
                btnProxima.style.display = 'inline-block';
            } else {
                btnProxima.style.display = 'none';
                feedbackMensagem.innerHTML += '<br><small>Aguardando host avançar...</small>';
            }
            
            feedbackContainer.style.display = 'flex';
            
            if (!ehHost) {
                intervaloBuscar = setInterval(() => buscarPergunta(), 2000);
            }
        }

        async function proximaPergunta() {
            if (!ehHost) return;
            
            try {
                const formData = new FormData();
                formData.append('acao', 'proxima_pergunta');
                formData.append('codigo_sala', codigoSala);
                formData.append('id_jogador', idJogador);

                const response = await fetch('../utils/game_logic.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.status === 'ok') {
                    document.getElementById('feedbackContainer').style.display = 'none';
                    
                    setTimeout(() => buscarPergunta(), 500);
                } else if (data.status === 'fim_jogo') {
                    window.location.href = `ranking.php?codigo=${codigoSala}`;
                }
            } catch (error) {
                console.error('Erro ao avançar:', error);
            }
        }

        buscarPergunta();
    </script>
</body>
</html>