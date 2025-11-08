<?php
session_start();
include "../db/conexao.php";

$acao = $_POST['acao'] ?? '';
$codigo_sala = $_POST['codigo_sala'] ?? '';
$id_jogador = $_POST['id_jogador'] ?? 0;

if (empty($codigo_sala) || empty($id_jogador)) {
    echo 'ERRO|Dados invalidos';
    exit;
}

// Buscar sala
$stmt = $conn->prepare("SELECT id_sala FROM salas WHERE codigo_sala = ?");
$stmt->bind_param("s", $codigo_sala);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo 'ERRO|Sala nao encontrada';
    exit;
}

$sala_data = $result->fetch_assoc();
$id_sala = $sala_data['id_sala'];

// ============= CARREGAR PERGUNTA =============
if ($acao === 'carregar_pergunta') {
    $stmt = $conn->prepare("
        SELECT s.*, c.nome_categoria 
        FROM salas s 
        JOIN categorias c ON s.id_categoria = c.id_categoria 
        WHERE s.id_sala = ?
    ");
    $stmt->bind_param("i", $id_sala);
    $stmt->execute();
    $result = $stmt->get_result();
    $sala = $result->fetch_assoc();
    
    // Verificar se jogo acabou
    if ($sala['rodada_atual'] > $sala['rodadas']) {
        echo 'FIM';
        exit;
    }
    
    // Verificar se é host
    $stmt = $conn->prepare("SELECT is_host FROM jogadores WHERE id_jogador = ? AND id_sala = ?");
    $stmt->bind_param("ii", $id_jogador, $id_sala);
    $stmt->execute();
    $result = $stmt->get_result();
    $jogador = $result->fetch_assoc();
    $eh_host = ($jogador['is_host'] == 1);
    
    // Se não há pergunta, criar (host)
    if ($sala['id_pergunta_atual'] == null) {
        if ($eh_host) {
            $nova_rodada = $sala['rodada_atual'] + 1;
            
            if ($nova_rodada <= $sala['rodadas']) {
                $stmt = $conn->prepare("SELECT * FROM perguntas WHERE id_categoria = ? ORDER BY RAND() LIMIT 1");
                $stmt->bind_param("i", $sala['id_categoria']);
                $stmt->execute();
                $result = $stmt->get_result();
                $pergunta = $result->fetch_assoc();
                
                if ($pergunta) {
                    $alternativas = [
                        $pergunta['alternativa1'],
                        $pergunta['alternativa2'],
                        $pergunta['alternativa3'],
                        $pergunta['alternativa4']
                    ];
                    shuffle($alternativas);
                    $ordem_json = json_encode($alternativas);
                    
                    $stmt = $conn->prepare("UPDATE salas SET rodada_atual = ?, id_pergunta_atual = ?, alternativas_ordem = ?, tempo_inicio_pergunta = NOW() WHERE id_sala = ?");
                    $stmt->bind_param("iisi", $nova_rodada, $pergunta['id_pergunta'], $ordem_json, $id_sala);
                    $stmt->execute();
                    
                    // Recarregar sala
                    $stmt = $conn->prepare("SELECT s.* FROM salas s WHERE s.id_sala = ?");
                    $stmt->bind_param("i", $id_sala);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $sala = $result->fetch_assoc();
                }
            }
        } else {
            echo 'AGUARDANDO';
            exit;
        }
    }
    
    if (!$sala['id_pergunta_atual']) {
        echo 'AGUARDANDO';
        exit;
    }
    
    // Buscar pergunta
    $stmt = $conn->prepare("SELECT * FROM perguntas WHERE id_pergunta = ?");
    $stmt->bind_param("i", $sala['id_pergunta_atual']);
    $stmt->execute();
    $result = $stmt->get_result();
    $pergunta = $result->fetch_assoc();
    
    // Decodificar alternativas
    $alternativas = json_decode($sala['alternativas_ordem'], true);
    $resposta_correta = array_search($pergunta['alternativa1'], $alternativas) + 1;
    
    // Calcular tempo
    $tempo_inicio = strtotime($sala['tempo_inicio_pergunta']);
    $tempo_atual = time();
    $tempo_decorrido = $tempo_atual - $tempo_inicio;
    $tempo_restante = max(0, $sala['tempo_resposta'] - $tempo_decorrido);
    
    // Verificar se já respondeu
    $stmt = $conn->prepare("SELECT id_resposta FROM respostas WHERE id_jogador = ? AND id_pergunta = ?");
    $stmt->bind_param("ii", $id_jogador, $sala['id_pergunta_atual']);
    $stmt->execute();
    $result = $stmt->get_result();
    $ja_respondeu = ($result->num_rows > 0) ? '1' : '0';
    
    // Formato: OK|numero|total|pergunta|alt1|alt2|alt3|alt4|tempo_restante|tempo_total|resposta_correta|ja_respondeu
    echo 'OK|' . $sala['rodada_atual'] . '|' . $sala['rodadas'] . '|' . 
         htmlspecialchars($pergunta['pergunta']) . '|' . 
         htmlspecialchars($alternativas[0]) . '|' . 
         htmlspecialchars($alternativas[1]) . '|' . 
         htmlspecialchars($alternativas[2]) . '|' . 
         htmlspecialchars($alternativas[3]) . '|' . 
         $tempo_restante . '|' . 
         $sala['tempo_resposta'] . '|' . 
         $resposta_correta . '|' . 
         $ja_respondeu;
    exit;
}

// ============= RESPONDER =============
if ($acao === 'responder') {
    $alternativa = $_POST['alternativa'] ?? 0;
    $tempo_clique = $_POST['tempo_clique'] ?? 0;
    
    $stmt = $conn->prepare("SELECT id_pergunta_atual, alternativas_ordem FROM salas WHERE id_sala = ?");
    $stmt->bind_param("i", $id_sala);
    $stmt->execute();
    $result = $stmt->get_result();
    $sala = $result->fetch_assoc();
    
    if (!$sala['id_pergunta_atual']) {
        echo 'ERRO|Nenhuma pergunta ativa';
        exit;
    }
    
    // Verificar se já respondeu
    $stmt = $conn->prepare("SELECT id_resposta FROM respostas WHERE id_jogador = ? AND id_pergunta = ?");
    $stmt->bind_param("ii", $id_jogador, $sala['id_pergunta_atual']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        echo 'ERRO|Ja respondeu';
        exit;
    }
    
    // Buscar resposta correta
    $stmt = $conn->prepare("SELECT alternativa1 FROM perguntas WHERE id_pergunta = ?");
    $stmt->bind_param("i", $sala['id_pergunta_atual']);
    $stmt->execute();
    $result = $stmt->get_result();
    $pergunta = $result->fetch_assoc();
    
    $alternativas = json_decode($sala['alternativas_ordem'], true);
    $resposta_correta = array_search($pergunta['alternativa1'], $alternativas) + 1;
    $acertou = ($alternativa == $resposta_correta && $alternativa > 0) ? 1 : 0;
    
    // Calcular pontos
    $pontos = 0;
    if ($acertou) {
        $pontos = max(100, intval(1000 - ($tempo_clique * 50)));
    }
    
    // Salvar resposta
    $letras = ['', 'A', 'B', 'C', 'D'];
    $resposta_texto = $alternativa > 0 ? $letras[$alternativa] : '';
    
    $stmt = $conn->prepare("INSERT INTO respostas (id_jogador, id_pergunta, resposta_escolhida, correta, tempo_resposta) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("iisii", $id_jogador, $sala['id_pergunta_atual'], $resposta_texto, $acertou, $tempo_clique);
    $stmt->execute();
    
    // Atualizar pontos
    if ($pontos > 0) {
        $stmt = $conn->prepare("UPDATE jogadores SET pontos = pontos + ? WHERE id_jogador = ?");
        $stmt->bind_param("ii", $pontos, $id_jogador);
        $stmt->execute();
    }
    
    echo 'OK';
    exit;
}

// ============= VERIFICAR RESULTADO =============
if ($acao === 'verificar_resultado') {
    $stmt = $conn->prepare("SELECT id_pergunta_atual, alternativas_ordem, tempo_resposta, tempo_inicio_pergunta FROM salas WHERE id_sala = ?");
    $stmt->bind_param("i", $id_sala);
    $stmt->execute();
    $result = $stmt->get_result();
    $sala = $result->fetch_assoc();
    
    if (!$sala['id_pergunta_atual']) {
        echo 'ERRO|Nenhuma pergunta ativa';
        exit;
    }
    
    // Verificar tempo
    $tempo_inicio = strtotime($sala['tempo_inicio_pergunta']);
    $tempo_atual = time();
    $tempo_decorrido = $tempo_atual - $tempo_inicio;
    $tempo_acabou = $tempo_decorrido >= $sala['tempo_resposta'];
    
    if (!$tempo_acabou) {
        echo 'AGUARDANDO';
        exit;
    }
    
    // Buscar resposta correta
    $stmt = $conn->prepare("SELECT alternativa1 FROM perguntas WHERE id_pergunta = ?");
    $stmt->bind_param("i", $sala['id_pergunta_atual']);
    $stmt->execute();
    $result = $stmt->get_result();
    $pergunta = $result->fetch_assoc();
    
    $alternativas = json_decode($sala['alternativas_ordem'], true);
    $resposta_correta = array_search($pergunta['alternativa1'], $alternativas) + 1;
    
    // Buscar resposta do jogador
    $stmt = $conn->prepare("SELECT resposta_escolhida, correta, tempo_resposta FROM respostas WHERE id_jogador = ? AND id_pergunta = ?");
    $stmt->bind_param("ii", $id_jogador, $sala['id_pergunta_atual']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $acertou = 0;
    $pontos = 0;
    $alternativa_escolhida = 0;
    
    if ($result->num_rows > 0) {
        $resposta = $result->fetch_assoc();
        $letras = ['A' => 1, 'B' => 2, 'C' => 3, 'D' => 4];
        $alternativa_escolhida = isset($letras[$resposta['resposta_escolhida']]) ? $letras[$resposta['resposta_escolhida']] : 0;
        $acertou = $resposta['correta'];
        if ($acertou) {
            $pontos = max(100, intval(1000 - ($resposta['tempo_resposta'] * 50)));
        }
    }
    
    // Formato: RESULTADO|acertou|pontos|resposta_correta|alternativa_escolhida
    echo 'RESULTADO|' . $acertou . '|' . $pontos . '|' . $resposta_correta . '|' . $alternativa_escolhida;
    exit;
}

// ============= LIMPAR PERGUNTA =============
if ($acao === 'limpar_pergunta') {
    $stmt = $conn->prepare("SELECT is_host FROM jogadores WHERE id_jogador = ? AND id_sala = ?");
    $stmt->bind_param("ii", $id_jogador, $id_sala);
    $stmt->execute();
    $result = $stmt->get_result();
    $jogador = $result->fetch_assoc();
    
    if ($jogador['is_host'] != 1) {
        echo 'ERRO|Apenas host pode avancar';
        exit;
    }
    
    $stmt = $conn->prepare("UPDATE salas SET id_pergunta_atual = NULL, alternativas_ordem = NULL, tempo_inicio_pergunta = NULL WHERE id_sala = ?");
    $stmt->bind_param("i", $id_sala);
    $stmt->execute();
    
    echo 'OK';
    exit;
}

echo 'ERRO|Acao desconhecida';
?>