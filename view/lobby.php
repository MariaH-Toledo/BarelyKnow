<?php
session_start();
include "../db/conexao.php";

$codigo = isset($_GET['codigo']) ? trim($_GET['codigo']) : '';

if (empty($codigo)) {
    die('
        <!DOCTYPE html>
        <html>
        <head>
            <title>Erro</title>
            <style>
                body { background: #121212; color: white; font-family: Arial; text-align: center; padding: 50px; }
                .erro { color: #ff4444; font-size: 24px; margin: 20px; }
                .btn { background: #33a4e6; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; }
            </style>
        </head>
        <body>
            <div class="erro">❌ Error: Código da sala não encontrado</div>
            <p><a href="../index.php" class="btn">Voltar para Início</a></p>
        </body>
        </html>
    ');
}

$stmt = $conn->prepare("SELECT s.*, c.nome_categoria FROM salas s JOIN categorias c ON s.id_categoria = c.id_categoria WHERE s.codigo_sala = ?");
$stmt->bind_param("s", $codigo);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die('
        <!DOCTYPE html>
        <html>
        <head>
            <title>Erro</title>
            <style>
                body { background: #121212; color: white; font-family: Arial; text-align: center; padding: 50px; }
                .erro { color: #ff4444; font-size: 24px; margin: 20px; }
                .btn { background: #33a4e6; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; }
            </style>
        </head>
        <body>
            <div class="erro">❌ Sala não encontrada!</div>
            <p><a href="../index.php" class="btn">Voltar para Início</a></p>
        </body>
        </html>
    ');
}

$sala = $result->fetch_assoc();

$id_jogador = $_SESSION['id_jogador'] ?? 0;
$stmt_jogador = $conn->prepare("SELECT id_jogador, is_host FROM jogadores WHERE id_sala = ? AND id_jogador = ?");
$stmt_jogador->bind_param("ii", $sala['id_sala'], $id_jogador);
$stmt_jogador->execute();
$result_jogador = $stmt_jogador->get_result();

if ($result_jogador->num_rows === 0) {
    die('
        <!DOCTYPE html>
        <html>
        <head>
            <title>Erro</title>
            <style>
                body { background: #121212; color: white; font-family: Arial; text-align: center; padding: 50px; }
                .erro { color: #ff4444; font-size: 24px; margin: 20px; }
                .btn { background: #33a4e6; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; }
            </style>
        </head>
        <body>
            <div class="erro">❌ Você não está nesta sala!</div>
            <p><a href="../index.php" class="btn">Voltar para Início</a></p>
        </body>
        </html>
    ');
}

$jogador = $result_jogador->fetch_assoc();
$ehHost = ($jogador['is_host'] == 1);
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
<body>
    <div class="lobby-header">
        <h1 class="lobby-codigo">Sala: <?= $codigo ?></h1>
        <p class="lobby-info">
            <?= $sala['nome_categoria'] ?> • 
            <?= $sala['rodadas'] ?> rodadas • 
            <?= $sala['tempo_resposta'] ?> segundos por pergunta
        </p>
        
        <?php if ($ehHost): ?>
            <p style="color: #fdc83a;">
                <i class="bi bi-star-fill"></i> Você é o Host
            </p>
        <?php else: ?>
            <p style="color: #33a4e6;">
                <i class="bi bi-person"></i> Aguardando host iniciar...
            </p>
        <?php endif; ?>
    </div>

    <div class="jogadores-section">
        <div class="jogadores-titulo">
            <i class="bi bi-people"></i> Jogadores
            <span class="contador-jogadores" id="contadorJogadores">0</span>
        </div>
        <div id="listaJogadores" class="lista-jogadores">
            <div style="color: #c9d0d1; text-align: center;">
                ✅ Lobby carregado! Buscando jogadores...
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

    <script>
        const codigoSala = "<?= $codigo ?>";
        const ehHost = <?= $ehHost ? 'true' : 'false'; ?>;
        const idJogador = <?= $jogador['id_jogador']; ?>;

        class LobbySimples {
            constructor() {
                this.init();
            }

            async init() {
                console.log('🎮 Lobby iniciado!');
                this.configurarBotoes();
                await this.carregarJogadores();
                setInterval(() => this.carregarJogadores(), 3000);
            }

            async carregarJogadores() {
                try {
                    const formData = new FormData();
                    formData.append('acao', 'listar_jogadores');
                    formData.append('codigo', codigoSala);

                    const response = await fetch('../utils/lobby_actions.php', {
                        method: 'POST',
                        body: formData
                    });
                    
                    const resultado = await response.json();
                    
                    if (resultado.status === 'ok') {
                        this.mostrarJogadores(resultado.jogadores);
                    }
                } catch (error) {
                    console.error('Erro:', error);
                }
            }

            mostrarJogadores(jogadores) {
                const container = document.getElementById('listaJogadores');
                if (!container) return;

                if (!jogadores || jogadores.length === 0) {
                    container.innerHTML = '<div style="color: #c9d0d1; text-align: center; padding: 20px;">😴 Nenhum jogador conectado</div>';
                    return;
                }

                let html = '';
                jogadores.forEach(jogador => {
                    const ehEu = (jogador.id_jogador == idJogador);
                    const ehHost = (jogador.is_host == 1);

                    html += `
                        <div class="jogador-item">
                            <div class="jogador-nome">
                                ${ehHost ? '👑' : '👤'} ${jogador.nome}
                                ${ehEu ? '<span class="badge-vc">Você</span>' : ''}
                            </div>
                        </div>
                    `;
                });

                container.innerHTML = html;
                
                document.getElementById('contadorJogadores').textContent = jogadores.length;
                
                if (ehHost) {
                    document.getElementById('btnIniciar').disabled = jogadores.length < 2;
                }
            }

            configurarBotoes() {
                if (ehHost) {
                    document.getElementById('btnIniciar').onclick = () => this.iniciarJogo();
                    document.getElementById('btnFechar').onclick = () => this.fecharSala();
                } else {
                    document.getElementById('btnSair').onclick = () => this.sairSala();
                }
            }

            async iniciarJogo() {
                if (confirm('Iniciar o jogo para todos os jogadores?')) {
                    try {
                        const formData = new FormData();
                        formData.append('acao', 'iniciar_jogo');
                        formData.append('codigo', codigoSala);

                        const response = await fetch('../utils/lobby_actions.php', {
                            method: 'POST',
                            body: formData
                        });
                        
                        const resultado = await response.json();
                        
                        if (resultado.status === 'ok') {
                            window.location.href = `game.php?codigo=${codigoSala}`;
                        } else {
                            alert('Erro: ' + resultado.mensagem);
                        }
                    } catch (error) {
                        alert('Erro ao iniciar jogo');
                    }
                }
            }

            async fecharSala() {
                if (confirm('Fechar a sala? Todos serão desconectados.')) {
                    try {
                        const formData = new FormData();
                        formData.append('acao', 'fechar_sala');
                        formData.append('codigo', codigoSala);

                        const response = await fetch('../utils/lobby_actions.php', {
                            method: 'POST',
                            body: formData
                        });
                        
                        const resultado = await response.json();
                        
                        if (resultado.status === 'ok') {
                            window.location.href = '../index.php';
                        }
                    } catch (error) {
                        alert('Erro ao fechar sala');
                    }
                }
            }

            async sairSala() {
                if (confirm('Sair da sala?')) {
                    try {
                        const formData = new FormData();
                        formData.append('acao', 'sair_sala');
                        formData.append('codigo', codigoSala);

                        const response = await fetch('../utils/lobby_actions.php', {
                            method: 'POST',
                            body: formData
                        });
                        
                        const resultado = await response.json();
                        
                        if (resultado.status === 'ok') {
                            window.location.href = '../index.php';
                        }
                    } catch (error) {
                        alert('Erro ao sair');
                    }
                }
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            new LobbySimples();
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</body>
</html>