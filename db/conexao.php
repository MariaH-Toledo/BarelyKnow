<?php
$host = "localhost";
$user = "root";
$password = "";
$db = "barelyknow";

$conn = new mysqli($host, $user, $password, $db);

if ($conn->connect_error) {
    die(json_encode([
        "status" => "erro", 
        "mensagem" => "Falha na conexao com banco de dados"
    ], JSON_UNESCAPED_UNICODE));
}

$conn->set_charset("utf8mb4");
?>