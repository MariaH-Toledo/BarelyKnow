<?php
$host = "localhost";
$user = "root";
$password = "";
$db = "barelyknow";

$conn = new mysqli($host, $user, $password, $db);

if ($conn->connect_error) {
    echo "ERRO AO CONECTAR!!!";
} else{
        // echo "CONECTADO COM SUCESSO!!!";
    }

$conn->set_charset("utf8mb4");
?>