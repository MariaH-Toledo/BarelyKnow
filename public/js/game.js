class GameController {
    constructor() {
        this.rodadaAtual = 1;
        this.tempoRestante = 0;
        this.timerInterval = null;
        this.perguntaAtual = null;
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
            const data = await response.json();

            if (data.status === 'ok') {
                this.perguntaAtual = data.pergunta;
                this.exibirPergunta();
                this.respostaEnviada = false;
            } else {
                this.finalizarJogo();
            }
        } catch (error) {
            console.error('Erro ao carregar pergunta:', error);
        }
    }

    exibirPergunta() {
        document.getElementById('perguntaTexto').textContent = this.perguntaAtual.pergunta;
        
        // Embaralhar alternativas
        const alternativas = [
            this.perguntaAtual.alternativa1,
            this.perguntaAtual.alternativa2, 
            this.perguntaAtual.alternativa3,
            this.perguntaAtual.alternativa4
        ];
        
        // Embaralhar (mantendo controle da correta)
        const indices = [0, 1, 2, 3];
        for (let i = indices.length - 1; i > 0; i--) {
            const j = Math.floor(Math.random() * (i + 1));
            [indices[i], indices[j]] = [indices[j], indices[i]];
        }

        document.getElementById('textoAlternativa1').textContent = alternativas[indices[0]];
        document.getElementById('textoAlternativa2').textContent = alternativas[indices[1]];
        document.getElementById('textoAlternativa3').textContent = alternativas[indices[2]];
        document.getElementById('textoAlternativa4').textContent = alternativas[indices[3]];

        // Salvar mapeamento para verificação
        this.mapeamentoRespostas = indices;

        document.getElementById('numeroRodada').textContent = this.rodadaAtual;
        
        // Configurar eventos das alternativas
        this.configurarEventosAlternativas();
    }

    configurarEventosAlternativas() {
        const alternativas = document.querySelectorAll('.alternativa');
        alternativas.forEach(alt => {
            alt.onclick = () => this.selecionarAlternativa(alt);
        });
    }

    selecionarAlternativa(alternativaElement) {
        if (this.respostaEnviada) return;

        // Remover seleção anterior
        document.querySelectorAll('.alternativa').forEach(alt => {
            alt.classList.remove('selecionada');
        });

        // Selecionar nova
        alternativaElement.classList.add('selecionada');
        
        const alternativaIndex = parseInt(alternativaElement.dataset.alternativa);
        this.enviarResposta(alternativaIndex);
    }

    async enviarResposta(alternativaIndex) {
        if (this.respostaEnviada) return;
        
        this.respostaEnviada = true;
        const tempoResposta = tempoResposta - this.tempoRestante;
        
        const respostaCorreta = (this.mapeamentoRespostas[alternativaIndex - 1] === 0);
        
        try {
            const response = await fetch('../utils/registrar_resposta.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `id_jogador=${idJogador}&id_pergunta=${this.perguntaAtual.id_pergunta}&resposta_escolhida=${alternativaIndex}&correta=${respostaCorreta ? 1 : 0}&tempo_resposta=${tempoResposta}`
            });

            const data = await response.json();
            
            this.mostrarFeedback(respostaCorreta);
            
            setTimeout(() => {
                this.proximaRodada();
            }, 2000);

        } catch (error) {
            console.error('Erro ao enviar resposta:', error);
        }
    }

    mostrarFeedback(correta) {
        const feedback = document.getElementById('feedback');
        if (correta) {
            feedback.innerHTML = '<div class="alert alert-success">✓ Resposta Correta!</div>';
        } else {
            feedback.innerHTML = '<div class="alert alert-danger">✗ Resposta Incorreta</div>';
        }
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
        
        const timerElement = document.getElementById('timer');
        if (this.tempoRestante <= 5) {
            timerElement.style.color = '#ff4444';
        } else if (this.tempoRestante <= 10) {
            timerElement.style.color = '#ffaa00';
        } else {
            timerElement.style.color = 'white';
        }
    }

    proximaRodada() {
        this.rodadaAtual++;
        
        if (this.rodadaAtual > totalRodadas) {
            this.finalizarJogo();
        } else {
            this.carregarPergunta();
            this.tempoRestante = tempoResposta;
            this.iniciarTimer();
            document.getElementById('feedback').innerHTML = '';
        }
    }

    async finalizarJogo() {
        try {
            const response = await fetch('../utils/finalizar_jogo.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `codigo=${codigoSala}`
            });

            window.location.href = `ranking.php?codigo=${codigoSala}`;
        } catch (error) {
            console.error('Erro ao finalizar jogo:', error);
        }
    }
}

document.addEventListener('DOMContentLoaded', () => {
    new GameController();
});