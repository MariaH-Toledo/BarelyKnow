<?php
include_once "../db/conexao.php";
include_once "../utils/funcoes.php";

$categoria = $_POST['categoria'];
$rodadas = $_POST['rodadas'];
$tempo_resposta = $_POST['tempo_resposta'];

$codigo_sala = gerarCodigoSala($conn);

$sql = "INSERT INTO salas (codigo_sala, id_categoria, rodadas, tempo_resposta)
        VALUES ('$codigo_sala', '$categoria', '$rodadas', '$tempo_resposta')";

if ($conn->query($sql) === TRUE) {
    echo json_encode(["status" => "ok", "codigo" => $codigo_sala]);
} else {
    echo json_encode(["status" => "erro", "mensagem" => $conn->error]);
}
?>
