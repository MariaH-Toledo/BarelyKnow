const btnCriar = document.getElementById("btnCriar");
const btnEntrar = document.getElementById("btnEntrar");
const popupFundo = document.getElementById("popupFundo");
const popupConteudo = document.getElementById("popupConteudo");

btnCriar.addEventListener("click", async () => {
    popupConteudo.innerHTML = `
        <h2>Criar Sala</h2>
        <form id="formCriarSala">
            <input type="text" name="nome" placeholder="Seu nome" required><br><br>
            
            <label>Categoria:</label>
            <select name="categoria" id="categoria" required>
                <option value="">Carregando...</option>
            </select><br><br>

            <label>Rodadas:</label>
            <input type="number" name="rodadas" min="3" max="15" required><br><br>

            <label>Tempo (s):</label>
            <select name="tempo_resposta" required>
                <option value="10">10 segundos</option>
                <option value="15">15 segundos</option>
                <option value="20">20 segundos</option>
            </select><br><br>

            <button type="submit">Criar</button>
            <button type="button" id="fecharPopup">Cancelar</button>
        </form>
    `;
    popupFundo.classList.remove("hidden");

    document.getElementById("fecharPopup").onclick = () => {
        popupFundo.classList.add("hidden");
    };

    const categoriaSelect = document.getElementById("categoria");
    const response = await fetch("utils/funcoes.php?action=getCategorias");
    if (response.ok) {
        const categorias = await response.json();
        categoriaSelect.innerHTML = "";
        categorias.forEach(cat => {
            const option = document.createElement("option");
            option.value = cat.id_categoria;
            option.textContent = cat.nome_categoria;
            categoriaSelect.appendChild(option);
        });
    }

    const form = document.getElementById("formCriarSala");
    form.addEventListener("submit", async (e) => {
        e.preventDefault();
        const formData = new FormData(form);

        const res = await fetch("view/criar_sala.php", {
            method: "POST",
            body: formData
        });

        const data = await res.json();
        if (data.status === "ok") {
            alert(`Sala criada com sucesso!\nCódigo: ${data.codigo}`);
            window.location.href = `view/lobby.php?codigo=${data.codigo}`;
        } else {
            alert("Erro: " + data.mensagem);
        }
    });
});

btnEntrar.addEventListener("click", () => {
    popupConteudo.innerHTML = `
        <h2>Entrar em Sala</h2>
        <form action="view/entrar_sala.php" method="POST">
            <input type="text" name="nome" placeholder="Seu nome" required><br><br>
            <input type="text" name="codigo_sala" placeholder="Código da sala" maxlength="6" required><br><br>
            <button type="submit">Entrar</button>
            <button type="button" id="fecharPopup">Cancelar</button>
        </form>
    `;
    popupFundo.classList.remove("hidden");

    document.getElementById("fecharPopup").onclick = () => {
        popupFundo.classList.add("hidden");
    };
});
