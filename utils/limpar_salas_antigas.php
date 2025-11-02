<?php
include "../db/conexao.php";

try {
    $conn->query("ALTER TABLE salas ADD COLUMN IF NOT EXISTS data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
} catch (Exception $e) {
}

$tempo_maximo_inatividade = 24;

$data_limite = date('Y-m-d H:i:s', strtotime("-$tempo_maximo_inatividade hours"));

echo "Limpando salas criadas antes de: $data_limite\n";

$sql_salas_antigas = "SELECT id_sala FROM salas WHERE data_criacao < ?";
$stmt_salas = $conn->prepare($sql_salas_antigas);
$stmt_salas->bind_param("s", $data_limite);
$stmt_salas->execute();
$result_salas = $stmt_salas->get_result();

$salas_excluidas = 0;

while ($sala = $result_salas->fetch_assoc()) {
    $id_sala = $sala['id_sala'];
    
    echo "Excluindo sala ID: $id_sala\n";
    
    $sql_jogadores = "SELECT id_jogador FROM jogadores WHERE id_sala = ?";
    $stmt_jogadores = $conn->prepare($sql_jogadores);
    $stmt_jogadores->bind_param("i", $id_sala);
    $stmt_jogadores->execute();
    $result_jogadores = $stmt_jogadores->get_result();
    
    $ids_jogadores = [];
    while ($jogador = $result_jogadores->fetch_assoc()) {
        $ids_jogadores[] = $jogador['id_jogador'];
    }
    
    if (!empty($ids_jogadores)) {
        $placeholders = str_repeat('?,', count($ids_jogadores) - 1) . '?';
        $sql_respostas = "DELETE FROM respostas WHERE id_jogador IN ($placeholders)";
        $stmt_respostas = $conn->prepare($sql_respostas);
        $stmt_respostas->bind_param(str_repeat('i', count($ids_jogadores)), ...$ids_jogadores);
        $stmt_respostas->execute();
    }
    
    $sql_delete_sala = "DELETE FROM salas WHERE id_sala = ?";
    $stmt_sala = $conn->prepare($sql_delete_sala);
    $stmt_sala->bind_param("i", $id_sala);
    $stmt_sala->execute();
    
    $salas_excluidas++;
}

echo "Total de salas excluídas: $salas_excluidas\n";
?>