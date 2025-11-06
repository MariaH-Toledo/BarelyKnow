class GameController {
    constructor() {
        this.rodadaAtual = 1;
        this.tempoRestante = 0;
        this.perguntaAtual = null;
        this.timerInterval = null;
        this.respostaEnviada = false;
        this.init();
    }

    async init() {
        await this.carregarPergunta();
        this.iniciarTimer();
    }

    async carregarPergunta() {
        try {
            const response = await fetch(`../utils/obter_pergunta.php?codigo=${codigoSala}&rodada=${this.rodadaAtual}`);
            
            if (!response.ok) {
                throw new Error('Erro na rede');
            }
            
            const data = await response.json();

            if (data.status === 'ok') {
                this.perguntaAtual = data.pergunta;
                this.exibirPergunta();
                this.respostaEnviada = false;
            } else if (data.status === 'fim') {
                this.finalizarJogo();
            } else {
                this.mostrarErro('Erro ao carregar pergunta: ' + data.mensagem);
            }
        } catch (error) {
            this.mostrarErro('Erro de conexão ao carregar pergunta');
        }
    }

    exibirPergunta() {
        document.getElementById('perguntaTexto').textContent = this.perguntaAtual.pergunta;
        
        const alternativas = [
            this.perguntaAtual.alternativa1,
            this.perguntaAtual.alternativa2, 
            this.perguntaAtual.alternativa3,
            this.perguntaAtual.alternativa4
        ];
        
        for (let i = alternativas.length - 1; i > 0; i--) {
            const j = Math.floor(Math.random() * (i + 1));
            [alternativas[i], alternativas[j]] = [alternativas[j], alternativas[i]];
        }

        document.getElementById('textoAlternativa1').textContent = alternativas[0];
        document.getElementById('textoAlternativa2').textContent = alternativas[1];
        document.getElementById('textoAlternativa3').textContent = alternativas[2];
        document.getElementById('textoAlternativa4').textContent = alternativas[3];

        document.getElementById('numeroRodada').textContent = this.rodadaAtual;
        this.configurarEventos();
    }

    configurarEventos() {
        const alternativas = document.querySelectorAll('.alternativa');
        alternativas.forEach(alt => {
            alt.onclick = () => this.selecionarAlternativa(alt);
        });
    }

    selecionarAlternativa(alt) {
        if (this.respostaEnviada) return;

        document.querySelectorAll('.alternativa').forEach(a => {
            a.classList.remove('selecionada');
        });

        alt.classList.add('selecionada');
        
        const alternativaIndex = parseInt(alt.dataset.alternativa);
        this.enviarResposta(alternativaIndex);
    }

    async enviarResposta(alternativaIndex) {
        if (this.respostaEnviada) return;
        this.respostaEnviada = true;

        const textoResposta = document.getElementById(`textoAlternativa${alternativaIndex}`).textContent;
        const respostaCorreta = (textoResposta === this.perguntaAtual.alternativa1);
        
        try {
            const response = await fetch('../utils/registrar_resposta.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `id_jogador=${idJogador}&id_pergunta=${this.perguntaAtual.id_pergunta}&resposta_escolhida=${alternativaIndex}&correta=${respostaCorreta ? 1 : 0}&tempo_resposta=${this.tempoRestante}`
            });

            if (respostaCorreta) {
                this.mostrarFeedback('✓ Resposta Correta!', 'success');
            } else {
                this.mostrarFeedback('✗ Resposta Incorreta!', 'error');
            }

            setTimeout(() => {
                this.proximaRodada();
            }, 2000);

        } catch (error) {
            this.mostrarErro('Erro ao enviar resposta');
        }
    }

    mostrarFeedback(mensagem, tipo) {
        Swal.fire({
            title: mensagem,
            icon: tipo,
            timer: 1500,
            showConfirmButton: false,
            background: 'var(--cor-fundo2)',
            color: 'var(--cor-branco)'
        });
    }

    mostrarErro(mensagem) {
        Swal.fire({
            icon: 'error',
            title: 'Erro',
            text: mensagem,
            background: 'var(--cor-fundo2)',
            color: 'var(--cor-branco)'
        });
    }

    iniciarTimer() {
        this.tempoRestante = tempoResposta;
        this.atualizarTimer();
        
        this.timerInterval = setInterval(() => {
            this.tempoRestante--;
            this.atualizarTimer();
            
            if (this.tempoRestante <= 0) {
                clearInterval(this.timerInterval);
                if (!this.respostaEnviada) {
                    this.enviarResposta(0);
                }
            }
        }, 1000);
    }

    atualizarTimer() {
        document.getElementById('timer').textContent = this.tempoRestante;
        
        if (this.tempoRestante <= 5) {
            document.getElementById('timer').style.color = 'var(--cor-rosa)';
        } else {
            document.getElementById('timer').style.color = 'var(--cor-branco)';
        }
    }

    proximaRodada() {
        this.rodadaAtual++;
        
        if (this.rodadaAtual > totalRodadas) {
            this.finalizarJogo();
        } else {
            this.carregarPergunta();
            this.iniciarTimer();
        }
    }

    finalizarJogo() {
        window.location.href = `ranking.php?codigo=${codigoSala}`;
    }
}

document.addEventListener('DOMContentLoaded', () => {
    new GameController();
});