<?php
include "db/conexao.php";
$categorias = $conn->query("SELECT * FROM categorias ORDER BY nome_categoria ASC");
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BarelyKnow</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/cssbootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/fontbootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background-color: #0a0a23;
            color: #fff;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
        }
        .btn-custom {
            width: 200px;
            font-size: 1.2rem;
            margin: 10px 0;
        }
        .modal-content {
            background-color: #fff;
            color: #000;
            border-radius: 10px;
        }
        h1 {
            font-weight: bold;
            margin-bottom: 0.3em;
        }
        .subtitulo {
            font-size: 1.1rem;
            color: #ccc;
            margin-bottom: 2em;
        }
    </style>
</head>
<body>
    <div class="text-center">
        <h1>BarelyKnow</h1>
        <p class="subtitulo">Frase de efeito que eu não sei qual vai ser ainda</p>

        <button class="btn btn-success btn-lg btn-custom" data-bs-toggle="modal" data-bs-target="#modalCriar">
            <i class="bi bi-plus-circle"></i> Criar Sala
        </button>
        <br>
        <button class="btn btn-primary btn-lg btn-custom" data-bs-toggle="modal" data-bs-target="#modalEntrar">
            <i class="bi bi-box-arrow-in-right"></i> Entrar em Sala
        </button>
    </div>

    <div class="modal fade" id="modalCriar" tabindex="-1" aria-labelledby="modalCriarLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalCriarLabel">Criar Sala</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form action="view/criar_sala.php" method="POST">
                        <div class="mb-3">
                            <label class="form-label">Seu nome:</label>
                            <input type="text" name="nome" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Categoria:</label>
                            <select name="categoria" class="form-select" required>
                                <option value="">Selecione uma categoria</option>
                                <?php while($cat = $categorias->fetch_assoc()) { ?>
                                    <option value="<?= $cat['id_categoria'] ?>">
                                        <?= htmlspecialchars($cat['nome_categoria']) ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Rodadas:</label>
                            <input type="number" name="rodadas" class="form-control" min="3" max="20" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Tempo por pergunta:</label>
                            <select name="tempo_resposta" class="form-select" required>
                                <option value="10">10 segundos</option>
                                <option value="15">15 segundos</option>
                                <option value="20">20 segundos</option>
                            </select>
                        </div>

                        <div class="text-end">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-success">Criar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalEntrar" tabindex="-1" aria-labelledby="modalEntrarLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalEntrarLabel">Entrar em Sala</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form action="view/entrar_sala.php" method="POST">
                        <div class="mb-3">
                            <label class="form-label">Seu nome:</label>
                            <input type="text" name="nome" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Código da Sala:</label>
                            <input type="text" name="codigo_sala" maxlength="6" class="form-control text-uppercase" required>
                        </div>
                        <div class="text-end">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-primary">Entrar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
