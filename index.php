<?php
session_start();
include "db/conexao.php";
$categorias = $conn->query("SELECT * FROM categorias ORDER BY nome_categoria ASC");
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BarelyKnow</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="public/style/index.css">
    <link rel="icon" href="public/img/icon.png">
</head>

<body class="body-index">
    <div class="text-center">
        <img src="public/img/logo-principal.png" alt="Logo BarelyKnow" class="logo-index">
        <h1 class="titulo-index">BarelyKnow</h1>
        <p class="subtitulo-index">Hora de colocar os conhecimentos em prática... ou pelo menos a sorte!</p>

        <button class="btn btn-lg btn-custom-index btn-criar-index" data-bs-toggle="modal" data-bs-target="#modalSala" onclick="abrirModal('criar')">
            <i class="bi bi-plus-circle"></i> Criar Sala
        </button>
        <br>
        <button class="btn btn-lg btn-custom-index btn-entrar-index" data-bs-toggle="modal" data-bs-target="#modalSala" onclick="abrirModal('entrar')">
            <i class="bi bi-box-arrow-in-right"></i> Entrar em Sala
        </button>
    </div>

    <div class="modal fade" id="modalSala" tabindex="-1" aria-labelledby="modalSalaLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content modal-content-dark">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Criar Sala</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <form id="formSala">
                        <div class="mb-3">
                            <label class="form-label">Seu nome:</label>
                            <input type="text" name="nome" class="form-control form-control-dark" required>
                        </div>

                        <div id="camposCriar">
                            <div class="mb-3">
                                <label class="form-label">Categoria:</label>
                                <select name="categoria" class="form-select form-select-dark" required>
                                    <option value="">Selecione uma categoria</option>
                                    <?php while ($cat = $categorias->fetch_assoc()) { ?>
                                        <option value="<?= $cat['id_categoria'] ?>">
                                            <?= htmlspecialchars($cat['nome_categoria']) ?>
                                        </option>
                                    <?php } ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Rodadas:</label>
                                <input type="number" name="rodadas" class="form-control form-control-dark" min="3" max="20" value="3" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Tempo por pergunta:</label>
                                <select name="tempo_resposta" class="form-select form-select-dark" required>
                                    <option value="10">10 segundos</option>
                                    <option value="15" selected>15 segundos</option>
                                    <option value="20">20 segundos</option>
                                </select>
                            </div>
                        </div>

                        <div id="camposEntrar" style="display: none;">
                            <div class="mb-3">
                                <label class="form-label">Código da Sala:</label>
                                <input type="text" name="codigo_sala" maxlength="6" class="form-control form-control-dark text-uppercase" placeholder="Ex: ABC123">
                            </div>
                        </div>

                        <div class="text-end">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-success" id="btnSubmit">Criar Sala</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script>
        let modalTipo = 'criar';

        function abrirModal(tipo) {
            modalTipo = tipo;
            const modalTitle = document.getElementById('modalTitle');
            const camposCriar = document.getElementById('camposCriar');
            const camposEntrar = document.getElementById('camposEntrar');
            const btnSubmit = document.getElementById('btnSubmit');
        
            if (tipo === 'criar') {
                modalTitle.textContent = 'Criar Sala';
                camposCriar.style.display = 'block';
                camposEntrar.style.display = 'none';
                btnSubmit.textContent = 'Criar Sala';
                btnSubmit.className = 'btn btn-success';

                document.querySelectorAll('#camposEntrar input').forEach(campo => {
                    campo.required = false;
                });
                document.querySelectorAll('#camposCriar input, #camposCriar select').forEach(campo => {
                    campo.required = true;
                });

            } else {
                modalTitle.textContent = 'Entrar em Sala';
                camposCriar.style.display = 'none';
                camposEntrar.style.display = 'block';
                btnSubmit.textContent = 'Entrar na Sala';
                btnSubmit.className = 'btn btn-primary-custom btn-custom';

                document.querySelectorAll('#camposCriar input, #camposCriar select').forEach(campo => {
                    campo.required = false;
                });
                document.querySelectorAll('#camposEntrar input').forEach(campo => {
                    campo.required = true;
                });

                setTimeout(() => {
                    const inputCodigo = document.querySelector('input[name="codigo_sala"]');
                    if (inputCodigo) inputCodigo.focus();
                }, 100);
            }
        }

        document.getElementById('modalSala').addEventListener('hidden.bs.modal', function() {
            document.getElementById('formSala').reset();
            document.querySelectorAll('input, select').forEach(campo => {
                campo.required = false;
            });
        });

        document.getElementById('formSala').addEventListener('submit', async function(event) {
            event.preventDefault();

            const formData = new FormData(this);
            const url = modalTipo === 'criar' ? 'view/criar_sala.php' : 'view/entrar_sala.php';

            try {
                const response = await fetch(url, {
                    method: 'POST',
                    body: formData
                });
            
                const data = await response.json();
            
                if (data.status === 'ok') {
                    const modal = bootstrap.Modal.getInstance(document.getElementById('modalSala'));
                    modal.hide();
                
                    Swal.fire({
                        icon: 'success',
                        title: modalTipo === 'criar' ? 'Sala criada!' : 'Entrou na sala!',
                        html: modalTipo === 'criar' ? `Sala criada com sucesso!<br><strong>Código: ${data.codigo}</strong>` : 'Redirecionando...',
                        timer: 2000,
                        showConfirmButton: false,
                        background: 'var(--cor-fundo2)',
                        color: 'var(--cor-branco)'
                    }).then(() => {
                        window.location.href = `view/lobby.php?codigo=${data.codigo}`;
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Erro',
                        text: data.mensagem,
                        background: 'var(--cor-fundo2)',
                        color: 'var(--cor-branco)'
                    });
                }
            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Erro de conexão',
                    text: 'Não foi possível conectar ao servidor',
                    background: 'var(--cor-fundo2)',
                    color: 'var(--cor-branco)'
                });
            }
        });     
    </script>
</body>
</html>