<?php
session_start();
require_once '../db/conexao.php';

if (!isset($_GET['codigo'])) {
    header('Location: ../index.php');
    exit;
}

$codigo_sala = $_GET['codigo'];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ranking - BarelyKnow</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../public/css/ranking.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="ranking-body">
    <div class="container">
        <div class="text-center mb-4">
            <h1>Ranking Final</h1>
            <p class="text-muted">Sala: <?php echo htmlspecialchars($codigo_sala); ?></p>
        </div>

        <div id="rankingContainer"></div>

        <div class="text-center mt-4">
            <a href="../index.php" class="btn btn-primary-custom btn-custom">Voltar ao Início</a>
        </div>
    </div>

    <script>
        async function carregarRanking() {
            const formData = new FormData();
            formData.append('codigo_sala', '<?php echo $codigo_sala; ?>');

            try {
                const response = await fetch('ranking_controller.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.status === 'ok') {
                    exibirRanking(data.ranking);
                }
            } catch (error) {
                console.error('Erro ao carregar ranking:', error);
            }
        }

        function exibirRanking(ranking) {
            const container = document.getElementById('rankingContainer');
            container.innerHTML = '';

            ranking.forEach((jogador, index) => {
                const div = document.createElement('div');
                div.className = 'ranking-item';
                
                let classePosicao = '';
                if (index === 0) classePosicao = 'primeiro';
                else if (index === 1) classePosicao = 'segundo';
                else if (index === 2) classePosicao = 'terceiro';

                div.innerHTML = `
                    <div class="d-flex align-items-center">
                        <span class="posicao ${classePosicao}">${index + 1}º</span>
                        <span class="ms-3">${jogador.nome}</span>
                    </div>
                    <span class="badge bg-primary">${jogador.acertos} acertos</span>
                `;
                
                container.appendChild(div);
            });
        }

        document.addEventListener('DOMContentLoaded', carregarRanking);
    </script>
</body>
</html>