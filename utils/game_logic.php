<?php
session_start();
header("Content-Type: application/json; charset=utf-8");
include_once __DIR__ . "/../db/conexao.php";

// Constantes para pontuação
define('PONTOS_MAX', 1000);
define('PONTOS_MIN', 0);

function resposta_json($arr) {
    echo json_encode($arr, JSON_UNESCAPED_UNICODE);
    exit;
}

function calcular_pontos($tempo_resposta, $tempo_total) {
    // Quanto mais rápido responder, mais pontos
    // 0 segundos = 1000 pontos, tempo_total segundos = 0 pontos
    $porcentagem_tempo = $tempo_resposta / $tempo_total;
    $pontos = PONTOS_MAX * (1 - $porcentagem_tempo);
    return max(PONTOS_MIN, intval($pontos));
}

$acao = $_POST['acao'] ?? $_GET['acao'] ?? '';

switch ($acao) {
    case 'iniciar_proxima':
        iniciar_proxima($conn);
        break;
    case 'buscar_pergunta':
        buscar_pergunta($conn);
        break;
    case 'enviar_resposta':
        enviar_resposta($conn);
        break;
    case 'verificar_tempo':
        verificar_tempo($conn);
        break;
    default:
        resposta_json(["status" => "erro", "mensagem" => "Ação inválida"]);
}

