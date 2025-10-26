document.addEventListener("DOMContentLoaded", function () {

    const meu_jogador_id = sessionStorage.getItem("quiz_jogador_id");
    const minha_sala_id = sessionStorage.getItem("quiz_sala_id");

    if (!meu_jogador_id || !minha_sala_id) {
        window.location.href = "index.html";
        return;
    }

    const elCodigoSala = document.getElementById("codigo-sala");
    const elStatusSala = document.getElementById("status-sala");
    const elTotalJogadores = document.getElementById("total-jogadores");
    const elListaJogadores = document.getElementById("lista-jogadores");
    const elBtnIniciar = document.getElementById("btn-iniciar-jogo");
    const elAreaPergunta = document.getElementById("pergunta-container");

    // (Dentro de sala.js, após pegar os elementos do HTML)

    elBtnIniciar.addEventListener("click", function () {
        elBtnIniciar.disabled = true;
        elBtnIniciar.textContent = "Starting...";

        const dados = new FormData();
        dados.append("sala_id", minha_sala_id);

        fetch("start_game.php", {
            method: "POST",
            body: dados
        })
            .then(response => response.json())
            .then(data => {
                if (data.status !== 'sucesso') {
                    console.error(data.mensagem);
                    alert(data.mensagem); // Mostra o erro para o host
                    elBtnIniciar.disabled = false; // Reabilita o botão em caso de erro
                    elBtnIniciar.textContent = "Iniciar Jogo!";
                }
            })
            .catch(error => {
                console.error("Erro ao iniciar jogo:", error);
                elBtnIniciar.disabled = false;
                elBtnIniciar.textContent = "Iniciar Jogo!";
            });
    });

    async function buscarAtualizacoes() {
        try {
            const response = await fetch(`get_room_status.php?sala_id=${minha_sala_id}`);
            if (!response.ok) {
                throw new Error("Falha ao buscar dados do servidor.");
            }

            const data = await response.json();

            const room_code = data.sala.room_code;

            elCodigoSala.textContent = `#${room_code}`;
            document.title = `Brainsparks | Quiz Room #${room_code}`;

            elTotalJogadores.textContent = data.jogadores.length;
            elListaJogadores.innerHTML = "";
            data.jogadores.forEach(jogador => {
                const li = document.createElement("li");
                if (jogador.id_player == meu_jogador_id) {
                    li.textContent = `${jogador.player_name} (${jogador.player_pontuation} pts) (You)`;

                    li.style.color = "blue";
                } else {
                    li.textContent = `${jogador.player_name} (${jogador.player_pontuation} pts)`;
                }
                elListaJogadores.appendChild(li);
            });

            if (data.sala.room_status === 'waiting') {
                elStatusSala.textContent = "Waiting for players...";
                elAreaPergunta.style.display = "none";

                if (data.sala.host_player == meu_jogador_id) {
                    elBtnIniciar.style.display = "block";
                }
            }


            else if (data.sala.room_status === 'playing') {
                elStatusSala.textContent = "Game in Progress...";
                elBtnIniciar.style.display = "none";
                elAreaPergunta.style.display = "block";

                // (Aqui virá a lógica para renderizar a pergunta e as opções)
                // document.getElementById("texto-pergunta").textContent = data.pergunta_atual.texto_pergunta;
                // ...
            }

            // --- ESTADO 3: FINALIZADO ---
            else if (data.sala.room_status === 'finish') {
                elStatusSala.textContent = "Game Finished!";
                elAreaPergunta.style.display = "none";
                elBtnIniciar.style.display = "none";
            }

        } catch (error) {
            console.error("Erro no loop de atualização:", error);
            elStatusSala.textContent = "Erro de conexão. Tentando reconectar...";
        }
    }

    buscarAtualizacoes();

    setInterval(buscarAtualizacoes, 2000);

    // 6. (Próximo passo) Adicionar o clique no botão "Iniciar Jogo"
    // elBtnIniciar.addEventListener("click", function() {
    //     // Faremos um fetch para um NOVO script, ex: "iniciar_jogo.php"
    //     // Esse script mudará o estado da sala de 'aguardando' para 'jogando'
    // });
});