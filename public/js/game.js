class GameController {
    constructor() {
        this.rodadaAtual = 1;
        this.tempoRestante = 0;
        this.perguntaAtual = null;
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
            } else if (data.status === 'fim') {
                this.finalizarJogo();
            }
        } catch (error) {
            console.error('Erro:', error);
        }
    }

    exibirPergunta() {
        document.getElementById('perguntaTexto').textContent = this.perguntaAtual.pergunta;
        
        // Alternativas (a primeira é sempre a correta)
        const alternativas = [
            this.perguntaAtual.alternativa1,
            this.perguntaAtual.alternativa2, 
            this.perguntaAtual.alternativa3,
            this.perguntaAtual.alternativa4
        ];
        
        // Embaralhar
        for (let i = alternativas.length - 1; i > 0; i--) {
            const j = Math.floor(Math.random() * (i + 1));
            [alternativas[i], alternativas[j]] = [alternativas[j], alternativas[i]];
        }

        // Exibir
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
        // Remover seleção anterior
        document.querySelectorAll('.alternativa').forEach(a => {
            a.classList.remove('selecionada');
        });

        // Selecionar nova
        alt.classList.add('selecionada');
        
        const alternativaIndex = parseInt(alt.dataset.alternativa);
        this.enviarResposta(alternativaIndex);
    }

    async enviarResposta(alternativaIndex) {
        const textoResposta = document.getElementById(`textoAlternativa${alternativaIndex}`).textContent;
        const respostaCorreta = (textoResposta === this.perguntaAtual.alternativa1);
        
        try {
            await fetch('../utils/registrar_resposta.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `id_jogador=${idJogador}&id_pergunta=${this.perguntaAtual.id_pergunta}&resposta_escolhida=${alternativaIndex}&correta=${respostaCorreta ? 1 : 0}&tempo_resposta=${this.tempoRestante}`
            });

            // Mostrar feedback
            if (respostaCorreta) {
                alert('✓ Correta!');
            } else {
                alert('✗ Errada!');
            }

            setTimeout(() => {
                this.proximaRodada();
            }, 1000);

        } catch (error) {
            console.error('Erro:', error);
        }
    }

    iniciarTimer() {
        this.tempoRestante = tempoResposta;
        this.timerInterval = setInterval(() => {
            this.tempoRestante--;
            document.getElementById('timer').textContent = this.tempoRestante;
            
            if (this.tempoRestante <= 0) {
                clearInterval(this.timerInterval);
                this.enviarResposta(0); // Tempo esgotado
            }
        }, 1000);
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

// Iniciar jogo
document.addEventListener('DOMContentLoaded', () => {
    new GameController();
});