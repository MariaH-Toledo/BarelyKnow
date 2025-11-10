<?php
session_start();
header("Content-Type: application/json; charset=utf-8");

include "../db/conexao.php";

function resposta_json($arr) {
    echo json_encode($arr, JSON_UNESCAPED_UNICODE);
    exit;
}

$acao = $_POST['acao'] ?? '';
$codigo_sala = $_POST['codigo_sala'] ?? '';
$id_jogador = intval($_POST['id_jogador'] ?? 0);

if (empty($codigo_sala) || $id_jogador <= 0) {
    resposta_json(["erro" => "Dados inválidos"]);
}

$stmt = $conn->prepare("SELECT id_sala FROM salas WHERE codigo_sala = ?");
$stmt->bind_param("s", $codigo_sala);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    resposta_json(["erro" => "Sala não encontrada"]);
}

$salaRow = $result->fetch_assoc();
$id_sala = intval($salaRow['id_sala']);

switch ($acao) {
    case 'buscar_pergunta':
        buscarPergunta($conn, $id_sala, $id_jogador);
        break;
    case 'enviar_resposta':
        $alternativa = intval($_POST['alternativa'] ?? 0);
        enviarResposta($conn, $id_sala, $id_jogador, $alternativa);
        break;
    case 'verificar_tempo':
        verificarTempo($conn, $id_sala, $id_jogador);
        break;
    case 'proxima_pergunta':
        proximaPergunta($conn, $id_sala, $id_jogador);
        break;
    default:
        resposta_json(["erro" => "Ação desconhecida"]);
}

