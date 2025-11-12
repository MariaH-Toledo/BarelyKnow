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
        let tentativasErro = 0;
        let ultimaPerguntaId = null;

        async function iniciar() {
            console.log('🎮 Iniciando jogo');
            console.log('👤 ID Jogador:', idJogador);
            console.log('👑 É Host:', ehHost);
            console.log('🚪 Sala:', codigoSala);
            
            await buscarPergunta();
        }

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
                console.log('📩 Buscar pergunta:', data);

                if (data.status === 'ok') {
                    tentativasErro = 0;
                    
                    const perguntaId = `${data.rodada_atual}-${data.pergunta}`;
                    
                    if (perguntaId !== ultimaPerguntaId) {
                        console.log('🆕 Nova pergunta detectada!');
                        ultimaPerguntaId = perguntaId;
                        
                        if (intervaloBuscar) {
                            clearInterval(intervaloBuscar);
                            intervaloBuscar = null;
                        }
                        
                        mostrarPergunta(data);
                        return true;
                    } else {
                        console.log('⏳ Mesma pergunta, aguardando...');
                        return false;
                    }
                    
                } else if (data.status === 'aguardando') {
                    console.log('⏳ Aguardando:', data.mensagem);
                    
                    if (ehHost && !perguntaAtual) {
                        console.log('👑 Host iniciando primeira pergunta...');
                        await carregarProximaPergunta();
                    } else {
                        aguardarPergunta();
                    }
                    return false;
                    
                } else if (data.status === 'fim_jogo') {
                    console.log('🏁 Jogo finalizado! Redirecionando...');
                    
                    if (intervaloTempo) clearInterval(intervaloTempo);
                    if (intervaloBuscar) clearInterval(intervaloBuscar);
                    
                    setTimeout(() => {
                        window.location.href = `ranking.php?codigo=${codigoSala}`;
                    }, 500);
                    return false;
                }
            } catch (error) {
                console.error('❌ Erro ao buscar pergunta:', error);
                tentativasErro++;
                
                if (tentativasErro >= 5) {
                    alert('Erro de conexão persistente. Recarregue a página.');
                    tentativasErro = 0;
                }
                return false;
            }
        }

        function aguardarPergunta() {
            document.getElementById('perguntaTexto').textContent = 'Aguardando host iniciar a pergunta...';
            document.getElementById('perguntaAtual').textContent = 'Aguardando...';

            const botoes = document.querySelectorAll('.btn-alternativa');
            botoes.forEach(btn => {
                btn.disabled = true;
                btn.classList.remove('correta', 'incorreta', 'selecionada');
            });

            for (let i = 1; i <= 4; i++) {
                document.getElementById(`alt${i}`).textContent = '-';
            }

            if (!intervaloBuscar) {
                console.log('⏳ Iniciando polling de perguntas (a cada 2s)');
                intervaloBuscar = setInterval(() => buscarPergunta(), 2000);
            }
        }

        async function carregarProximaPergunta() {
            if (!ehHost) {
                console.log('⚠️ Tentativa de carregar pergunta por não-host bloqueada');
                return;
            }

            try {
                console.log('👑 HOST: Carregando próxima pergunta...');
                
                const formData = new FormData();
                formData.append('acao', 'iniciar_proxima');
                formData.append('codigo_sala', codigoSala);
                formData.append('id_jogador', idJogador);
            
                const response = await fetch('../utils/game_logic.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();
                console.log('📨 Resposta iniciar_proxima:', data);

                if (data.status === 'ok') {
                    console.log('✅ Pergunta iniciada, aguardando 1s para buscar...');
                    setTimeout(() => buscarPergunta(), 1000);
                    
                } else if (data.status === 'fim_jogo') {
                    console.log('🏁 Fim do jogo detectado!');
                    
                    if (intervaloTempo) clearInterval(intervaloTempo);
                    if (intervaloBuscar) clearInterval(intervaloBuscar);
                    
                    setTimeout(() => {
                        window.location.href = `ranking.php?codigo=${codigoSala}`;
                    }, 500);
                    
                } else {
                    console.error('❌ Erro ao iniciar pergunta:', data.mensagem);
                    alert('Erro: ' + data.mensagem);
                }
            } catch (error) {
                console.error('❌ Erro ao carregar próxima:', error);
            }
        }

        function mostrarPergunta(data) {
            console.log('📝 Mostrando pergunta:', data);
            
            perguntaAtual = data;
            respostaEscolhida = null;

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

            if (data.ja_respondeu) {
                console.log('⚠️ Jogador já respondeu esta pergunta');
                respostaEscolhida = -1;
            }

            tempoRestante = data.tempo_restante;
            iniciarTimer(data.tempo_total, data.tempo_restante);
        }

        function iniciarTimer(tempoTotal, tempoInicial) {
            const tempoBar = document.getElementById('tempoBar');
            const tempoTexto = document.getElementById('tempoTexto');

            tempoRestante = tempoInicial;

            if (intervaloTempo) {
                clearInterval(intervaloTempo);
            }

            atualizarDisplay();

            intervaloTempo = setInterval(() => {
                tempoRestante--;

                if (tempoRestante <= 0) {
                    tempoRestante = 0;
                    clearInterval(intervaloTempo);
                    intervaloTempo = null;
                    console.log('⏰ Tempo esgotado!');
                    verificarSeAcabou();
                }

                atualizarDisplay();
            }, 1000);

            function atualizarDisplay() {
                const porcentagem = (tempoRestante / tempoTotal) * 100;
                tempoBar.style.width = `${porcentagem}%`;
                tempoTexto.textContent = `${tempoRestante}s`;

                if (tempoRestante <= 5) {
                    tempoBar.style.backgroundColor = '#ff66bc';
                } else if (tempoRestante <= 10) {
                    tempoBar.style.backgroundColor = '#fdc83a';
                } else {
                    tempoBar.style.backgroundColor = '#72d9eb';
                }
            }
        }

        async function responder(alternativa) {
            if (respostaEscolhida !== null) {
                console.log('⚠️ Já respondeu');
                return;
            }

            if (tempoRestante <= 0) {
                console.log('⚠️ Tempo esgotado');
                return;
            }

            console.log('✅ Resposta escolhida:', alternativa);
            respostaEscolhida = alternativa;

            const botaoEscolhido = document.querySelector(`[data-alternativa="${alternativa}"]`);
            botaoEscolhido.classList.add('selecionada');

            const botoes = document.querySelectorAll('.btn-alternativa');
            botoes.forEach(btn => btn.disabled = true);

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
                console.log('📨 Resposta enviada:', data);

                if (data.status !== 'ok') {
                    console.error('❌ Erro ao enviar:', data.mensagem);
                }
            } catch (error) {
                console.error('❌ Erro ao enviar resposta:', error);
            }
        }

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
                console.log('⏰ Verificar tempo:', data);

                if (data.status === 'tempo_acabou') {
                    mostrarFeedback(data.resposta_correta);
                }
            } catch (error) {
                console.error('❌ Erro ao verificar tempo:', error);
            }
        }

        function mostrarFeedback(respostaCorreta) {
            console.log('📊 Mostrando feedback. Correta:', respostaCorreta);
            
            if (intervaloTempo) {
                clearInterval(intervaloTempo);
                intervaloTempo = null;
            }

            const botoes = document.querySelectorAll('.btn-alternativa');
            botoes.forEach((btn, index) => {
                const numeroAlternativa = index + 1;
                btn.disabled = true;

                if (numeroAlternativa === respostaCorreta) {
                    btn.classList.add('correta');
                }

                if (respostaEscolhida && numeroAlternativa === respostaEscolhida && respostaEscolhida !== respostaCorreta) {
                    btn.classList.add('incorreta');
                }
            });

            const feedbackContainer = document.getElementById('feedbackContainer');
            const feedbackTitulo = document.getElementById('feedbackTitulo');
            const feedbackMensagem = document.getElementById('feedbackMensagem');
            const btnProxima = document.getElementById('btnProxima');

            if (respostaEscolhida === null) {
                feedbackTitulo.textContent = '⏰ Tempo esgotado!';
                feedbackMensagem.textContent = 'Você não respondeu a tempo.';
            } else if (respostaEscolhida === respostaCorreta) {
                feedbackTitulo.textContent = '🎉 Correto!';
                feedbackMensagem.textContent = 'Parabéns! Você acertou!';
            } else {
                feedbackTitulo.textContent = '❌ Incorreto!';
                feedbackMensagem.textContent = 'Não foi dessa vez. Continue tentando!';
            }

            if (ehHost) {
                btnProxima.style.display = 'inline-block';
                btnProxima.textContent = 'Próxima Pergunta';
                console.log('👑 HOST: Botão de próxima habilitado');
                
                if (intervaloBuscar) {
                    clearInterval(intervaloBuscar);
                    intervaloBuscar = null;
                }
            } else {
                btnProxima.style.display = 'none';
                feedbackMensagem.innerHTML += '<br><br><small>Aguardando host avançar...</small>';
                
                console.log('👤 NÃO-HOST: Iniciando polling para próxima pergunta');
                
                if (intervaloBuscar) {
                    clearInterval(intervaloBuscar);
                }
                
                perguntaAtual = null;
                
                intervaloBuscar = setInterval(async () => {
                    console.log('🔄 Polling: Buscando próxima pergunta...');
                    const encontrou = await buscarPergunta();
                    
                    if (encontrou) {
                        console.log('✅ Polling: Nova pergunta encontrada!');
                        if (intervaloBuscar) {
                            clearInterval(intervaloBuscar);
                            intervaloBuscar = null;
                        }
                    }
                }, 1500);
            }

            feedbackContainer.style.display = 'flex';
        }

        async function proximaPergunta() {
            if (!ehHost) {
                console.log('⚠️ Não-host tentou avançar pergunta');
                return;
            }

            console.log('👑 HOST: Avançando para próxima pergunta...');
            
            document.getElementById('feedbackContainer').style.display = 'none';

            if (intervaloBuscar) {
                clearInterval(intervaloBuscar);
                intervaloBuscar = null;
            }

            await carregarProximaPergunta();
        }

        async function verificarFimJogo() {
            try {
                const formData = new FormData();
                formData.append('acao', 'verificar_fim_jogo');
                formData.append('codigo_sala', codigoSala);
            
                const response = await fetch('../utils/game_logic.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.status === 'fim_jogo') {
                    console.log('🏁 Fim detectado via polling!');
                    
                    if (intervaloTempo) clearInterval(intervaloTempo);
                    if (intervaloBuscar) clearInterval(intervaloBuscar);
                    
                    window.location.href = `ranking.php?codigo=${codigoSala}`;
                }
            } catch (error) {
                console.error('❌ Erro ao verificar fim:', error);
            }
        }

        setInterval(verificarFimJogo, 10000);

        iniciar();
    </script>
</body>
</html>