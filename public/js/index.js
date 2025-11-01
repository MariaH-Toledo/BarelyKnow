document.addEventListener("DOMContentLoaded", () => {
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
                        confirmButtonText: "Entrar na sala"
                    }).then(() => {
                        window.location.href = `view/lobby.php?codigo=${data.codigo}`;
                    });
                } else {
                    Swal.fire({
                        icon: "error",
                        title: "Erro ao criar sala",
                        text: data.mensagem || "Tente novamente mais tarde."
                    });
                }
            } catch (error) {
                Swal.fire({
                    icon: "error",
                    title: "Erro inesperado",
                    text: "Não foi possível conectar ao servidor."
                });
            }
        });
    }
});
