<?php
session_start();
include "../db/conexao.php";

// Pega o código da sala
$codigo = $_GET['codigo'] ?? '';

if (empty($codigo)) {
    header("Location: ../index.php");
    exit;
}

// Busca informações da sala
$stmt = $conn->prepare("SELECT s.*, c.nome_categoria FROM salas s JOIN categorias c ON s.id_categoria = c.id_categoria WHERE s.codigo_sala = ?");
$stmt->bind_param("s", $codigo);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: ../index.php");
    exit;
}

$sala = $result->fetch_assoc();

// Verifica se o jogador está na sessão
$id_jogador = $_SESSION['id_jogador'] ?? 0;
$nome_jogador = $_SESSION['nome_jogador'] ?? 'Desconhecido';

// Busca todos os jogadores da sala
$stmt = $conn->prepare("SELECT nome, is_host FROM jogadores WHERE id_sala = ? ORDER BY is_host DESC, id_jogador ASC");
$stmt->bind_param("i", $sala['id_sala']);
$stmt->execute();
$result = $stmt->get_result();

$jogadores = [];
while ($row = $result->fetch_assoc()) {
    $jogadores[] = $row;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jogo Iniciado - BarelyKnow</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }

        .container {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 40px;
            max-width: 600px;
            width: 90%;
            text-align: center;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
        }

        h1 {
            font-size: 3em;
            margin-bottom: 20px;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }

        .info-box {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            padding: 20px;
            margin: 20px 0;
        }

        .info-item {
            margin: 10px 0;
            font-size: 1.2em;
        }

        .jogadores-list {
            margin-top: 30px;
            text-align: left;
        }

        .jogador {
            background: rgba(255, 255, 255, 0.15);
            padding: 15px;
            border-radius: 10px;
            margin: 10px 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .badge {
            background: #ffd700;
            color: #333;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8em;
            font-weight: bold;
        }

        .emoji {
            font-size: 1.5em;
        }

        .btn-voltar {
            margin-top: 30px;
            padding: 15px 30px;
            background: #ff6b6b;
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1.1em;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-voltar:hover {
            background: #ff5252;
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🎮 JOGO INICIADO! 🎮</h1>
        
        <div class="info-box">
            <div class="info-item">
                <strong>Código da Sala:</strong> <?= $codigo ?>
            </div>
            <div class="info-item">
                <strong>Categoria:</strong> <?= $sala['nome_categoria'] ?>
            </div>
            <div class="info-item">
                <strong>Rodadas:</strong> <?= $sala['rodadas'] ?>
            </div>
            <div class="info-item">
                <strong>Tempo por Pergunta:</strong> <?= $sala['tempo_resposta'] ?> segundos
            </div>
        </div>

        <div class="jogadores-list">
            <h2>👥 Jogadores na Partida:</h2>
            <?php foreach ($jogadores as $jogador): ?>
                <div class="jogador">
                    <span class="emoji"><?= $jogador['is_host'] ? '👑' : '🎮' ?></span>
                    <span><?= htmlspecialchars($jogador['nome']) ?></span>
                    <?php if ($jogador['is_host']): ?>
                        <span class="badge">HOST</span>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <p style="margin-top: 30px; font-size: 1.2em;">
            ✅ Sistema funcionando corretamente!<br>
            Aqui você irá implementar o quiz futuramente.
        </p>

        <button class="btn-voltar" onclick="window.location.href='../index.php'">
            🏠 Voltar para Início
        </button>
    </div>
</body>
</html>