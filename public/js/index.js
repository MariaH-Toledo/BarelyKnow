class IndexController {
    constructor() {
        this.modalTipo = 'criar';
        this.init();
    }

    init() {
        this.configurarModal();
        this.configurarFormulario();
    }

    configurarModal() {
        const modal = document.getElementById('modalSala');
        
        modal.addEventListener('shown.bs.modal', () => {
            setTimeout(() => {
                const input = this.modalTipo === 'entrar' 
                    ? document.querySelector('input[name="codigo_sala"]')
                    : document.querySelector('input[name="nome"]');
                if (input) input.focus();
            }, 150);
        });

        modal.addEventListener('hidden.bs.modal', () => {
            document.getElementById('formSala').reset();
            document.querySelectorAll('input, select').forEach(campo => {
                campo.disabled = false;
                if (campo.name === 'codigo_sala') {
                    campo.required = false;
                }
            });
        });
    }

    abrirModal(tipo) {
        this.modalTipo = tipo;
        const modalTitle = document.getElementById('modalTitle');
        const camposCriar = document.getElementById('camposCriar');
        const camposEntrar = document.getElementById('camposEntrar');
        const btnSubmit = document.getElementById('btnSubmit');

        if (tipo === 'criar') {
            this.configurarModalCriar(modalTitle, camposCriar, camposEntrar, btnSubmit);
        } else {
            this.configurarModalEntrar(modalTitle, camposCriar, camposEntrar, btnSubmit);
        }
    }

    configurarModalCriar(modalTitle, camposCriar, camposEntrar, btnSubmit) {
        modalTitle.textContent = 'Criar Sala';
        camposCriar.style.display = 'block';
        camposEntrar.style.display = 'none';
        btnSubmit.textContent = 'Criar Sala';
        btnSubmit.className = 'btn btn-success-custom btn-custom';

        this.toggleCampos(camposCriar, false);
        this.toggleCampos(camposEntrar, true);
    }

    configurarModalEntrar(modalTitle, camposCriar, camposEntrar, btnSubmit) {
        modalTitle.textContent = 'Entrar em Sala';
        camposCriar.style.display = 'none';
        camposEntrar.style.display = 'block';
        btnSubmit.textContent = 'Entrar na Sala';
        btnSubmit.className = 'btn btn-primary-custom btn-custom';

        this.toggleCampos(camposEntrar, false);
        this.toggleCampos(camposCriar, true);
    }

    toggleCampos(container, desabilitar) {
        const campos = container.querySelectorAll('input, select');
        campos.forEach(campo => {
            campo.disabled = desabilitar;
            campo.required = !desabilitar;
        });
    }

    configurarFormulario() {
        const form = document.getElementById('formSala');
        form.addEventListener('submit', (event) => {
            event.preventDefault();
            this.enviarFormulario();
        });
    }

    async enviarFormulario() {
        const formData = new FormData(document.getElementById('formSala'));
        const url = this.modalTipo === 'criar' ? 'view/criar_sala.php' : 'view/entrar_sala.php';

        try {
            const response = await fetch(url, {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.status === 'ok') {
                this.mostrarSucesso(data.codigo);
            } else {
                this.mostrarErro(data.mensagem);
            }
        } catch (error) {
            this.mostrarErro('Não foi possível conectar ao servidor');
        }
    }

    mostrarSucesso(codigo) {
        const modal = bootstrap.Modal.getInstance(document.getElementById('modalSala'));
        modal.hide();

        Swal.fire({
            icon: 'success',
            title: this.modalTipo === 'criar' ? 'Sala criada!' : 'Entrou na sala!',
            html: this.modalTipo === 'criar' ? `Sala criada com sucesso!<br><strong>Código: ${codigo}</strong>` : 'Redirecionando...',
            timer: 2000,
            showConfirmButton: false,
            background: 'var(--cor-fundo2)',
            color: 'var(--cor-branco)',
            customClass: {
                popup: 'swal2-dark'
            }
        }).then(() => {
            window.location.href = `view/lobby.php?codigo=${codigo}`;
        });
    }

    mostrarErro(mensagem) {
        Swal.fire({
            icon: 'error',
            title: 'Erro',
            text: mensagem,
            background: 'var(--cor-fundo2)',
            color: 'var(--cor-branco)',
            iconColor: 'var(--cor-rosa)'
        });
    }
}

document.addEventListener('DOMContentLoaded', () => {
    window.indexController = new IndexController();
});