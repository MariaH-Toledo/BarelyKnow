<?php
session_start();
include "../db/conexao.php";
include "../utils/categorias.php";

$codigo = $_GET['codigo'] ?? null;

if (!$codigo) {
    die("<h2>Sala não encontrada.</h2>");
}

$sql = "SELECT s.*, c.nome_categoria 
        FROM salas s 
        JOIN categorias c ON s.id_categoria = c.id_categoria
        WHERE s.codigo_sala = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $codigo);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("<h2>Sala não encontrada.</h2>");
}

$sala = $result->fetch_assoc();

$idUsuario = $_SESSION['id_usuario'] ?? null;
$ehHost = ($idUsuario && $idUsuario == $sala['id_host']);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lobby - <?= htmlspecialchars($codigo) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../public/style/index.css">
</head>
<body class="body-index text-center">
    <div class="container py-5">
        <h1 class="titulo-index">Lobby - <?= htmlspecialchars($codigo) ?></h1>
        <p class="text-light">Categoria: <strong><?= htmlspecialchars($sala['nome_categoria']) ?></strong></p>
        <p class="text-light">Rodadas: <strong><?= $sala['rodadas'] ?></strong> | Tempo: <strong><?= $sala['tempo_resposta'] ?>s</strong></p>

        <div class="card mt-4 bg-light">
            <div class="card-body">
                <h5 class="card-title">Jogadores na Sala</h5>
                <ul id="listaJogadores" class="list-group list-group-flush">
                    <li class="list-group-item">Carregando...</li>
                </ul>
            </div>
        </div>

        <div class="mt-4 d-flex justify-content-center gap-3">
            <?php if ($ehHost): ?>
                <button id="btnIniciar" class="btn btn-success">Iniciar Jogo</button>
                <button id="btnFechar" class="btn btn-danger" onclick="fecharSala()">Fechar Sala</button>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        const codigoSala = "<?= $codigo ?>";

        async function atualizarJogadores() {
            try {
                const res = await fetch(`../utils/listar_jogadores.php?codigo=${codigoSala}`);
                const data = await res.json();
                
                const lista = document.querySelector("#listaJogadores");
                lista.innerHTML = "";

                if (data.length > 0) {
                    data.forEach(j => {
                        const li = document.createElement("li");
                        li.className = "list-group-item";
                        li.textContent = j.nome + (j.is_host == 1 ? " 👑" : "");
                        lista.appendChild(li);
                    });
                } else {
                    lista.innerHTML = "<li class='list-group-item'>Nenhum jogador ainda...</li>";
                }
            } catch {
                console.error("Erro ao atualizar jogadores");
            }
        }

        setInterval(atualizarJogadores, 3000);
        atualizarJogadores();

        function fecharSala() {
            Swal.fire({
                title: "Fechar sala?",
                text: "Todos os jogadores serão desconectados.",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#d33",
                cancelButtonColor: "#3085d6",
                confirmButtonText: "Sim, fechar",
                cancelButtonText: "Cancelar"
            }).then(async (result) => {
                if (result.isConfirmed) {
                    const res = await fetch(`../utils/fechar_sala.php?codigo=${codigoSala}`);
                    const data = await res.text();
                    Swal.fire({
                        icon: "success",
                        title: "Sala encerrada!",
                        text: "Você será redirecionado.",
                        timer: 1500,
                        showConfirmButton: false
                    });
                    setTimeout(() => window.location.href = "../index.php", 1500);
                }
            });
        }
    </script>
</body>
</html>
