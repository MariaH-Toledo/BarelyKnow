<?php
session_start();
header("Content-Type: application/json; charset=utf-8");

ini_set('display_errors', 0);
error_reporting(0);

try {
    include "../db/conexao.php";
    
    $acao = $_POST['acao'] ?? '';
    $codigo_sala = $_POST['codigo_sala'] ?? '';
    $id_jogador = $_POST['id_jogador'] ?? 0;
    
    if (empty($codigo_sala) || empty($id_jogador)) {
        echo json_encode(["status" => "erro", "mensagem" => "Dados invalidos"], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    $stmt = $conn->prepare("SELECT id_sala FROM salas WHERE codigo_sala = ?");
    $stmt->bind_param("s", $codigo_sala);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(["status" => "erro", "mensagem" => "Sala nao encontrada"], JSON_UNESCAPED_UNICODE);
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
            $tempo_clique = $_POST['tempo_clique'] ?? 0;
            processarResposta($conn, $id_sala, $id_jogador, $alternativa, $tempo_clique);
            break;
            
        case 'verificar_resultado':
            verificarResultado($conn, $id_sala, $id_jogador);
            break;
        
        case 'limpar_pergunta':
            limparPergunta($conn, $id_sala, $id_jogador);
            break;
            
        default:
            echo json_encode(["status" => "erro", "mensagem" => "Acao desconhecida"], JSON_UNESCAPED_UNICODE);
    }
    
} catch (Exception $e) {
    echo json_encode(["status" => "erro", "mensagem" => "Erro interno: " . $e->getMessage()], JSON_UNESCAPED_UNICODE);
}

function carregarPergunta($conn, $id_sala, $id_jogador) {
    $stmt = $conn->prepare("
        SELECT rodadas, rodada_atual, id_categoria, id_pergunta_atual, 
               alternativas_ordem, tempo_inicio_pergunta, tempo_resposta 
        FROM salas 
        WHERE id_sala = ?
    ");
    $stmt->bind_param("i", $id_sala);
    $stmt->execute();
    $result = $stmt->get_result();
    $sala = $result->fetch_assoc();
    
    if ($sala['rodada_atual'] > $sala['rodadas']) {
        echo json_encode(["status" => "fim"], JSON_UNESCAPED_UNICODE);
        return;
    }
    
    $stmt = $conn->prepare("SELECT is_host FROM jogadores WHERE id_jogador = ? AND id_sala = ?");
    $stmt->bind_param("ii", $id_jogador, $id_sala);
    $stmt->execute();
    $result = $stmt->get_result();
    $jogador = $result->fetch_assoc();
    $eh_host = ($jogador['is_host'] == 1);
    
    if ($sala['id_pergunta_atual'] == null) {
        if ($eh_host) {
            criarNovaPergunta($conn, $id_sala, $sala);
            
            $stmt = $conn->prepare("
                SELECT rodadas, rodada_atual, id_categoria, id_pergunta_atual, 
                       alternativas_ordem, tempo_inicio_pergunta, tempo_resposta 
                FROM salas 
                WHERE id_sala = ?
            ");
            $stmt->bind_param("i", $id_sala);
            $stmt->execute();
            $result = $stmt->get_result();
            $sala = $result->fetch_assoc();
        } else {
            echo json_encode(["status" => "aguardando"], JSON_UNESCAPED_UNICODE);
            return;
        }
    }
    
    $stmt = $conn->prepare("SELECT * FROM perguntas WHERE id_pergunta = ?");
    $stmt->bind_param("i", $sala['id_pergunta_atual']);
    $stmt->execute();
    $result = $stmt->get_result();
    $pergunta = $result->fetch_assoc();
    
    if (!$pergunta) {
        echo json_encode(["status" => "erro", "mensagem" => "Pergunta nao encontrada"], JSON_UNESCAPED_UNICODE);
        return;
    }
    
    $alternativas = json_decode($sala['alternativas_ordem'], true);
    $resposta_correta = array_search($pergunta['alternativa1'], $alternativas) + 1;
    
    $tempo_inicio = strtotime($sala['tempo_inicio_pergunta']);
    $tempo_atual = time();
    $tempo_decorrido = $tempo_atual - $tempo_inicio;
    $tempo_restante = max(0, $sala['tempo_resposta'] - $tempo_decorrido);
    
    $stmt = $conn->prepare("SELECT id_resposta FROM respostas WHERE id_jogador = ? AND id_pergunta = ?");
    $stmt->bind_param("ii", $id_jogador, $sala['id_pergunta_atual']);
    $stmt->execute();
    $result = $stmt->get_result();
    $ja_respondeu = ($result->num_rows > 0);
    
    echo json_encode([
        "status" => "ok",
        "pergunta" => [
            "numero" => $sala['rodada_atual'],
            "total" => $sala['rodadas'],
            "pergunta" => $pergunta['pergunta'],
            "alternativas" => $alternativas,
            "resposta_correta" => $resposta_correta,
            "tempo_restante" => $tempo_restante,
            "tempo_total" => $sala['tempo_resposta'],
            "ja_respondeu" => $ja_respondeu
        ]
    ], JSON_UNESCAPED_UNICODE);
}

function criarNovaPergunta($conn, $id_sala, $sala) {
    $nova_rodada = $sala['rodada_atual'] + 1;
    
    if ($nova_rodada > $sala['rodadas']) {
        return;
    }
    
    $stmt = $conn->prepare("
        SELECT * FROM perguntas 
        WHERE id_categoria = ? 
        ORDER BY RAND() 
        LIMIT 1
    ");
    $stmt->bind_param("i", $sala['id_categoria']);
    $stmt->execute();
    $result = $stmt->get_result();
    $pergunta = $result->fetch_assoc();
    
    if (!$pergunta) {
        return;
    }
    
    $alternativas = [
        $pergunta['alternativa1'],
        $pergunta['alternativa2'],
        $pergunta['alternativa3'],
        $pergunta['alternativa4']
    ];
    
    shuffle($alternativas);
    $ordem_json = json_encode($alternativas, JSON_UNESCAPED_UNICODE);
    
    $stmt = $conn->prepare("
        UPDATE salas 
        SET rodada_atual = ?, 
            id_pergunta_atual = ?, 
            alternativas_ordem = ?, 
            tempo_inicio_pergunta = NOW() 
        WHERE id_sala = ?
    ");
    $stmt->bind_param("iisi", $nova_rodada, $pergunta['id_pergunta'], $ordem_json, $id_sala);
    $stmt->execute();
}

function processarResposta($conn, $id_sala, $id_jogador, $alternativa_escolhida, $tempo_clique) {
    $stmt = $conn->prepare("
        SELECT id_pergunta_atual, alternativas_ordem, tempo_resposta, tempo_inicio_pergunta 
        FROM salas 
        WHERE id_sala = ?
    ");
    $stmt->bind_param("i", $id_sala);
    $stmt->execute();
    $result = $stmt->get_result();
    $sala = $result->fetch_assoc();
    
    if (!$sala['id_pergunta_atual']) {
        echo json_encode(["status" => "erro", "mensagem" => "Nenhuma pergunta ativa"], JSON_UNESCAPED_UNICODE);
        return;
    }
    
    $stmt = $conn->prepare("SELECT id_resposta FROM respostas WHERE id_jogador = ? AND id_pergunta = ?");
    $stmt->bind_param("ii", $id_jogador, $sala['id_pergunta_atual']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        echo json_encode(["status" => "erro", "mensagem" => "Voce ja respondeu esta pergunta"], JSON_UNESCAPED_UNICODE);
        return;
    }
    
    $stmt = $conn->prepare("SELECT alternativa1 FROM perguntas WHERE id_pergunta = ?");
    $stmt->bind_param("i", $sala['id_pergunta_atual']);
    $stmt->execute();
    $result = $stmt->get_result();
    $pergunta = $result->fetch_assoc();
    
    $alternativas = json_decode($sala['alternativas_ordem'], true);
    $resposta_correta = array_search($pergunta['alternativa1'], $alternativas) + 1;
    $acertou = ($alternativa_escolhida == $resposta_correta && $alternativa_escolhida > 0);
    
    $pontos = 0;
    if ($acertou) {
        $pontos = max(100, intval(1000 - ($tempo_clique * 50)));
    }
    
    $letras = ['', 'A', 'B', 'C', 'D'];
    $resposta_texto = $alternativa_escolhida > 0 ? $letras[$alternativa_escolhida] : '';
    
    $stmt = $conn->prepare("
        INSERT INTO respostas (id_jogador, id_pergunta, resposta_escolhida, correta, tempo_resposta) 
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("iisii", $id_jogador, $sala['id_pergunta_atual'], $resposta_texto, $acertou, $tempo_clique);
    $stmt->execute();
    
    if ($pontos > 0) {
        $stmt = $conn->prepare("UPDATE jogadores SET pontos = pontos + ? WHERE id_jogador = ?");
        $stmt->bind_param("ii", $pontos, $id_jogador);
        $stmt->execute();
    }
    
    echo json_encode([
        "status" => "ok",
        "mensagem" => "Resposta registrada"
    ], JSON_UNESCAPED_UNICODE);
}

function verificarResultado($conn, $id_sala, $id_jogador) {
    $stmt = $conn->prepare("
        SELECT id_pergunta_atual, alternativas_ordem, tempo_resposta, tempo_inicio_pergunta 
        FROM salas 
        WHERE id_sala = ?
    ");
    $stmt->bind_param("i", $id_sala);
    $stmt->execute();
    $result = $stmt->get_result();
    $sala = $result->fetch_assoc();
    
    if (!$sala['id_pergunta_atual']) {
        echo json_encode(["status" => "erro", "mensagem" => "Nenhuma pergunta ativa"], JSON_UNESCAPED_UNICODE);
        return;
    }
    
    $tempo_inicio = strtotime($sala['tempo_inicio_pergunta']);
    $tempo_atual = time();
    $tempo_decorrido = $tempo_atual - $tempo_inicio;
    $tempo_acabou = $tempo_decorrido >= $sala['tempo_resposta'];
    
    if (!$tempo_acabou) {
        echo json_encode([
            "status" => "aguardando",
            "tempo_restante" => $sala['tempo_resposta'] - $tempo_decorrido
        ], JSON_UNESCAPED_UNICODE);
        return;
    }
    
    $stmt = $conn->prepare("SELECT alternativa1 FROM perguntas WHERE id_pergunta = ?");
    $stmt->bind_param("i", $sala['id_pergunta_atual']);
    $stmt->execute();
    $result = $stmt->get_result();
    $pergunta = $result->fetch_assoc();
    
    $alternativas = json_decode($sala['alternativas_ordem'], true);
    $resposta_correta = array_search($pergunta['alternativa1'], $alternativas) + 1;
    
    $stmt = $conn->prepare("
        SELECT resposta_escolhida, correta, tempo_resposta 
        FROM respostas 
        WHERE id_jogador = ? AND id_pergunta = ?
    ");
    $stmt->bind_param("ii", $id_jogador, $sala['id_pergunta_atual']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $resposta = $result->fetch_assoc();
        
        $letras = ['A' => 1, 'B' => 2, 'C' => 3, 'D' => 4];
        $alternativa_escolhida = $resposta['resposta_escolhida'] != '' ? $letras[$resposta['resposta_escolhida']] : 0;
        
        $pontos = 0;
        if ($resposta['correta']) {
            $pontos = max(100, intval(1000 - ($resposta['tempo_resposta'] * 50)));
        }
        
        echo json_encode([
            "status" => "ok",
            "tempo_acabou" => true,
            "acertou" => $resposta['correta'] == 1,
            "pontos" => $pontos,
            "resposta_correta" => $resposta_correta,
            "alternativa_escolhida" => $alternativa_escolhida
        ], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode([
            "status" => "ok",
            "tempo_acabou" => true,
            "acertou" => false,
            "pontos" => 0,
            "resposta_correta" => $resposta_correta,
            "alternativa_escolhida" => 0
        ], JSON_UNESCAPED_UNICODE);
    }
}

function limparPergunta($conn, $id_sala, $id_jogador) {
    $stmt = $conn->prepare("SELECT is_host FROM jogadores WHERE id_jogador = ? AND id_sala = ?");
    $stmt->bind_param("ii", $id_jogador, $id_sala);
    $stmt->execute();
    $result = $stmt->get_result();
    $jogador = $result->fetch_assoc();
    
    if ($jogador['is_host'] != 1) {
        echo json_encode(["status" => "erro", "mensagem" => "Apenas o host pode avancar"], JSON_UNESCAPED_UNICODE);
        return;
    }
    
    $stmt = $conn->prepare("
        UPDATE salas 
        SET id_pergunta_atual = NULL, 
            alternativas_ordem = NULL, 
            tempo_inicio_pergunta = NULL 
        WHERE id_sala = ?
    ");
    $stmt->bind_param("i", $id_sala);
    $stmt->execute();
    
    echo json_encode(["status" => "ok"], JSON_UNESCAPED_UNICODE);
}
?>