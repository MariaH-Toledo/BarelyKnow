class Lobby {
    constructor() {
        this.carregando = false;
        this.ehHost = window.ehHost;
        this.idJogador = window.idJogador; // Adicione esta linha
        
        console.log('🔧 Lobby iniciado:');
        console.log('  this.ehHost:', this.ehHost, '(tipo:', typeof this.ehHost, ')');
        console.log('  this.idJogador:', this.idJogador);
        console.log('  window.ehHost:', window.ehHost);
        console.log('  window.idJogador:', window.idJogador);
        
        this.iniciar();
    }

    async carregarJogadores() {
        if (this.carregando) return;
        this.carregando = true;

        try {
            const jogadores = await this.ajax('listar_jogadores');
            this.jogadores = jogadores; // Guarda os jogadores para usar em outros métodos
            this.mostrarJogadores(jogadores);
            
            this.atualizarContador(jogadores.length);
            
            // CORREÇÃO: usar this.ehHost em vez de ehHost
            if (this.ehHost) {
                const btnIniciar = document.getElementById('btnIniciar');
                btnIniciar.disabled = jogadores.length < 2;
                
                if (jogadores.length < 2) {
                    btnIniciar.title = `Mínimo 2 jogadores (${jogadores.length}/2)`;
                } else {
                    btnIniciar.title = 'Clique para iniciar o jogo';
                }
            }
        } catch (error) {
            console.error('Erro:', error);
        }

        this.carregando = false;
    }

    atualizarContador(total) {
        const contador = document.getElementById('contadorJogadores');
        if (contador) {
            contador.textContent = total;
        }
    }

    mostrarJogadores(jogadores) {
        const container = document.getElementById('listaJogadores');
    
        if (jogadores.length === 0) {
            container.innerHTML = '<div style="color: var(--cor-branco2); text-align: center;">Nenhum jogador</div>';
            return;
        }

        let html = '';
        jogadores.forEach(jogador => {
            const ehEu = (jogador.id_jogador == idJogador);
            const ehEsteHost = (jogador.is_host == 1);

            // DEBUG: Verificar o que está vindo do banco
            console.log(`👤 Jogador: ${jogador.nome}, ID: ${jogador.id_jogador}, is_host: ${jogador.is_host}, EhEu: ${ehEu}`);

            // CORREÇÃO: Host pode remover qualquer jogador que não seja ele mesmo
            const possoRemover = (this.ehHost && !ehEu);

            console.log(`🔧 Pode remover ${jogador.nome}: ${possoRemover} (Host: ${this.ehHost}, Não é eu: ${!ehEu})`);

            html += `
                <div class="jogador-item">
                    <div class="jogador-nome">
                        ${ehEsteHost ? '👑' : '👤'} ${jogador.nome}
                        ${ehEu ? '<span class="badge-vc">Você</span>' : ''}
                        ${ehEsteHost ? '<span class="badge-host">Host</span>' : ''}
                    </div>
                    ${possoRemover ? `
                        <button class="btn-remover" onclick="lobby.removerJogador(${jogador.id_jogador}, '${this.escapeHtml(jogador.nome)}')">
                            <i class="bi bi-person-x"></i>
                        </button>
                    ` : ''}
                </div>
            `;
        });

        container.innerHTML = html;
        document.getElementById('contadorJogadores').textContent = jogadores.length;

        if (this.ehHost) {
            const btnIniciar = document.getElementById('btnIniciar');
            btnIniciar.disabled = jogadores.length < 2;
        }
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    configurarBotoes() {
        console.log('🔧 Configurando botões - Host:', this.ehHost);
        
        if (this.ehHost) {
            console.log('🔧 Configurando botões do HOST');
            document.getElementById('btnIniciar').onclick = () => this.iniciarJogo();
            document.getElementById('btnFechar').onclick = () => this.fecharSala();
        } else {
            console.log('🔧 Configurando botões do JOGADOR');
            document.getElementById('btnSair').onclick = () => this.sairSala();
        }
    }

    async atualizarLobby() {
        await this.carregarJogadores();
        
        // CORREÇÃO: usar this.ehHost em vez de ehHost
        if (!this.ehHost) {
            const status = await this.ajax('verificar_status');
            if (status.status === 'iniciada') {
                window.location.href = `game.php?codigo=${codigoSala}`;
            }
        }
    }

    async iniciarJogo() {
        const { value: confirmar } = await Swal.fire({
            title: 'Iniciar Jogo?',
            html: `
                <div style="text-align: left; color: var(--cor-branco);">
                    <p>Tem certeza que deseja iniciar o jogo?</p>
                    <p><strong>Configurações:</strong></p>
                    <ul>
                        <li>${this.jogadores.length} jogadores conectados</li>
                        <li>${this.jogadores.find(j => j.is_host == 1)?.nome || 'Você'} é o Host</li>
                    </ul>
                </div>
            `,
            icon: 'question',
            background: 'var(--cor-fundo2)',
            color: 'var(--cor-branco)',
            iconColor: 'var(--cor-azul)',
            showCancelButton: true,
            confirmButtonText: 'Sim, iniciar!',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: 'var(--cor-amarela2)',
            cancelButtonColor: 'var(--cor-rosa)'
        });

        if (confirmar) {
            const resultado = await this.ajax('iniciar_jogo');
            if (resultado.status === 'ok') {
                await Swal.fire({
                    icon: 'success',
                    title: 'Jogo Iniciado!',
                    text: 'Redirecionando...',
                    background: 'var(--cor-fundo2)',
                    color: 'var(--cor-branco)',
                    iconColor: 'var(--cor-azul)',
                    timer: 2000,
                    showConfirmButton: false
                });
                window.location.href = `game.php?codigo=${codigoSala}`;
            } else {
                await Swal.fire({
                    icon: 'error',
                    title: 'Erro',
                    text: resultado.mensagem,
                    background: 'var(--cor-fundo2)',
                    color: 'var(--cor-branco)',
                    iconColor: 'var(--cor-rosa)'
                });
            }
        }
    }

    async sairSala() {
        const { value: confirmar } = await Swal.fire({
            title: 'Sair da Sala?',
            text: 'Você poderá entrar novamente se tiver o código.',
            icon: 'warning',
            background: 'var(--cor-fundo2)',
            color: 'var(--cor-branco)',
            iconColor: 'var(--cor-azul)',
            showCancelButton: true,
            confirmButtonText: 'Sim, sair!',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: 'var(--cor-azul2)',
            cancelButtonColor: 'var(--cor-rosa)'
        });

        if (confirmar) {
            const resultado = await this.ajax('sair_sala');
            if (resultado.status === 'ok') {
                window.location.href = '../index.php';
            } else {
                await Swal.fire({
                    icon: 'error',
                    title: 'Erro',
                    text: resultado.mensagem,
                    background: 'var(--cor-fundo2)',
                    color: 'var(--cor-branco)',
                    iconColor: 'var(--cor-rosa)'
                });
            }
        }
    }

    async fecharSala() {
        console.log('🚪 Tentando fechar sala...');
        const { value: confirmar } = await Swal.fire({
            title: 'Fechar Sala?',
            html: `
                <div style="text-align: left; color: var(--cor-branco);">
                    <p><strong>Tem certeza que deseja fechar a sala?</strong></p>
                    <p>⚠️ Todos os jogadores serão desconectados</p>
                    <p>⚠️ A sala será excluída permanentemente</p>
                </div>
            `,
            icon: 'warning',
            background: 'var(--cor-fundo2)',
            color: 'var(--cor-branco)',
            iconColor: 'var(--cor-rosa)',
            showCancelButton: true,
            confirmButtonText: 'Sim, fechar!',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: 'var(--cor-rosa)',
            cancelButtonColor: 'var(--cor-azul)'
        });

        if (confirmar) {
            console.log('✅ Confirmou fechar sala');
            try {
                const resultado = await this.ajax('fechar_sala');
                console.log('📦 Resultado fechar sala:', resultado);
                
                if (resultado.status === 'ok') {
                    await Swal.fire({
                        icon: 'success',
                        title: 'Sala Fechada!',
                        text: 'Redirecionando...',
                        background: 'var(--cor-fundo2)',
                        color: 'var(--cor-branco)',
                        iconColor: 'var(--cor-azul)',
                        timer: 1500,
                        showConfirmButton: false
                    });
                    window.location.href = '../index.php';
                } else {
                    await Swal.fire({
                        icon: 'error',
                        title: 'Erro',
                        text: resultado.mensagem || 'Erro desconhecido',
                        background: 'var(--cor-fundo2)',
                        color: 'var(--cor-branco)',
                        iconColor: 'var(--cor-rosa)'
                    });
                }
            } catch (error) {
                console.error('❌ Erro ao fechar sala:', error);
                await Swal.fire({
                    icon: 'error',
                    title: 'Erro de conexão',
                    text: 'Não foi possível conectar ao servidor',
                    background: 'var(--cor-fundo2)',
                    color: 'var(--cor-branco)',
                    iconColor: 'var(--cor-rosa)'
                });
            }
        }
    }

    async removerJogador(id, nome) {
        console.log('👥 Tentando remover jogador:', id, nome);
        const { value: confirmar } = await Swal.fire({
            title: 'Remover Jogador?',
            html: `
                <div style="text-align: left; color: var(--cor-branco);">
                    <p>Tem certeza que deseja remover <strong>${nome}</strong> da sala?</p>
                    <p>O jogador será desconectado imediatamente.</p>
                </div>
            `,
            icon: 'question',
            background: 'var(--cor-fundo2)',
            color: 'var(--cor-branco)',
            iconColor: 'var(--cor-rosa)',
            showCancelButton: true,
            confirmButtonText: 'Sim, remover!',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: 'var(--cor-rosa)',
            cancelButtonColor: 'var(--cor-azul)'
        });

        if (confirmar) {
            console.log('✅ Confirmou remover jogador');
            try {
                const resultado = await this.ajax('remover_jogador', {id_jogador: id});
                console.log('📦 Resultado remover jogador:', resultado);
                
                if (resultado.status === 'ok') {
                    await Swal.fire({
                        icon: 'success',
                        title: 'Jogador Removido!',
                        text: `${nome} foi removido da sala`,
                        background: 'var(--cor-fundo2)',
                        color: 'var(--cor-branco)',
                        iconColor: 'var(--cor-azul)',
                        timer: 1500,
                        showConfirmButton: false
                    });
                    this.carregarJogadores();
                } else {
                    await Swal.fire({
                        icon: 'error',
                        title: 'Erro',
                        text: resultado.mensagem || 'Erro desconhecido',
                        background: 'var(--cor-fundo2)',
                        color: 'var(--cor-branco)',
                        iconColor: 'var(--cor-rosa)'
                    });
                }
            } catch (error) {
                console.error('❌ Erro ao remover jogador:', error);
                await Swal.fire({
                    icon: 'error',
                    title: 'Erro de conexão',
                    text: 'Não foi possível conectar ao servidor',
                    background: 'var(--cor-fundo2)',
                    color: 'var(--cor-branco)',
                    iconColor: 'var(--cor-rosa)'
                });
            }
        }
    }

    async ajax(acao, dadosExtras = {}) {
        const formData = new FormData();
        formData.append('acao', acao);
        formData.append('codigo', codigoSala);
        
        for (let key in dadosExtras) {
            formData.append(key, dadosExtras[key]);
        }

        console.log(`📡 Fazendo requisição: ${acao}`, Object.fromEntries(formData));
        
        const response = await fetch('../utils/lobby_actions.php', {
            method: 'POST',
            body: formData
        });

        const resultado = await response.json();
        console.log(`📦 Resposta ${acao}:`, resultado);
        
        return resultado;
    }

    mostrarErro(mensagem) {
        Swal.fire({
            icon: 'error',
            title: 'Erro',
            text: mensagem || 'Algo deu errado',
            background: 'var(--cor-fundo2)',
            color: 'var(--cor-branco)',
            iconColor: 'var(--cor-rosa)'
        });
    }
}

const lobby = new Lobby();