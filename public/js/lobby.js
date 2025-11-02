document.addEventListener("DOMContentLoaded", () => {
    const btnFechar = document.querySelector("#btnFechar");

    if (btnFechar) {
        btnFechar.addEventListener("click", async () => {
            const confirm = await Swal.fire({
                title: "Deseja realmente fechar a sala?",
                icon: "warning",
                showCancelButton: true,
                confirmButtonText: "Sim, fechar",
                cancelButtonText: "Cancelar",
            });

            if (confirm.isConfirmed) {
                try {
                    const res = await fetch("../view/fechar_sala.php", {
                        method: "POST",
                        headers: { "Content-Type": "application/json" },
                        body: JSON.stringify({ codigo: new URLSearchParams(window.location.search).get("codigo") })
                    });

                    const data = await res.json();

                    if (data.status === "ok") {
                        Swal.fire({
                            icon: "success",
                            title: "Sala fechada com sucesso!",
                            timer: 1500,
                            showConfirmButton: false
                        }).then(() => {
                            window.location.href = "../index.php";
                        });
                    } else {
                        Swal.fire("Erro", data.mensagem || "Falha ao fechar sala.", "error");
                    }
                } catch (err) {
                    Swal.fire("Erro", "Não foi possível conectar ao servidor.", "error");
                }
            }
        });
    }
});
