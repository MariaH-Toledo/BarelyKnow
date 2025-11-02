document.addEventListener("DOMContentLoaded", () => {
    const swalDarkTheme = {
        color: 'white',
        background: '#1a1a1a',
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33'
    };

    const formCriar = document.querySelector("#formCriar");

    if (formCriar) {
        formCriar.addEventListener("submit", async (e) => {
            e.preventDefault();

            const formData = new FormData(formCriar);

            try {
                const res = await fetch("view/criar_sala.php", {
                    method: "POST",
                    body: formData
                });

                const data = await res.json();

                if (data.status === "ok") {
                    Swal.fire({
                        icon: "success",
                        title: "Sala criada com sucesso!",
                        html: `<strong>Código:</strong> ${data.codigo}`,
                        confirmButtonText: "Entrar na sala",
                        ...swalDarkTheme
                    }).then(() => {
                        window.location.href = `view/lobby.php?codigo=${data.codigo}`;
                    });
                } else {
                    Swal.fire({
                        icon: "error",
                        title: "Erro ao criar sala",
                        text: data.mensagem || "Tente novamente mais tarde.",
                        ...swalDarkTheme
                    });
                }
            } catch (error) {
                Swal.fire({
                    icon: "error",
                    title: "Erro inesperado",
                    text: "Não foi possível conectar ao servidor.",
                    ...swalDarkTheme
                });
            }
        });
    }

    const formEntrar = document.querySelector("#formEntrar");

    if (formEntrar) {
        formEntrar.addEventListener("submit", async (e) => {
            e.preventDefault();
        
            const formData = new FormData(formEntrar);
        
            try {
                const res = await fetch("view/entrar_sala.php", {
                    method: "POST",
                    body: formData
                });
            
                const data = await res.json();
            
                if (data.status === "ok") {
                    Swal.fire({
                        icon: "success",
                        title: "Você entrou na sala!",
                        confirmButtonText: "Ir para o lobby",
                        ...swalDarkTheme
                    }).then(() => {
                        window.location.href = `view/lobby.php?codigo=${data.codigo}`;
                    });
                } else {
                    Swal.fire({
                        icon: "error",
                        title: "Erro",
                        text: data.mensagem || "Não foi possível entrar na sala.",
                        ...swalDarkTheme
                    });
                }
            } catch (error) {
                Swal.fire({
                    icon: "error",
                    title: "Erro inesperado",
                    text: "Falha na conexão com o servidor.",
                    ...swalDarkTheme
                });
            }
        });
    }   
});