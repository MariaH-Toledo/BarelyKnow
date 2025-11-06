class LobbySimples {
    constructor() {
        this.init();
    }

    async init() {
        console.log('🎮 Lobby iniciado!');
        this.configurarBotoes();
        await this.carregarJogadores();
        this.iniciarAtualizacao();
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

    async verificarStatusJogo() {
        if (ehHost) return;
        
        try {
            const formData = new FormData();
            formData.append('acao', 'verificar_status');
            formData.append('codigo', codigoSala);

            const response = await fetch('../utils/lobby_actions.php', {
                method: 'POST',
                body: formData
            });
            
            const resultado = await response.json();
            
            if (resultado.status === 'iniciada') {
                console.log('🎯 Jogo iniciado! Redirecionando...');
                window.location.href = `game.php?codigo=${codigoSala}`;
            }
        } catch (error) {
            console.error('Erro ao verificar status:', error);
        }
    }

    mostrarJogadores(jogadores) {
        console.log('Jogadores recebidos:', jogadores); // DEBUG
    
        const container = document.getElementById('listaJogadores');
        if (!container) return;

        if (!jogadores || jogadores.length === 0) {
            container.innerHTML = '<div style="color: #c9d0d1; text-align: center; padding: 20px;">😴 Nenhum jogador conectado</div>';
            return;
        }

        // REMOVER duplicatas no cliente também (segurança extra)
        const jogadoresUnicos = [];
        const nomesVistos = new Set();
        
        jogadores.forEach(jogador => {
            if (!nomesVistos.has(jogador.nome)) {
                nomesVistos.add(jogador.nome);
                jogadoresUnicos.push(jogador);
            }
        });

        let html = '';
        jogadoresUnicos.forEach(jogador => {
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
        
        document.getElementById('contadorJogadores').textContent = jogadoresUnicos.length;
        
        if (ehHost) {
            document.getElementById('btnIniciar').disabled = jogadoresUnicos.length < 2;
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

    // 🎯 CORRIGIR: Usar SweetAlert2 para pop-up
    async iniciarJogo() {
        const resultado = await Swal.fire({
            title: 'Iniciar jogo?',
            text: 'Todos os jogadores entrarão no jogo',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: 'var(--cor-azul)',
            cancelButtonColor: 'var(--cor-rosa)',
            confirmButtonText: 'Sim, iniciar!',
            cancelButtonText: 'Cancelar',
            background: 'var(--cor-fundo2)',
            color: 'var(--cor-branco)'
        });

        if (resultado.isConfirmed) {
            try {
                const formData = new FormData();
                formData.append('acao', 'iniciar_jogo');
                formData.append('codigo', codigoSala);

                const response = await fetch('../utils/lobby_actions.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.status === 'ok') {
                    Swal.fire({
                        title: 'Jogo iniciado!',
                        text: 'Redirecionando...',
                        icon: 'success',
                        timer: 1500,
                        showConfirmButton: false,
                        background: 'var(--cor-fundo2)',
                        color: 'var(--cor-branco)'
                    }).then(() => {
                        window.location.href = `game.php?codigo=${codigoSala}`;
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Erro',
                        text: data.mensagem,
                        background: 'var(--cor-fundo2)',
                        color: 'var(--cor-branco)'
                    });
                }
            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Erro',
                    text: 'Não foi possível iniciar o jogo',
                    background: 'var(--cor-fundo2)',
                    color: 'var(--cor-branco)'
                });
            }
        }
    }

    async fecharSala() {
        const resultado = await Swal.fire({
            title: 'Fechar sala?',
            text: 'Todos os jogadores serão desconectados',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: 'var(--cor-rosa)',
            cancelButtonColor: 'var(--cor-azul)',
            confirmButtonText: 'Sim, fechar!',
            cancelButtonText: 'Cancelar',
            background: 'var(--cor-fundo2)',
            color: 'var(--cor-branco)'
        });

        if (resultado.isConfirmed) {
            try {
                const formData = new FormData();
                formData.append('acao', 'fechar_sala');
                formData.append('codigo', codigoSala);

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
                    background: 'var(--cor-fundo2)',
                    color: 'var(--cor-branco)'
                });
            }
        }
    }

    async sairSala() {
        const resultado = await Swal.fire({
            title: 'Sair da sala?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: 'var(--cor-azul)',
            cancelButtonColor: 'var(--cor-rosa)',
            confirmButtonText: 'Sim, sair!',
            cancelButtonText: 'Cancelar',
            background: 'var(--cor-fundo2)',
            color: 'var(--cor-branco)'
        });

        if (resultado.isConfirmed) {
            try {
                const formData = new FormData();
                formData.append('acao', 'sair_sala');
                formData.append('codigo', codigoSala);

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
                    background: 'var(--cor-fundo2)',
                    color: 'var(--cor-branco)'
                });
            }
        }
    }

    iniciarAtualizacao() {
        // Atualizar lista de jogadores
        setInterval(() => {
            this.carregarJogadores();
        }, 3000);
        
        // 🎯 ADICIONAR: Verificar status do jogo (apenas não-hosts)
        if (!ehHost) {
            setInterval(() => {
                this.verificarStatusJogo();
            }, 2000);
        }
    }
}