// Aguarda o HTML ser totalmente carregado antes de executar o script
document.addEventListener("DOMContentLoaded", function() {

    // 1. Pega os elementos do HTML com os quais vamos trabalhar
    const formEntrada = document.getElementById("form-entrada");
    const inputNome = document.getElementById("nome_jogador");
    const inputCodigo = document.getElementById("codigo_sala");
    const msgErro = document.getElementById("mensagem-erro");

    // 2. Adiciona um "ouvinte" de evento ao formulário
    // Isso intercepta o envio (submit) do formulário
    formEntrada.addEventListener("submit", function(event) {
        
        // 3. Impede o comportamento padrão do formulário
        // (Que seria recarregar a página)
        event.preventDefault();

        // Limpa erros antigos
        msgErro.textContent = "";

        // 4. Coleta os valores dos campos de input
        const nome = inputNome.value;
        const codigo = inputCodigo.value;

        // 5. Prepara os dados para enviar ao PHP
        // FormData é uma forma fácil de enviar dados como se fosse um formulário HTML
        const dados = new FormData();
        dados.append("nome_jogador", nome);
        dados.append("codigo_sala", codigo);

        // 6. A MÁGICA DO AJAX (Fetch API)
        // Enviamos os 'dados' para o nosso script PHP
        fetch("index.php", {
            method: "POST", // O método HTTP (tem que ser POST pois o PHP usa $_POST)
            body: dados     // Os dados que coletamos
        })
        .then(response => response.json()) // Espera a resposta do PHP e a converte de JSON para um objeto JavaScript
        .then(data => {
            // 7. Processa a resposta que o PHP nos deu
            
            // 'data' é o objeto JavaScript convertido do JSON
            // (Ex: { status: 'sucesso', acao: 'criou', sala_id: 12, jogador_id: 34 } )

            if (data.status === "sucesso") {
                // Deu tudo certo!
                // Agora, o que fazemos? Redirecionamos o usuário para a sala.
                
                // Guardamos os IDs no navegador para a próxima página saber quem somos
                sessionStorage.setItem("quiz_jogador_id", data.jogador_id);
                sessionStorage.setItem("quiz_sala_id", data.sala_id);

                // Redireciona o usuário para a página da sala
                // (Ainda não criamos essa página, mas vamos criar)
                window.location.href = "sala.php"; 

            } else {
                // O PHP retornou um erro (ex: sala cheia, nome inválido)
                // Mostramos a mensagem de erro que o PHP enviou
                msgErro.textContent = data.mensagem;
            }
        })
        .catch(error => {
            // 8. Lida com erros de rede (ex: PHP quebrou, internet caiu)
            console.error("Erro na requisição:", error);
            msgErro.textContent = "Não foi possível conectar ao servidor. Tente novamente.";
        });
    });
});