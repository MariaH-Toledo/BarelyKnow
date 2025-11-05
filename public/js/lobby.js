class Lobby {
    constructor() {
        this.jogadores = [];
        this.init();
    }

    async init() {
        console.log('Lobby iniciado');
        this.iniciarPolling();
        this.configurarBotoes();
        await this.carregarJogadores();
    }

    iniciarPolling() {
        setInterval(() => {
            this.carregarJogadores();
        }, 2000);
    }

    async carregarJogadores() {
        try {
            console.log('🔄 Buscando jogadores...');
            const resultado = await this.ajax('listar_jogadores');
            console.log('📦 Resposta:', resultado);
            
            if (resultado.status === 'ok') {
                this.jogadores = resultado.jogadores || [];
                console.log(`👥 ${this.jogadores.length} jogadores carregados`);
                this.mostrarJogadores(this.jogadores);
                this.atualizarContador(this.jogadores.length);
                
                if (window.ehHost) {
                    this.atualizarBotaoIniciar(this.jogadores.length);
                }
            } else {
                console.error('❌ Erro do servidor:', resultado.mensagem);
                this.mostrarJogadores([]);
            }
        } catch (error) {
            console.error('❌ Erro ao carregar jogadores:', error);
            this.mostrarJogadores([]);
        }
    }

    mostrarJogadores(jogadores) {
        const container = document.getElementById('listaJogadores');
        if (!container) {
            console.error('Elemento listaJogadores não encontrado!');
            return;
        }

        // Se não há jogadores ou array vazio
        if (!jogadores || jogadores.length === 0) {
            container.innerHTML = '<div style="color: var(--cor-branco2); text-align: center; padding: 20px;">Nenhum jogador conectado</div>';
            return;
        }

        let html = '';
        jogadores.forEach(jogador => {
            const ehEu = (jogador.id_jogador == window.idJogador);
            const ehHost = (jogador.is_host == 1);

            html += `
                <div class="jogador-item">
                    <div class="jogador-nome">
                        ${ehHost ? '👑' : '👤'} ${jogador.nome}
                        ${ehEu ? '<span class="badge-vc">Você</span>' : ''}
                        ${ehHost ? '<span class="badge-host">Host</span>' : ''}
                    </div>
                </div>
            `;
        });

        container.innerHTML = html;
    }

    async iniciarJogo() {
        if (this.jogadores.length < 2) {
            alert('Mínimo de 2 jogadores!');
            return;
        }

        if (confirm('Iniciar jogo?')) {
            try {
                const resultado = await this.ajax('iniciar_jogo');
                if (resultado.status === 'ok') {
                    window.location.href = `game.php?codigo=${window.codigoSala}`;
                } else {
                    alert(resultado.mensagem);
                }
            } catch (error) {
                alert('Erro ao iniciar jogo');
            }
        }
    }

    async sairSala() {
        if (confirm('Sair da sala?')) {
            try {
                const resultado = await this.ajax('sair_sala');
                if (resultado.status === 'ok') {
                    window.location.href = '../index.php';
                }
            } catch (error) {
                alert('Erro ao sair');
            }
        }
    }

    async ajax(acao) {
        const formData = new FormData();
        formData.append('acao', acao);
        formData.append('codigo', window.codigoSala);

        const response = await fetch('../utils/lobby_actions.php', {
            method: 'POST',
            body: formData
        });
        
        return await response.json();
    }

    configurarBotoes() {
        if (window.ehHost) {
            const btnIniciar = document.getElementById('btnIniciar');
            const btnSair = document.getElementById('btnSair');
            
            if (btnIniciar) btnIniciar.onclick = () => this.iniciarJogo();
            if (btnSair) btnSair.onclick = () => this.sairSala();
        }
    }

    atualizarContador(total) {
        const contador = document.getElementById('contadorJogadores');
        if (contador) contador.textContent = total;
    }

    atualizarBotaoIniciar(total) {
        const btnIniciar = document.getElementById('btnIniciar');
        if (btnIniciar) {
            btnIniciar.disabled = total < 2;
        }
    }
}

// Inicializar
document.addEventListener('DOMContentLoaded', () => {
    window.lobby = new Lobby();
});