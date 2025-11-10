<?php
session_start();
include "../db/conexao.php";

$codigo = $_GET['codigo'] ?? '';

if (empty($codigo)) {
    die('<script>alert("Código da sala não encontrado"); window.location.href="../index.php";</script>');
}

$stmt = $conn->prepare("SELECT s.*, c.nome_categoria FROM salas s JOIN categorias c ON s.id_categoria = c.id_categoria WHERE s.codigo_sala = ?");
$stmt->bind_param("s", $codigo);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die('<script>alert("Sala não encontrada"); window.location.href="../index.php";</script>');
}

$sala = $result->fetch_assoc();

$id_jogador = $_SESSION['id_jogador'] ?? 0;

if ($id_jogador == 0) {
    die('<script>alert("Você não está nesta sala"); window.location.href="../index.php";</script>');
}

$stmt = $conn->prepare("SELECT nome, is_host FROM jogadores WHERE id_jogador = ? AND id_sala = ?");
$stmt->bind_param("ii", $id_jogador, $sala['id_sala']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die('<script>alert("Você não está nesta sala"); window.location.href="../index.php";</script>');
}

$jogador_atual = $result->fetch_assoc();
$eh_host = ($jogador_atual['is_host'] == 1);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lobby - <?= $codigo ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../public/style/lobby.css">
        <link rel="icon" href="../public/img/icon.png">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1 class="codigo-sala">Sala: <?= $codigo ?></h1>
            <p class="info">
                <?= $sala['nome_categoria'] ?> • 
                <?= $sala['rodadas'] ?> rodadas • 
                <?= $sala['tempo_resposta'] ?> segundos
            </p>
            
            <?php if ($eh_host): ?>
                <span class="badge-host">👑 Você é o Host</span>
            <?php else: ?>
                <span class="badge-player">🎮 Aguardando host iniciar...</span>
            <?php endif; ?>
        </div>

        <div class="jogadores-box">
            <div class="jogadores-titulo">
                <i class="bi bi-people"></i> Jogadores
                <span class="contador" id="contador">0</span>
            </div>
            <div id="listaJogadores">
                <p style="text-align: center; color: #c9d0d1;">Carregando jogadores...</p>
            </div>
        </div>

        <div class="botoes">
            <?php if ($eh_host): ?>
                <button class="btn btn-iniciar" id="btnIniciar" disabled>
                    <i class="bi bi-play-circle"></i> Iniciar Jogo
                </button>
                <button class="btn btn-fechar" id="btnFechar">
                    <i class="bi bi-x-circle"></i> Fechar Sala
                </button>
            <?php else: ?>
                <button class="btn btn-sair" id="btnSair">
                    <i class="bi bi-box-arrow-left"></i> Sair da Sala
                </button>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const codigoSala = "<?= $codigo ?>";
        const ehHost = <?= $eh_host ? 'true' : 'false' ?>;
        const idJogador = <?= $id_jogador ?>;

        async function carregarJogadores() {
            try {
                const formData = new FormData();
                formData.append('acao', 'listar_jogadores');
                formData.append('codigo_sala', codigoSala);

                const response = await fetch('../utils/lobby_actions.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.status === 'ok') {
                    mostrarJogadores(data.jogadores);
                }
            } catch (error) {
                console.error('Erro ao carregar jogadores:', error);
            }
        }

        function mostrarJogadores(jogadores) {
            const lista = document.getElementById('listaJogadores');
            const contador = document.getElementById('contador');
            
            if (!jogadores || jogadores.length === 0) {
                lista.innerHTML = '<p style="text-align: center; color: #c9d0d1;">Nenhum jogador na sala</p>';
                contador.textContent = '0';
                return;
            }

            let html = '';
            jogadores.forEach(jogador => {
                const ehEu = (jogador.id_jogador == idJogador);
                const ehHostJogador = (jogador.is_host == 1);
                
                html += `
                    <div class="jogador">
                        ${ehHostJogador ? '👑' : '🎮'} ${jogador.nome}
                        ${ehEu ? '<span class="badge-vc">Você</span>' : ''}
                    </div>
                `;
            });

            lista.innerHTML = html;
            contador.textContent = jogadores.length;

            if (ehHost) {
                document.getElementById('btnIniciar').disabled = jogadores.length < 2;
            }
        }

        async function iniciarJogo() {
            const result = await Swal.fire({
                title: 'Iniciar jogo?',
                text: 'Todos os jogadores serão redirecionados',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#33a4e6',
                cancelButtonColor: '#ff66bc',
                confirmButtonText: 'Sim, iniciar!',
                cancelButtonText: 'Cancelar',
                background: '#1f1e1e',
                color: 'white'
            });

            if (result.isConfirmed) {
                try {
                    const formData = new FormData();
                    formData.append('acao', 'iniciar_jogo');
                    formData.append('codigo_sala', codigoSala);

                    const response = await fetch('../utils/lobby_actions.php', {
                        method: 'POST',
                        body: formData
                    });
                    
                    const data = await response.json();
                    
                    if (data.status === 'ok') {
                        Swal.fire({
                            title: 'Jogo iniciado!',
                            icon: 'success',
                            timer: 1500,
                            showConfirmButton: false,
                            background: '#1f1e1e',
                            color: 'white'
                        }).then(() => {
                            window.location.href = `game.php?codigo=${codigoSala}`;
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Erro',
                            text: data.mensagem,
                            background: '#1f1e1e',
                            color: 'white'
                        });
                    }
                } catch (error) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Erro',
                        text: 'Não foi possível iniciar o jogo',
                        background: '#1f1e1e',
                        color: 'white'
                    });
                }
            }
        }

        async function fecharSala() {
            const result = await Swal.fire({
                title: 'Fechar sala?',
                text: 'Todos os jogadores serão desconectados',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ff66bc',
                cancelButtonColor: '#33a4e6',
                confirmButtonText: 'Sim, fechar!',
                cancelButtonText: 'Cancelar',
                background: '#1f1e1e',
                color: 'white'
            });

            if (result.isConfirmed) {
                try {
                    const formData = new FormData();
                    formData.append('acao', 'fechar_sala');
                    formData.append('codigo_sala', codigoSala);

                    const response = await fetch('../utils/lobby_actions.php', {
                        method: 'POST',
                        body: formData
                    });
                    
                    const data = await response.json();
                    
                    if (data.status === 'ok') {
                        window.location.href = '../index.php';
                    }
                } catch (error) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Erro',
                        text: 'Não foi possível fechar a sala',
                        background: '#1f1e1e',
                        color: 'white'
                    });
                }
            }
        }

        async function sairSala() {
            const result = await Swal.fire({
                title: 'Sair da sala?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#33a4e6',
                cancelButtonColor: '#ff66bc',
                confirmButtonText: 'Sim, sair!',
                cancelButtonText: 'Cancelar',
                background: '#1f1e1e',
                color: 'white'
            });

            if (result.isConfirmed) {
                try {
                    const formData = new FormData();
                    formData.append('acao', 'sair_sala');
                    formData.append('codigo_sala', codigoSala);

                    const response = await fetch('../utils/lobby_actions.php', {
                        method: 'POST',
                        body: formData
                    });
                    
                    const data = await response.json();
                    
                    if (data.status === 'ok') {
                        window.location.href = '../index.php';
                    }
                } catch (error) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Erro',
                        text: 'Não foi possível sair da sala',
                        background: '#1f1e1e',
                        color: 'white'
                    });
                }
            }
        }

        async function verificarStatus() {
            if (ehHost) return; 

            try {
                const formData = new FormData();
                formData.append('acao', 'verificar_status');
                formData.append('codigo_sala', codigoSala);

                const response = await fetch('../utils/lobby_actions.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.status === 'iniciada') {
                    window.location.href = `game.php?codigo=${codigoSala}`;
                }
            } catch (error) {
                console.error('Erro ao verificar status:', error);
            }
        }

        if (ehHost) {
            document.getElementById('btnIniciar').onclick = iniciarJogo;
            document.getElementById('btnFechar').onclick = fecharSala;
        } else {
            document.getElementById('btnSair').onclick = sairSala;
        }

        setInterval(carregarJogadores, 2000);
        
        if (!ehHost) {
            setInterval(verificarStatus, 2000);
        }

        carregarJogadores();
    </script>
</body>
</html>