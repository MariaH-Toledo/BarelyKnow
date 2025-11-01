<?php
header("Content-Type: application/json");
include "../db/conexao.php";

// Verifica se veio algo via POST
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nome = trim($_POST["nome"] ?? "");
    $categoria = intval($_POST["categoria"] ?? 0);
    $rodadas = intval($_POST["rodadas"] ?? 0);
    $tempo_resposta = intval($_POST["tempo_resposta"] ?? 0);

    if (empty($nome) || !$categoria || !$rodadas || !$tempo_resposta) {
        echo json_encode(["status" => "erro", "mensagem" => "Campos obrigatórios não preenchidos."]);
        exit;
    }

    // Gera código da sala de 6 dígitos (único)
    do {
        $codigo = strtoupper(substr(str_shuffle("ABCDEFGHJKLMNPQRSTUVWXYZ23456789"), 0, 6));
        $check = $conn->query("SELECT id_sala FROM salas WHERE codigo_sala = '$codigo'");
    } while ($check && $check->num_rows > 0);

    // Insere a sala
    $sql_sala = "INSERT INTO salas (codigo_sala, id_categoria, rodadas, tempo_resposta)
                 VALUES ('$codigo', $categoria, $rodadas, $tempo_resposta)";

    if ($conn->query($sql_sala)) {
        $id_sala = $conn->insert_id;

        // Adiciona o jogador como host
        $sql_jogador = "INSERT INTO jogadores (nome, id_sala, is_host)
                        VALUES ('$nome', $id_sala, 1)";
        $conn->query($sql_jogador);

        echo json_encode([
            "status" => "ok",
            "codigo" => $codigo
        ]);
    } else {
        echo json_encode([
            "status" => "erro",
            "mensagem" => "Erro ao criar sala: " . $conn->error
        ]);
    }
}
?>
