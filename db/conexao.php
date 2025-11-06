<?php
$host = "localhost";
$user = "root";
$password = "";
$db = "barelyknow";

$conn = new mysqli($host, $user, $password, $db);

if ($conn->connect_error) {
    die("Falha na conexão: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");
?>