// HOST INICIA A PRÓXIMA PERGUNTA
function iniciar_proxima($conn) {
    $codigo_sala = $_POST['codigo_sala'] ?? '';
    $id_jogador = $_POST['id_jogador'] ?? 0;

    if (empty($codigo_sala) || !$id_jogador) {
        resposta_json(["status" => "erro", "mensagem" => "Dados incompletos"]);
    }

    // Buscar sala
    $stmt = $conn->prepare("SELECT * FROM salas WHERE codigo_sala = ?");
    $stmt->bind_param("s", $codigo_sala);
    $stmt->execute();
    $sala = $stmt->get_result()->fetch_assoc();

    if (!$sala) {
        resposta_json(["status" => "erro", "mensagem" => "Sala não encontrada"]);
    }

    // Verificar se é o host
    $stmt = $conn->prepare("SELECT is_host FROM jogadores WHERE id_jogador = ? AND id_sala = ?");
    $stmt->bind_param("ii", $id_jogador, $sala['id_sala']);
    $stmt->execute();
    $jogador = $stmt->get_result()->fetch_assoc();

    if (!$jogador || $jogador['is_host'] != 1) {
        resposta_json(["status" => "erro", "mensagem" => "Apenas o host pode iniciar"]);
    }

    // Verificar se acabaram as rodadas
    if ($sala['rodada_atual'] >= $sala['rodadas']) {
        resposta_json(["status" => "fim_jogo"]);
    }

    // Incrementar rodada
    $nova_rodada = $sala['rodada_atual'] + 1;

    // Buscar uma pergunta aleatória da categoria
    $stmt = $conn->prepare("
        SELECT id_pergunta, pergunta, alternativa1, alternativa2, alternativa3, alternativa4 
        FROM perguntas 
        WHERE id_categoria = ? 
        ORDER BY RAND() 
        LIMIT 1
    ");
    $stmt->bind_param("i", $sala['id_categoria']);
    $stmt->execute();
    $pergunta = $stmt->get_result()->fetch_assoc();

    if (!$pergunta) {
        resposta_json(["status" => "erro", "mensagem" => "Nenhuma pergunta disponível"]);
    }

    // Embaralhar alternativas
    $alternativas = [
        $pergunta['alternativa1'],
        $pergunta['alternativa2'],
        $pergunta['alternativa3'],
        $pergunta['alternativa4']
    ];
    
    // A primeira alternativa é sempre a correta no banco
    $resposta_correta = $alternativas[0];
    
    // Embaralhar
    shuffle($alternativas);
    
    // Descobrir nova posição da correta
    $nova_posicao_correta = array_search($resposta_correta, $alternativas) + 1;

    // Salvar ordem embaralhada
    $ordem_json = json_encode($alternativas);

    // Atualizar sala com nova pergunta e reiniciar timer
    $stmt = $conn->prepare("
        UPDATE salas 
        SET rodada_atual = ?, 
            id_pergunta_atual = ?, 
            tempo_inicio_pergunta = NOW(), 
            alternativas_ordem = ?
        WHERE id_sala = ?
    ");
    $stmt->bind_param("iisi", $nova_rodada, $pergunta['id_pergunta'], $ordem_json, $sala['id_sala']);
    
    if ($stmt->execute()) {
        resposta_json([
            "status" => "ok",
            "mensagem" => "Pergunta iniciada",
            "rodada_atual" => $nova_rodada,
            "posicao_correta" => $nova_posicao_correta // Para salvar no banco depois
        ]);
    } else {
        resposta_json(["status" => "erro", "mensagem" => "Erro ao iniciar pergunta"]);
    }
}

// JOGADORES BUSCAM A PERGUNTA ATUAL
function buscar_pergunta($conn) {
    $codigo_sala = $_POST['codigo_sala'] ?? $_GET['codigo_sala'] ?? '';
    $id_jogador = $_POST['id_jogador'] ?? $_GET['id_jogador'] ?? 0;

    if (empty($codigo_sala)) {
        resposta_json(["status" => "erro", "mensagem" => "Código da sala obrigatório"]);
    }

    // Buscar sala
    $stmt = $conn->prepare("SELECT * FROM salas WHERE codigo_sala = ?");
    $stmt->bind_param("s", $codigo_sala);
    $stmt->execute();
    $sala = $stmt->get_result()->fetch_assoc();

    if (!$sala) {
        resposta_json(["status" => "erro", "mensagem" => "Sala não encontrada"]);
    }

    // Se não há pergunta iniciada ainda
    if (!$sala['id_pergunta_atual'] || !$sala['tempo_inicio_pergunta']) {
        resposta_json([
            "status" => "aguardando",
            "mensagem" => "Aguardando host iniciar a pergunta"
        ]);
    }

    // Buscar pergunta
    $stmt = $conn->prepare("SELECT pergunta FROM perguntas WHERE id_pergunta = ?");
    $stmt->bind_param("i", $sala['id_pergunta_atual']);
    $stmt->execute();
    $pergunta = $stmt->get_result()->fetch_assoc();

    if (!$pergunta) {
        resposta_json(["status" => "erro", "mensagem" => "Pergunta não encontrada"]);
    }

    // Calcular tempo restante
    $stmt = $conn->prepare("
        SELECT 
            UNIX_TIMESTAMP(tempo_inicio_pergunta) as inicio,
            UNIX_TIMESTAMP(NOW()) as agora
        FROM salas WHERE id_sala = ?
    ");
    $stmt->bind_param("i", $sala['id_sala']);
    $stmt->execute();
    $tempos = $stmt->get_result()->fetch_assoc();

    $tempo_decorrido = $tempos['agora'] - $tempos['inicio'];
    $tempo_restante = $sala['tempo_resposta'] - $tempo_decorrido;
    
    if ($tempo_restante < 0) $tempo_restante = 0;

    // Verificar se jogador já respondeu
    $ja_respondeu = false;
    if ($id_jogador) {
        $stmt = $conn->prepare("
            SELECT id_resposta 
            FROM respostas 
            WHERE id_jogador = ? AND id_pergunta = ?
        ");
        $stmt->bind_param("ii", $id_jogador, $sala['id_pergunta_atual']);
        $stmt->execute();
        $ja_respondeu = $stmt->get_result()->num_rows > 0;
    }

    // Pegar alternativas embaralhadas
    $alternativas = json_decode($sala['alternativas_ordem'], true);

    resposta_json([
        "status" => "ok",
        "rodada_atual" => intval($sala['rodada_atual']),
        "total_rodadas" => intval($sala['rodadas']),
        "pergunta" => $pergunta['pergunta'],
        "alternativas" => $alternativas,
        "tempo_total" => intval($sala['tempo_resposta']),
        "tempo_restante" => intval($tempo_restante),
        "timestamp_inicio" => intval($tempos['inicio']),
        "timestamp_servidor" => intval($tempos['agora']),
        "ja_respondeu" => $ja_respondeu
    ]);
}

// JOGADOR ENVIA RESPOSTA
function enviar_resposta($conn) {
    $codigo_sala = $_POST['codigo_sala'] ?? '';
    $id_jogador = $_POST['id_jogador'] ?? 0;
    $alternativa = intval($_POST['alternativa'] ?? 0); // 1, 2, 3 ou 4

    if (empty($codigo_sala) || !$id_jogador || !$alternativa) {
        resposta_json(["status" => "erro", "mensagem" => "Dados incompletos"]);
    }

    // Buscar sala
    $stmt = $conn->prepare("SELECT * FROM salas WHERE codigo_sala = ?");
    $stmt->bind_param("s", $codigo_sala);
    $stmt->execute();
    $sala = $stmt->get_result()->fetch_assoc();

    if (!$sala) {
        resposta_json(["status" => "erro", "mensagem" => "Sala não encontrada"]);
    }

    if (!$sala['id_pergunta_atual']) {
        resposta_json(["status" => "erro", "mensagem" => "Nenhuma pergunta ativa"]);
    }

    // Calcular tempo de resposta
    $stmt = $conn->prepare("
        SELECT 
            UNIX_TIMESTAMP(tempo_inicio_pergunta) as inicio,
            UNIX_TIMESTAMP(NOW()) as agora
        FROM salas WHERE id_sala = ?
    ");
    $stmt->bind_param("i", $sala['id_sala']);
    $stmt->execute();
    $tempos = $stmt->get_result()->fetch_assoc();

    $tempo_resposta = $tempos['agora'] - $tempos['inicio'];

    // Verificar se tempo não expirou
    if ($tempo_resposta > $sala['tempo_resposta']) {
        resposta_json(["status" => "erro", "mensagem" => "Tempo esgotado"]);
    }

    // Verificar se já respondeu
    $stmt = $conn->prepare("
        SELECT id_resposta 
        FROM respostas 
        WHERE id_jogador = ? AND id_pergunta = ?
    ");
    $stmt->bind_param("ii", $id_jogador, $sala['id_pergunta_atual']);
    $stmt->execute();
    
    if ($stmt->get_result()->num_rows > 0) {
        resposta_json(["status" => "erro", "mensagem" => "Você já respondeu esta pergunta"]);
    }

    // Buscar resposta correta da pergunta
    $stmt = $conn->prepare("
        SELECT alternativa1 
        FROM perguntas 
        WHERE id_pergunta = ?
    ");
    $stmt->bind_param("i", $sala['id_pergunta_atual']);
    $stmt->execute();
    $pergunta = $stmt->get_result()->fetch_assoc();

    // Pegar alternativas embaralhadas
    $alternativas = json_decode($sala['alternativas_ordem'], true);
    
    // A alternativa correta é a alternativa1 do banco
    // Descobrir em que posição ela está no array embaralhado
    $posicao_correta = array_search($pergunta['alternativa1'], $alternativas) + 1;

    // Verificar se acertou
    $acertou = ($alternativa == $posicao_correta);

    // Calcular pontos
    $pontos = 0;
    if ($acertou) {
        $pontos = calcular_pontos($tempo_resposta, $sala['tempo_resposta']);
    }

    // Salvar resposta
    $stmt = $conn->prepare("
        INSERT INTO respostas (id_jogador, id_pergunta, resposta_escolhida, correta, tempo_resposta) 
        VALUES (?, ?, ?, ?, ?)
    ");
    $resposta_escolhida = strval($alternativa);
    $stmt->bind_param("iisii", $id_jogador, $sala['id_pergunta_atual'], $resposta_escolhida, $acertou, $tempo_resposta);
    $stmt->execute();

    // Atualizar pontos do jogador
    if ($acertou) {
        $stmt = $conn->prepare("
            UPDATE jogadores 
            SET pontos = pontos + ? 
            WHERE id_jogador = ?
        ");
        $stmt->bind_param("ii", $pontos, $id_jogador);
        $stmt->execute();
    }

    resposta_json([
        "status" => "ok",
        "mensagem" => "Resposta registrada",
        "tempo_resposta" => $tempo_resposta
    ]);
}

// VERIFICAR SE O TEMPO ACABOU
function verificar_tempo($conn) {
    $codigo_sala = $_POST['codigo_sala'] ?? $_GET['codigo_sala'] ?? '';

    if (empty($codigo_sala)) {
        resposta_json(["status" => "erro", "mensagem" => "Código da sala obrigatório"]);
    }

    // Buscar sala
    $stmt = $conn->prepare("SELECT * FROM salas WHERE codigo_sala = ?");
    $stmt->bind_param("s", $codigo_sala);
    $stmt->execute();
    $sala = $stmt->get_result()->fetch_assoc();

    if (!$sala) {
        resposta_json(["status" => "erro", "mensagem" => "Sala não encontrada"]);
    }

    if (!$sala['tempo_inicio_pergunta']) {
        resposta_json([
            "status" => "aguardando",
            "tempo_restante" => 0
        ]);
    }

    // Calcular tempo restante
    $stmt = $conn->prepare("
        SELECT 
            UNIX_TIMESTAMP(tempo_inicio_pergunta) as inicio,
            UNIX_TIMESTAMP(NOW()) as agora
        FROM salas WHERE id_sala = ?
    ");
    $stmt->bind_param("i", $sala['id_sala']);
    $stmt->execute();
    $tempos = $stmt->get_result()->fetch_assoc();

    $tempo_decorrido = $tempos['agora'] - $tempos['inicio'];
    $tempo_restante = $sala['tempo_resposta'] - $tempo_decorrido;
    
    if ($tempo_restante < 0) $tempo_restante = 0;

    if ($tempo_restante == 0) {
        // Tempo acabou - enviar resposta correta
        $stmt = $conn->prepare("
            SELECT alternativa1 
            FROM perguntas 
            WHERE id_pergunta = ?
        ");
        $stmt->bind_param("i", $sala['id_pergunta_atual']);
        $stmt->execute();
        $pergunta = $stmt->get_result()->fetch_assoc();

        // Pegar alternativas embaralhadas
        $alternativas = json_decode($sala['alternativas_ordem'], true);
        
        // Descobrir posição da correta
        $posicao_correta = array_search($pergunta['alternativa1'], $alternativas) + 1;

        resposta_json([
            "status" => "tempo_acabou",
            "tempo_restante" => 0,
            "resposta_correta" => $posicao_correta
        ]);
    } else {
        resposta_json([
            "status" => "andamento",
            "tempo_restante" => intval($tempo_restante),
            "timestamp_servidor" => intval($tempos['agora'])
        ]);
    }
}
?>