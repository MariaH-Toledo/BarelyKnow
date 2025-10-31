<?php
include_once "../db/conexao.php";

if (isset($_GET['action']) && $_GET['action'] === 'getCategorias') {
    $result = $conn->query("SELECT * FROM categorias ORDER BY nome_categoria");
    $categorias = [];
    while ($row = $result->fetch_assoc()) {
        $categorias[] = $row;
    }
    echo json_encode($categorias);
    exit;
}

function gerarCodigoSala($conn) {
    do {
        $codigo = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
        $check = $conn->query("SELECT * FROM salas WHERE codigo_sala = '$codigo'");
    } while ($check->num_rows > 0);
    return $codigo;
}
?>
