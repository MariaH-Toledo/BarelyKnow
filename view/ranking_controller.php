<?php
session_start();
require_once '../db/conexao.php';

if (!isset($_POST['codigo_sala'])) {
    echo json_encode(['status' => 'erro', 'mensagem' => 'Código da sala não informado']);
    exit;
}

$codigo_sala = $_POST['codigo_sala'];

$stmt = $conn->prepare("
    SELECT j.nome, COUNT(r.id_resposta) as acertos
    FROM jogadores j
    LEFT JOIN respostas r ON j.id_jogador = r.id_jogador AND r.correta = 1
    INNER JOIN salas s ON j.id_sala = s.id_sala
    WHERE s.codigo_sala = ?
    GROUP BY j.id_jogador
    ORDER BY acertos DESC, j.nome ASC
");
$stmt->bind_param("s", $codigo_sala);
$stmt->execute();
$result = $stmt->get_result();

$ranking = [];
while ($row = $result->fetch_assoc()) {
    $ranking[] = $row;
}

echo json_encode([
    'status' => 'ok',
    'ranking' => $ranking
]);
?>