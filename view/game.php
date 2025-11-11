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
        let respostaEscolhida = null;
        let intervaloTempo = null;
        let intervaloBuscar = null;
        let tempoRestante = 0;

        // INICIAR: Buscar pergunta atual ou aguardar
        async function iniciar() {
            if (ehHost) {
                // Host precisa iniciar a primeira pergunta
                await carregarProximaPergunta();
            } else {
                // Jogadores aguardam host iniciar
                aguardarPergunta();
            }
        }

        // HOST CARREGA PRÓXIMA PERGUNTA
        async function carregarProximaPergunta() {
            try {
                const formData = new FormData();
                formData.append('acao', 'iniciar_proxima');
                formData.append('codigo_sala', codigoSala);
                formData.append('id_jogador', idJogador);
            
                const response = await fetch('../utils/game_logic.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.status === 'ok') {
                    // Pergunta iniciada com sucesso
                    setTimeout(() => buscarPergunta(), 500);
                } else if (data.status === 'fim_jogo') {
                    // Acabaram as perguntas
                    window.location.href = `ranking.php?codigo=${codigoSala}`;
                } else {
                    console.error('Erro ao iniciar pergunta:', data.mensagem);
                }
            } catch (error) {
                console.error('Erro ao carregar próxima:', error);
            }
        }

        // BUSCAR PERGUNTA ATUAL
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
                    // Continuar aguardando
                    if (!intervaloBuscar) {
                        aguardarPergunta();
                    }
                }
            } catch (error) {
                console.error('Erro ao buscar pergunta:', error);
            }
        }

        // AGUARDAR HOST INICIAR PERGUNTA
        function aguardarPergunta() {
            document.getElementById('perguntaTexto').textContent = 'Aguardando host iniciar a pergunta...';
            document.getElementById('perguntaAtual').textContent = 'Aguardando...';

            const botoes = document.querySelectorAll('.btn-alternativa');
            botoes.forEach(btn => btn.disabled = true);

            // Verificar a cada 2 segundos
            if (!intervaloBuscar) {
                intervaloBuscar = setInterval(() => buscarPergunta(), 2000);
            }
        }

        // MOSTRAR PERGUNTA NA TELA
        function mostrarPergunta(data) {
            perguntaAtual = data;
            respostaEscolhida = null;

            // Esconder feedback se estiver visível
            document.getElementById('feedbackContainer').style.display = 'none';

            // Atualizar cabeçalho
            document.getElementById('perguntaAtual').textContent = 
                `Pergunta ${data.rodada_atual} de ${data.total_rodadas}`;

            // Mostrar pergunta
            document.getElementById('perguntaTexto').textContent = data.pergunta;

            // Mostrar alternativas
            for (let i = 0; i < 4; i++) {
                document.getElementById(`alt${i + 1}`).textContent = data.alternativas[i];
            }

            // Resetar botões
            const botoes = document.querySelectorAll('.btn-alternativa');
            botoes.forEach(btn => {
                btn.disabled = data.ja_respondeu;
                btn.classList.remove('correta', 'incorreta', 'selecionada');
            });

            // Se já respondeu, marcar como respondida
            if (data.ja_respondeu) {
                respostaEscolhida = -1;
            }

            // Iniciar timer
            tempoRestante = data.tempo_restante;
            iniciarTimer(data.tempo_total, data.tempo_restante);
        }

        // CONTROLAR TIMER
        function iniciarTimer(tempoTotal, tempoInicial) {
            const tempoBar = document.getElementById('tempoBar');
            const tempoTexto = document.getElementById('tempoTexto');

            tempoRestante = tempoInicial;

            // Limpar intervalo anterior se existir
            if (intervaloTempo) {
                clearInterval(intervaloTempo);
            }

            // Atualizar display inicial
            atualizarDisplay();

            // Atualizar a cada segundo
            intervaloTempo = setInterval(() => {
                tempoRestante--;

                if (tempoRestante <= 0) {
                    tempoRestante = 0;
                    clearInterval(intervaloTempo);
                    intervaloTempo = null;
                    verificarSeAcabou();
                }

                atualizarDisplay();
            }, 1000);

            function atualizarDisplay() {
                const porcentagem = (tempoRestante / tempoTotal) * 100;
                tempoBar.style.width = `${porcentagem}%`;
                tempoTexto.textContent = `${tempoRestante}s`;

                // Mudar cor quando estiver acabando
                if (tempoRestante <= 5) {
                    tempoBar.style.backgroundColor = '#ff66bc';
                } else if (tempoRestante <= 10) {
                    tempoBar.style.backgroundColor = '#fdc83a';
                } else {
                    tempoBar.style.backgroundColor = '#72d9eb';
                }
            }
        }

        // JOGADOR ESCOLHE RESPOSTA
        async function responder(alternativa) {
            // Não permitir responder duas vezes
            if (respostaEscolhida !== null) return;

            // Não permitir responder se tempo acabou
            if (tempoRestante <= 0) return;

            respostaEscolhida = alternativa;

            // Marcar botão escolhido
            const botaoEscolhido = document.querySelector(`[data-alternativa="${alternativa}"]`);
            botaoEscolhido.classList.add('selecionada');

            // Desabilitar todos os botões
            const botoes = document.querySelectorAll('.btn-alternativa');
            botoes.forEach(btn => btn.disabled = true);

            // Enviar resposta para o servidor
            try {
                const formData = new FormData();
                formData.append('acao', 'enviar_resposta');
                formData.append('codigo_sala', codigoSala);
                formData.append('id_jogador', idJogador);
                formData.append('alternativa', alternativa);
            
                const response = await fetch('../utils/game_logic.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.status !== 'ok') {
                    console.error('Erro ao enviar resposta:', data.mensagem);
                }
            } catch (error) {
                console.error('Erro ao enviar resposta:', error);
            }
        }

        // VERIFICAR SE TEMPO ACABOU
        async function verificarSeAcabou() {
            try {
                const formData = new FormData();
                formData.append('acao', 'verificar_tempo');
                formData.append('codigo_sala', codigoSala);
            
                const response = await fetch('../utils/game_logic.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.status === 'tempo_acabou') {
                    mostrarFeedback(data.resposta_correta);
                }
            } catch (error) {
                console.error('Erro ao verificar tempo:', error);
            }
        }

        // MOSTRAR FEEDBACK (ACERTOU OU ERROU)
        function mostrarFeedback(respostaCorreta) {
            // Parar timer
            if (intervaloTempo) {
                clearInterval(intervaloTempo);
                intervaloTempo = null;
            }

            // Marcar alternativa correta
            const botoes = document.querySelectorAll('.btn-alternativa');
            botoes.forEach((btn, index) => {
                const numeroAlternativa = index + 1;

                btn.disabled = true;

                // Marcar a correta
                if (numeroAlternativa === respostaCorreta) {
                    btn.classList.add('correta');
                }

                // Marcar a escolhida errada (se errou)
                if (respostaEscolhida && numeroAlternativa === respostaEscolhida && respostaEscolhida !== respostaCorreta) {
                    btn.classList.add('incorreta');
                }
            });

            // Preparar mensagem de feedback
            const feedbackContainer = document.getElementById('feedbackContainer');
            const feedbackTitulo = document.getElementById('feedbackTitulo');
            const feedbackMensagem = document.getElementById('feedbackMensagem');
            const btnProxima = document.getElementById('btnProxima');

            if (respostaEscolhida === null) {
                // Não respondeu
                feedbackTitulo.textContent = '⏰ Tempo esgotado!';
                feedbackMensagem.textContent = 'Você não respondeu a tempo.';
            } else if (respostaEscolhida === respostaCorreta) {
                // Acertou
                feedbackTitulo.textContent = '🎉 Correto!';
                feedbackMensagem.textContent = 'Parabéns! Você acertou!';
            } else {
                // Errou
                feedbackTitulo.textContent = '❌ Incorreto!';
                feedbackMensagem.textContent = 'Não foi dessa vez. Continue tentando!';
            }

            // Mostrar ou esconder botão de próxima
            if (ehHost) {
                btnProxima.style.display = 'inline-block';
            } else {
                btnProxima.style.display = 'none';
                feedbackMensagem.innerHTML += '<br><br><small>Aguardando host avançar...</small>';
                // Jogadores ficam aguardando host avançar
                intervaloBuscar = setInterval(() => buscarPergunta(), 2000);
            }

            // Mostrar feedback
            feedbackContainer.style.display = 'flex';
        }

        // HOST AVANÇA PARA PRÓXIMA PERGUNTA
        async function proximaPergunta() {
            if (!ehHost) return;

            document.getElementById('feedbackContainer').style.display = 'none';

            await carregarProximaPergunta();
        }

        iniciar();
    </script>
</body>
</html>