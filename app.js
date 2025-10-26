document.addEventListener("DOMContentLoaded", function() {
    const formEntrada = document.getElementById("form-entrada");
    const inputNome = document.getElementById("nome_jogador");
    const inputCodigo = document.getElementById("codigo_sala");
    const msgErro = document.getElementById("mensagem-erro");

    formEntrada.addEventListener("submit", async function(event) {
        event.preventDefault();
        msgErro.textContent = "";

        const nome = inputNome.value.trim();
        const codigo = inputCodigo.value.trim();

        if (!nome || !codigo) {
            msgErro.textContent = "Por favor, preencha todos os campos.";
            return;
        }

        const dados = new FormData();
        dados.append("nome_jogador", nome);
        dados.append("codigo_sala", codigo);

        try {
            const response = await fetch("data_process.php", {
                method: "POST",
                body: dados
            });

            if (!response.ok) {
                throw new Error(`Erro HTTP: ${response.status}`);
            }

            const data = await response.json();

            if (data.status === "sucesso") {
                sessionStorage.setItem("quiz_jogador_id", data.jogador_id);
                sessionStorage.setItem("quiz_sala_id", data.sala_id);
                window.location.href = "room.php";
            } else {
                msgErro.textContent = data.mensagem || "Erro desconhecido.";
            }

        } catch (error) {
            console.error("Erro na requisição:", error);
            msgErro.textContent = "Não foi possível conectar ao servidor.";
        }
    });
});