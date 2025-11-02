<?php
session_start();
include "../db/conexao.php";

$codigo = $_GET['codigo'] ?? null;
$idUsuario = $_SESSION['id_usuario'] ?? null;

if (!$codigo || !$idUsuario) {
    http_response_code(400);
    echo "Requisição inválida.";
    exit;
}

$sql = "SELECT id_host FROM salas WHERE codigo_sala = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $codigo);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(404);
    echo "Sala não encontrada.";
    exit;
}

$sala = $result->fetch_assoc();

if ($sala['id_host'] != $idUsuario) {
    http_response_code(403);
    echo "Apenas o host pode encerrar a sala.";
    exit;
}

$conn->query("DELETE FROM jogadores WHERE codigo_sala = '$codigo'");
$conn->query("DELETE FROM salas WHERE codigo_sala = '$codigo'");

echo "Sala encerrada com sucesso.";