function buscarPergunta($conn, $id_sala, $id_jogador) {
    $stmt = $conn->prepare("
        SELECT rodadas, rodada_atual, id_categoria, id_pergunta_atual, alternativas_ordem, tempo_inicio_pergunta, tempo_resposta, status
        FROM salas
        WHERE id_sala = ?
    ");
    $stmt->bind_param("i", $id_sala);
    $stmt->execute();
    $sala = $stmt->get_result()->fetch_assoc();

    if (!$sala) resposta_json(["erro" => "Sala inválida"]);

    $rodada_atual = intval($sala['rodada_atual']);
    $rodadas = intval($sala['rodadas']);
    if ($rodada_atual > $rodadas) {
        resposta_json(["status" => "fim_jogo"]);
    }

    $stmt = $conn->prepare("SELECT is_host FROM jogadores WHERE id_jogador = ? AND id_sala = ?");
    $stmt->bind_param("ii", $id_jogador, $id_sala);
    $stmt->execute();
    $jog = $stmt->get_result()->fetch_assoc();
    $eh_host = ($jog && intval($jog['is_host']) === 1);

    if (is_null($sala['id_pergunta_atual'])) {
        if (!$eh_host) {
            resposta_json(["status" => "aguardando"]);
        }

        $conn->begin_transaction();
        try {
            $qry = $conn->prepare("SELECT id_pergunta_atual, rodada_atual, rodadas, id_categoria, tempo_resposta FROM salas WHERE id_sala = ? FOR UPDATE");
            $qry->bind_param("i", $id_sala);
            $qry->execute();
            $lockedSala = $qry->get_result()->fetch_assoc();

            if (is_null($lockedSala['id_pergunta_atual'])) {
                $rodada_atual_locked = intval($lockedSala['rodada_atual']);
                $rodadas_locked = intval($lockedSala['rodadas']);
                if ($rodada_atual_locked >= $rodadas_locked) {
                    $conn->commit();
                    resposta_json(["status" => "fim_jogo"]);
                }

                $id_categoria = intval($lockedSala['id_categoria']);
                $stmtP = $conn->prepare("SELECT * FROM perguntas WHERE id_categoria = ? ORDER BY RAND() LIMIT 1");
                $stmtP->bind_param("i", $id_categoria);
                $stmtP->execute();
                $pergunta = $stmtP->get_result()->fetch_assoc();

                if (!$pergunta) {
                    $conn->commit();
                    resposta_json(["erro" => "Sem perguntas na categoria"]);
                }

                $alternativas = [
                    $pergunta['alternativa1'],
                    $pergunta['alternativa2'],
                    $pergunta['alternativa3'],
                    $pergunta['alternativa4']
                ];
                shuffle($alternativas);
                $ordem_json = json_encode($alternativas, JSON_UNESCAPED_UNICODE);

                $nova_rodada = $rodada_atual_locked + 1;
                $tempo_resposta = intval($lockedSala['tempo_resposta']);

                $upd = $conn->prepare("
                    UPDATE salas
                    SET rodada_atual = ?, id_pergunta_atual = ?, alternativas_ordem = ?, tempo_inicio_pergunta = NOW(), tempo_resposta = ?
                    WHERE id_sala = ?
                ");
                $upd->bind_param("iissi", $nova_rodada, $pergunta['id_pergunta'], $ordem_json, $tempo_resposta, $id_sala);
                $upd->execute();
            }
            $conn->commit();
        } catch (Exception $e) {
            $conn->rollback();
            resposta_json(["erro" => "Erro ao criar pergunta (lock): " . $e->getMessage()]);
        }

        $stmt = $conn->prepare("
            SELECT rodadas, rodada_atual, id_categoria, id_pergunta_atual, alternativas_ordem, tempo_inicio_pergunta, tempo_resposta
            FROM salas
            WHERE id_sala = ?
        ");
        $stmt->bind_param("i", $id_sala);
        $stmt->execute();
        $sala = $stmt->get_result()->fetch_assoc();
    }

    if (!$sala['id_pergunta_atual']) {
        resposta_json(["status" => "aguardando"]);
    }

    $id_pergunta = intval($sala['id_pergunta_atual']);
    $stmt = $conn->prepare("SELECT * FROM perguntas WHERE id_pergunta = ?");
    $stmt->bind_param("i", $id_pergunta);
    $stmt->execute();
    $pergunta = $stmt->get_result()->fetch_assoc();

    if (!$pergunta) resposta_json(["erro" => "Pergunta não encontrada"]);

    $alternativas = json_decode($sala['alternativas_ordem'], true);
    if (!is_array($alternativas) || count($alternativas) !== 4) {
        $alternativas = [
            $pergunta['alternativa1'],
            $pergunta['alternativa2'],
            $pergunta['alternativa3'],
            $pergunta['alternativa4']
        ];
    }

    $posicao_correta = array_search($pergunta['alternativa1'], $alternativas);
    $posicao_correta = ($posicao_correta === false) ? 1 : ($posicao_correta + 1);

    $tempo_inicio = $sala['tempo_inicio_pergunta'] ? strtotime($sala['tempo_inicio_pergunta']) : null;
    $tempo_total = intval($sala['tempo_resposta']);
    $timestamp_servidor = time();

    if ($tempo_inicio === null) {
        $tempo_passado = 0;
        $tempo_restante = $tempo_total;
        $timestamp_inicio = $timestamp_servidor;
    } else {
        $tempo_passado = max(0, $timestamp_servidor - $tempo_inicio);
        $tempo_restante = max(0, $tempo_total - $tempo_passado);
        $timestamp_inicio = $tempo_inicio;
    }

    $stmt = $conn->prepare("SELECT id_resposta FROM respostas WHERE id_jogador = ? AND id_pergunta = ?");
    $stmt->bind_param("ii", $id_jogador, $id_pergunta);
    $stmt->execute();
    $ja_respondeu = ($stmt->get_result()->num_rows > 0);

    resposta_json([
        "status" => "ok",
        "rodada_atual" => intval($sala['rodada_atual']),
        "total_rodadas" => intval($sala['rodadas']),
        "pergunta" => $pergunta['pergunta'],
        "alternativas" => $alternativas,
        "tempo_restante" => $tempo_restante,
        "tempo_total" => $tempo_total,
        "ja_respondeu" => $ja_respondeu,
        "resposta_correta" => $posicao_correta,
        "timestamp_inicio" => $timestamp_inicio,
        "timestamp_servidor" => $timestamp_servidor,
        "tempo_passado_servidor" => $tempo_passado
    ]);
}

function enviarResposta($conn, $id_sala, $id_jogador, $alternativa_escolhida) {
    if ($alternativa_escolhida < 1 || $alternativa_escolhida > 4) {
        resposta_json(["erro" => "Alternativa inválida"]);
    }

    $stmt = $conn->prepare("SELECT id_pergunta_atual, alternativas_ordem, tempo_inicio_pergunta, tempo_resposta FROM salas WHERE id_sala = ?");
    $stmt->bind_param("i", $id_sala);
    $stmt->execute();
    $sala = $stmt->get_result()->fetch_assoc();

    if (!$sala || !$sala['id_pergunta_atual']) {
        resposta_json(["erro" => "Nenhuma pergunta ativa"]);
    }

    $id_pergunta = intval($sala['id_pergunta_atual']);
    $tempo_inicio = $sala['tempo_inicio_pergunta'] ? strtotime($sala['tempo_inicio_pergunta']) : null;
    $tempo_total = intval($sala['tempo_resposta']);
    $now = time();

    if ($tempo_inicio === null) {
        resposta_json(["erro" => "Tempo de pergunta inválido"]);
    }

    $tempo_passado = max(0, $now - $tempo_inicio);
    if ($tempo_passado > $tempo_total) {
        resposta_json(["erro" => "Tempo esgotado para responder"]);
    }

    $stmt = $conn->prepare("SELECT id_resposta FROM respostas WHERE id_jogador = ? AND id_pergunta = ?");
    $stmt->bind_param("ii", $id_jogador, $id_pergunta);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        resposta_json(["erro" => "Você já respondeu"]);
    }

    $stmt = $conn->prepare("SELECT alternativa1 FROM perguntas WHERE id_pergunta = ?");
    $stmt->bind_param("i", $id_pergunta);
    $stmt->execute();
    $perg = $stmt->get_result()->fetch_assoc();
    if (!$perg) resposta_json(["erro" => "Pergunta não encontrada"]);

    $alternativas = json_decode($sala['alternativas_ordem'], true);
    if (!is_array($alternativas) || count($alternativas) !== 4) {
        $posicao_correta = 1;
    } else {
        $pos = array_search($perg['alternativa1'], $alternativas);
        $posicao_correta = ($pos === false) ? 1 : ($pos + 1);
    }

    $acertou = ($alternativa_escolhida === $posicao_correta) ? 1 : 0;

    $tempo_resposta_servidor = $tempo_passado;
    $pontos = 0;
    if ($acertou) {
        $pontos = max(100, 1000 - ($tempo_resposta_servidor * 50));
    }

    $letras = ['', 'A', 'B', 'C', 'D'];
    $letra_escolhida = $letras[$alternativa_escolhida];

    $stmt = $conn->prepare("
        INSERT INTO respostas (id_jogador, id_pergunta, resposta_escolhida, correta, tempo_resposta)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("iisii", $id_jogador, $id_pergunta, $letra_escolhida, $acertou, $tempo_resposta_servidor);
    $ok = $stmt->execute();
    if (!$ok) {
        resposta_json(["erro" => "Erro ao salvar resposta"]);
    }

    if ($pontos > 0) {
        $stmt = $conn->prepare("UPDATE jogadores SET pontos = pontos + ? WHERE id_jogador = ?");
        $stmt->bind_param("ii", $pontos, $id_jogador);
        $stmt->execute();
    }

    resposta_json([
        "status" => "ok",
        "mensagem" => "Resposta registrada",
        "acertou" => (bool)$acertou,
        "pontos_ganhos" => intval($pontos)
    ]);
}

function verificarTempo($conn, $id_sala, $id_jogador) {
    $stmt = $conn->prepare("SELECT id_pergunta_atual, alternativas_ordem, tempo_resposta, tempo_inicio_pergunta FROM salas WHERE id_sala = ?");
    $stmt->bind_param("i", $id_sala);
    $stmt->execute();
    $sala = $stmt->get_result()->fetch_assoc();

    if (!$sala || !$sala['id_pergunta_atual']) {
        resposta_json(["erro" => "Nenhuma pergunta ativa"]);
    }

    $id_pergunta = intval($sala['id_pergunta_atual']);
    $tempo_inicio = $sala['tempo_inicio_pergunta'] ? strtotime($sala['tempo_inicio_pergunta']) : null;
    $tempo_total = intval($sala['tempo_resposta']);
    $now = time();

    if ($tempo_inicio === null) {
        resposta_json(["erro" => "Tempo de pergunta inválido"]);
    }

    $tempo_passado = max(0, $now - $tempo_inicio);
    $tempo_restante = max(0, $tempo_total - $tempo_passado);
    $tempo_acabou = ($tempo_restante <= 0);

    if (!$tempo_acabou) {
        resposta_json([
            "status" => "aguardando",
            "tempo_restante" => $tempo_restante,
            "timestamp_servidor" => $now
        ]);
    }

    $stmt = $conn->prepare("SELECT alternativa1 FROM perguntas WHERE id_pergunta = ?");
    $stmt->bind_param("i", $id_pergunta);
    $stmt->execute();
    $pergunta = $stmt->get_result()->fetch_assoc();
    if (!$pergunta) resposta_json(["erro" => "Pergunta não encontrada"]);

    $alternativas = json_decode($sala['alternativas_ordem'], true);
    $pos = array_search($pergunta['alternativa1'], $alternativas);
    $posicao_correta = ($pos === false) ? 1 : ($pos + 1);

    $stmt = $conn->prepare("SELECT resposta_escolhida, correta, tempo_resposta FROM respostas WHERE id_jogador = ? AND id_pergunta = ?");
    $stmt->bind_param("ii", $id_jogador, $id_pergunta);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $resposta = $result->fetch_assoc();
        $map = ['A' => 1, 'B' => 2, 'C' => 3, 'D' => 4];
        $sua_resposta = isset($map[$resposta['resposta_escolhida']]) ? $map[$resposta['resposta_escolhida']] : 0;
        $pontos = 0;
        if (intval($resposta['correta']) === 1) {
            $pontos = max(100, 1000 - (intval($resposta['tempo_resposta']) * 50));
        }

        resposta_json([
            "status" => "tempo_acabou",
            "acertou" => (intval($resposta['correta']) === 1),
            "pontos" => intval($pontos),
            "resposta_correta" => $posicao_correta,
            "sua_resposta" => intval($sua_resposta)
        ]);
    } else {
        resposta_json([
            "status" => "tempo_acabou",
            "acertou" => false,
            "pontos" => 0,
            "resposta_correta" => $posicao_correta,
            "sua_resposta" => 0
        ]);
    }
}

function proximaPergunta($conn, $id_sala, $id_jogador) {
    $stmt = $conn->prepare("SELECT is_host FROM jogadores WHERE id_jogador = ? AND id_sala = ?");
    $stmt->bind_param("ii", $id_jogador, $id_sala);
    $stmt->execute();
    $jog = $stmt->get_result()->fetch_assoc();
    if (!$jog || intval($jog['is_host']) !== 1) {
        resposta_json(["erro" => "Apenas o host pode avançar"]);
    }

    $stmt = $conn->prepare("SELECT rodada_atual, rodadas FROM salas WHERE id_sala = ?");
    $stmt->bind_param("i", $id_sala);
    $stmt->execute();
    $sala = $stmt->get_result()->fetch_assoc();
    if (!$sala) resposta_json(["erro" => "Sala inválida"]);

    $rodada_atual = intval($sala['rodada_atual']);
    $rodadas = intval($sala['rodadas']);
    if ($rodada_atual >= $rodadas) {
        resposta_json(["status" => "fim_jogo"]);
    }

    $stmt = $conn->prepare("
        UPDATE salas
        SET id_pergunta_atual = NULL, alternativas_ordem = NULL, tempo_inicio_pergunta = NULL
        WHERE id_sala = ?
    ");
    $stmt->bind_param("i", $id_sala);
    $stmt->execute();

    resposta_json(["status" => "ok"]);
}
?>
