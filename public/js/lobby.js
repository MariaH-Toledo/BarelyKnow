class LobbyController {
    constructor() {
        this.ultimaAtualizacao = 0;
        this.init();
    }

    init() {
        this.configurarEventos();
        this.iniciarAtualizacaoAutomatica();
        this.atualizarLobby();
    }

    configurarEventos() {
        const btnFechar = document.querySelector("#btnFechar");
        if (btnFechar) {
            btnFechar.addEventListener("click", () => this.fecharSala());
        }

        const btnSair = document.querySelector("#btnSair");
        if (btnSair) {
            btnSair.addEventListener("click", () => this.sairSala());
        }

        const btnIniciar = document.querySelector("#btnIniciar");
        if (btnIniciar) {
            btnIniciar.addEventListener("click", () => this.iniciarJogo());
        }
    }

    get swalDarkTheme() {
        return {
            color: 'white',
            background: '#1a1a1a',
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33'
        };
    }

    async fecharSala() {
        const confirm = await Swal.fire({
            title: "Fechar Sala?",
            text: "Todos os jogadores serão desconectados e a sala será encerrada permanentemente.",
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Sim, fechar sala",
            cancelButtonText: "Cancelar",
            confirmButtonColor: "#d33",
            cancelButtonColor: "#6c757d",
            ...this.swalDarkTheme
        });

        if (confirm.isConfirmed) {
            try {
                const res = await fetch("../utils/fechar_sala.php", {
                    method: "POST",
                    headers: { 
                        "Content-Type": "application/x-www-form-urlencoded" 
                    },
                    body: `codigo=${encodeURIComponent(codigoSala)}`
                });

                const data = await res.json();

                if (data.status === "ok") {
                    Swal.fire({
                        icon: "success",
                        title: "Sala fechada!",
                        text: "A sala foi encerrada com sucesso.",
                        timer: 1500,
                        showConfirmButton: false,
                        ...this.swalDarkTheme
                    }).then(() => {
                        window.location.href = "../index.php";
                    });
                } else {
                    Swal.fire({
                        icon: "error",
                        title: "Erro",
                        text: data.mensagem || "Não foi possível fechar a sala.",
                        ...this.swalDarkTheme
                    });
                }
            } catch (err) {
                Swal.fire({
                    icon: "error",
                    title: "Erro de conexão",
                    text: "Não foi possível conectar ao servidor.",
                    ...this.swalDarkTheme
                });
            }
        }
    }

    async sairSala() {
        const confirm = await Swal.fire({
            title: "Sair da Sala?",
            text: "Você será removido desta sala.",
            icon: "question",
            showCancelButton: true,
            confirmButtonText: "Sim, sair",
            cancelButtonText: "Cancelar",
            confirmButtonColor: "#ffc107",
            cancelButtonColor: "#6c757d",
            ...this.swalDarkTheme
        });

        if (confirm.isConfirmed) {
            try {
                const res = await fetch("../utils/sair_sala.php", {
                    method: "POST",
                    headers: { 
                        "Content-Type": "application/x-www-form-urlencoded" 
                    },
                    body: `codigo=${encodeURIComponent(codigoSala)}`
                });

                const data = await res.json();

                if (data.status === "ok") {
                    Swal.fire({
                        icon: "success",
                        title: "Você saiu da sala!",
                        text: "Você foi removido da sala com sucesso.",
                        timer: 1500,
                        showConfirmButton: false,
                        ...this.swalDarkTheme
                    }).then(() => {
                        window.location.href = "../index.php";
                    });
                } else {
                    Swal.fire({
                        icon: "error",
                        title: "Erro",
                        text: data.mensagem || "Não foi possível sair da sala.",
                        ...this.swalDarkTheme
                    });
                }
            } catch (err) {
                Swal.fire({
                    icon: "error",
                    title: "Erro de conexão",
                    text: "Não foi possível conectar ao servidor.",
                    ...this.swalDarkTheme
                });
            }
        }
    }

    async iniciarJogo() {
        try {
            const res = await fetch("../utils/iniciar_jogo.php", {
                method: "POST",
                headers: { 
                    "Content-Type": "application/x-www-form-urlencoded" 
                },
                body: `codigo=${encodeURIComponent(codigoSala)}`
            });

            const data = await res.json();

            if (data.status === "ok") {
                window.location.href = `game.php?codigo=${codigoSala}`;
            } else {
                Swal.fire({
                    icon: "error",
                    title: "Erro",
                    text: data.mensagem || "Não foi possível iniciar o jogo.",
                    ...this.swalDarkTheme
                });
            }
        } catch (err) {
            Swal.fire({
                icon: "error",
                title: "Erro de conexão",
                text: "Não foi possível conectar ao servidor.",
                ...this.swalDarkTheme
            });
        }
    }

    async removerJogador(idJogador, nomeJogador) {
        const confirm = await Swal.fire({
            title: "Remover Jogador?",
            html: `Deseja remover <strong>${nomeJogador}</strong> da sala?`,
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Sim, remover",
            cancelButtonText: "Cancelar",
            confirmButtonColor: "#d33",
            cancelButtonColor: "#6c757d",
            ...this.swalDarkTheme
        });

        if (confirm.isConfirmed) {
            try {
                const res = await fetch("../utils/remover_jogador.php", {
                    method: "POST",
                    headers: { 
                        "Content-Type": "application/x-www-form-urlencoded" 
                    },
                    body: `codigo_sala=${encodeURIComponent(codigoSala)}&id_jogador=${idJogador}`
                });

                const data = await res.json();

                if (data.status === "ok") {
                    Swal.fire({
                        icon: "success",
                        title: "Jogador removido!",
                        text: `${nomeJogador} foi removido da sala.`,
                        timer: 1500,
                        showConfirmButton: false,
                        ...this.swalDarkTheme
                    });
                } else {
                    Swal.fire({
                        icon: "error",
                        title: "Erro",
                        text: data.mensagem || "Não foi possível remover o jogador.",
                        ...this.swalDarkTheme
                    });
                }
            } catch (err) {
                Swal.fire({
                    icon: "error",
                    title: "Erro de conexão",
                    text: "Não foi possível conectar ao servidor.",
                    ...this.swalDarkTheme
                });
            }
        }
    }

    async atualizarJogadores() {
        try {
            const res = await fetch(`../utils/listar_jogadores.php?codigo=${codigoSala}&t=${Date.now()}`);
            const data = await res.json();
            
            const container = document.querySelector("#listaJogadores");
            
            if (data.length > 0) {
                let html = '';
                data.forEach(jogador => {
                    const isHost = jogador.is_host == 1;
                    const isCurrentPlayer = (jogador.id_jogador == idJogadorAtual);
                    
                    html += `
                        <div class="d-flex justify-content-between align-items-center p-2 border-bottom border-secondary">
                            <div class="d-flex align-items-center">
                                <span class="me-2">${isHost ? '👑' : '👤'}</span>
                                <span class="${isCurrentPlayer ? 'text-primary fw-bold' : 'text-light'}">
                                    ${jogador.nome} ${isCurrentPlayer ? '(Você)' : ''}
                                </span>
                            </div>
                            ${ehHost && !isHost ? `
                                <button class="btn btn-sm btn-outline-danger btn-remover" 
                                        data-id="${jogador.id_jogador}" 
                                        data-nome="${jogador.nome}">
                                    <i class="bi bi-person-x"></i>
                                </button>
                            ` : ''}
                        </div>
                    `;
                });
                container.innerHTML = html;

                document.querySelectorAll('.btn-remover').forEach(btn => {
                    btn.addEventListener('click', (e) => {
                        const id = e.target.closest('.btn-remover').dataset.id;
                        const nome = e.target.closest('.btn-remover').dataset.nome;
                        this.removerJogador(id, nome);
                    });
                });
            } else {
                container.innerHTML = '<div class="text-muted">Nenhum jogador na sala...</div>';
            }
        } catch (error) {
            console.error('Erro ao atualizar jogadores:', error);
        }
    }

    async verificarStatusJogo() {
        try {
            const res = await fetch(`../utils/verificar_status_sala.php?codigo=${codigoSala}&t=${Date.now()}`);
            const data = await res.json();
            
            if (data.status === 'iniciada') {
                window.location.href = `game.php?codigo=${codigoSala}`;
                return true;
            }
            return false;
        } catch (error) {
            console.error('Erro ao verificar status:', error);
            return false;
        }
    }

    async atualizarLobby() {
        const agora = Date.now();
        
        if (agora - this.ultimaAtualizacao > 1000) {
            await this.atualizarJogadores();
            
            if (!ehHost) {
                await this.verificarStatusJogo();
            }
            
            this.ultimaAtualizacao = agora;
        }
    }

    iniciarAtualizacaoAutomatica() {
        setInterval(() => this.atualizarLobby(), 2000);
    }
}

document.addEventListener('DOMContentLoaded', () => {
    new LobbyController();
});