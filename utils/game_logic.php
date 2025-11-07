game_logic.php
<?php
session_start();
header("Content-Type: application/json");
include "../db/conexao.php";

$acao = $_POST['acao'] ?? '';
$codigo_sala = $_POST['codigo_sala'] ?? '';
$id_jogador = $_POST['id_jogador'] ?? 0;

if (empty($codigo_sala) || empty($id_jogador)) {
    echo json_encode(["status" => "erro", "mensagem" => "Dados inválidos"]);
    exit;
}

$stmt = $conn->prepare("SELECT id_sala FROM salas WHERE codigo_sala = ?");
$stmt->bind_param("s", $codigo_sala);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(["status" => "erro", "mensagem" => "Sala não encontrada"]);
    exit;
}

$sala = $result->fetch_assoc();
$id_sala = $sala['id_sala'];

switch ($acao) {
    case 'carregar_pergunta':
        carregarPergunta($conn, $id_sala, $id_jogador);
        break;
        
    case 'responder':
        $alternativa = $_POST['alternativa'] ?? 0;
        $tempo_restante = $_POST['tempo_restante'] ?? 0;
        processarResposta($conn, $id_sala, $id_jogador, $alternativa, $tempo_restante);
        break;
        
    default:
        echo json_encode(["status" => "erro", "mensagem" => "Ação desconhecida"]);
}

function carregarPergunta($conn, $id_sala, $id_jogador) {
    $stmt = $conn->prepare("SELECT rodadas, id_categoria FROM salas WHERE id_sala = ?");
    $stmt->bind_param("i", $id_sala);
    $stmt->execute();
    $result = $stmt->get_result();
    $sala = $result->fetch_assoc();
    
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM respostas WHERE id_jogador = ?");
    $stmt->bind_param("i", $id_jogador);
    $stmt->execute();
    $result = $stmt->get_result();
    $respondidas = $result->fetch_assoc()['total'];
    
    if ($respondidas >= $sala['rodadas']) {
        echo json_encode(["status" => "fim"]);
        return;
    }
    
    $numero_pergunta = $respondidas + 1;
    
    $stmt = $conn->prepare("
        SELECT p.* 
        FROM perguntas p 
        WHERE p.id_categoria = ? 
        AND p.id_pergunta NOT IN (
            SELECT r.id_pergunta 
            FROM respostas r 
            WHERE r.id_jogador = ?
        )
        ORDER BY RAND() 
        LIMIT 1
    ");
    $stmt->bind_param("ii", $sala['id_categoria'], $id_jogador);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $stmt = $conn->prepare("
            SELECT p.* 
            FROM perguntas p 
            WHERE p.id_categoria = ? 
            ORDER BY RAND() 
            LIMIT 1
        ");
        $stmt->bind_param("i", $sala['id_categoria']);
        $stmt->execute();
        $result = $stmt->get_result();
    }
    
    $pergunta = $result->fetch_assoc();
    
    $alternativas = [
        $pergunta['alternativa1'],
        $pergunta['alternativa2'],
        $pergunta['alternativa3'],
        $pergunta['alternativa4']
    ];
    shuffle($alternativas);
    
    $resposta_correta = array_search($pergunta['alternativa1'], $alternativas) + 1;
    
    $_SESSION['resposta_correta'] = $resposta_correta;
    $_SESSION['id_pergunta_atual'] = $pergunta['id_pergunta'];
    
    echo json_encode([
        "status" => "ok",
        "pergunta" => [
            "numero" => $numero_pergunta,
            "pergunta" => $pergunta['pergunta'],
            "alternativas" => $alternativas,
            "resposta_correta" => $resposta_correta
        ]
    ]);
}

function processarResposta($conn, $id_sala, $id_jogador, $alternativa_escolhida, $tempo_restante) {
    $resposta_correta = $_SESSION['resposta_correta'] ?? 0;
    $id_pergunta = $_SESSION['id_pergunta_atual'] ?? 0;
    
    $acertou = ($alternativa_escolhida == $resposta_correta);
    $tempo_resposta = $tempo_restante;
    
    $pontos = 0;
    if ($acertou && $alternativa_escolhida > 0) {
        $pontos = max(100, intval(($tempo_resposta / 15) * 1000));
    }
    
    $stmt = $conn->prepare("
        INSERT INTO respostas (id_jogador, id_pergunta, resposta_escolhida, correta, tempo_resposta) 
        VALUES (?, ?, ?, ?, ?)
    ");
    
    $resposta_texto = '';
    if ($alternativa_escolhida > 0) {
        $letras = ['A', 'B', 'C', 'D'];
        $resposta_texto = $letras[$alternativa_escolhida - 1];
    }
    
    $stmt->bind_param("iisii", $id_jogador, $id_pergunta, $resposta_texto, $acertou, $tempo_resposta);
    $stmt->execute();
    
    if ($pontos > 0) {
        $stmt = $conn->prepare("UPDATE jogadores SET pontos = pontos + ? WHERE id_jogador = ?");
        $stmt->bind_param("ii", $pontos, $id_jogador);
        $stmt->execute();
    }
    
    unset($_SESSION['resposta_correta']);
    unset($_SESSION['id_pergunta_atual']);
    
    echo json_encode([
        "status" => "ok",
        "acertou" => $acertou,
        "pontos" => $pontos,
        "resposta_correta" => $resposta_correta,
        "alternativa_escolhida" => $alternativa_escolhida
    ]);
}
?>