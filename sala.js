document.addEventListener("DOMContentLoaded", function () {
    const meu_jogador_id = sessionStorage.getItem("quiz_jogador_id");
    const minha_sala_id = sessionStorage.getItem("quiz_sala_id");

    if (!meu_jogador_id || !minha_sala_id) {
        window.location.href = "index.html";
        return;
    }

    const elCodigoSala = document.getElementById("codigo-sala");
    const elTotalJogadores = document.getElementById("total-jogadores");
    const elListaJogadores = document.getElementById("lista-jogadores");

    const elAreaLobby = document.getElementById("area-lobby");
    const elAreaJogo = document.getElementById("area-jogo");
    const elAreaRanking = document.getElementById("area-ranking");
    const elBtnIniciar = document.getElementById("btn-iniciar-jogo");

    const elTimer = document.getElementById("timer").querySelector("span");
    const elCategoriaPergunta = document.getElementById("categoria-pergunta");
    const elTextoPergunta = document.getElementById("texto-pergunta");
    const elOpcoesRespostas = document.getElementById("opcoes-respostas");

    const elRankingLista = document.getElementById("ranking-lista");

    let perguntaAtualExibida = null;

    async function buscarAtualizacoes() {
        try {
            const response = await fetch(`get_room_status.php?sala_id=${minha_sala_id}`);
            if (!response.ok) throw new Error("Falha no servidor.");

            const data = await response.json();
            if (data.status === 'erro') throw new Error(data.mensagem);

            elCodigoSala.textContent = data.sala.room_code;

            elTotalJogadores.textContent = data.jogadores.length;
            elListaJogadores.innerHTML = "";
            data.jogadores.forEach(jogador => {
                const li = document.createElement("li");
                li.textContent = `${jogador.player_name} (${jogador.player_pontuation} pts)`;
                if (jogador.id_player == meu_jogador_id) {
                    li.style.fontWeight = "bold";
                }
                elListaJogadores.appendChild(li);
            });

            const estado = data.sala.room_status;

            if (estado === 'waiting') {
                mostrarTela("lobby");
                if (data.sala.host_player == meu_jogador_id) {
                    elBtnIniciar.style.display = "block";
                }
            }
            else if (estado === 'playing') {
                mostrarTela("jogo");

                elTimer.textContent = data.tempo_restante > 0 ? data.tempo_restante : 0;

                if (data.pergunta_atual && perguntaAtualExibida !== data.pergunta_atual.id_question) {
                    perguntaAtualExibida = data.pergunta_atual.id_question;
                    renderizarPergunta(data.pergunta_atual, data.opcoes_atuais);
                }
            }
            else if (estado === 'finish') {
                mostrarTela("ranking");
                renderizarRanking(data.jogadores);
                clearInterval(gameLoop);
            }

        } catch (error) {
            console.error("Erro no loop de atualização:", error.message);
        }
    }

    function mostrarTela(tela) {
        elAreaLobby.style.display = (tela === 'lobby') ? 'block' : 'none';
        elAreaJogo.style.display = (tela === 'jogo') ? 'block' : 'none';
        elAreaRanking.style.display = (tela === 'ranking') ? 'block' : 'none';
    }

    function renderizarPergunta(pergunta, opcoes) {
        elCategoriaPergunta.textContent = pergunta.question_category;
        elTextoPergunta.textContent = pergunta.question_text;
        elOpcoesRespostas.innerHTML = "";

        opcoes.forEach(opt => {
            const btn = document.createElement("button");
            btn.className = "btn-opcao";
            btn.textContent = opt.option_text;
            btn.dataset.optionId = opt.id_option;

            btn.onclick = () => {
                enviarResposta(pergunta.id_question, opt.id_option);
                document.querySelectorAll('.btn-opcao').forEach(b => {
                    b.disabled = true;
                    b.style.cursor = 'not-allowed';
                });
            };
            elOpcoesRespostas.appendChild(btn);
        });
    }

    async function enviarResposta(questionId, optionId) {
        const dados = new FormData();
        dados.append("sala_id", minha_sala_id);
        dados.append("jogador_id", meu_jogador_id);
        dados.append("question_id", questionId);
        dados.append("option_id", optionId);

        try {
            const response = await fetch("process_response.php", {
                method: "POST", body: dados
            });
            const result = await response.json();

            // Feedback visual da resposta (opcional, mas legal)
            const btnClicado = document.querySelector(`.btn-opcao[data-option-id="${optionId}"]`);
            if (result.correta) {
                btnClicado.style.backgroundColor = 'lightgreen';
            } else {
                btnClicado.style.backgroundColor = 'lightcoral';
            }

        } catch (error) {
            console.error("Erro ao enviar resposta:", error);
        }
    }

    function renderizarRanking(jogadores) {
        elRankingLista.innerHTML = "";

        jogadores.forEach((jogador, index) => {
            const li = document.createElement("li");
            let parabens = "";
            let classe = "";

            // Verifica se é o jogador atual
            if (jogador.id_player == meu_jogador_id) {
                if (index === 0) { // 1º lugar
                    parabens = " 🥇 PARABÉNS, CAMPEÃO! 🥇";
                    classe = "lugar-1";
                } else if (index === 1) { // 2º lugar
                    parabens = " 🥈 Muito bem! 🥈";
                    classe = "lugar-2";
                } else if (index === 2) { // 3º lugar
                    parabens = " 🥉 Pódio! 🥉";
                    classe = "lugar-3";
                }
            }

            li.innerHTML = `#${index + 1} - ${jogador.player_name} (${jogador.player_pontuation} pts) ${parabens}`;
            if (classe) li.classList.add(classe);

            elRankingLista.appendChild(li);
        });
    }


    elBtnIniciar.addEventListener("click", function () {
        elBtnIniciar.disabled = true;
        elBtnIniciar.textContent = "Iniciando...";

        const dados = new FormData();
        dados.append("sala_id", minha_sala_id);
        dados.append("jogador_id", meu_jogador_id);

        fetch("start_game.php", {
            method: "POST",
            body: dados
        })
            .then(response => response.json())
            .then(data => {
                if (data.status !== 'sucesso') {
                    alert(data.mensagem);
                    elBtnIniciar.disabled = false;
                    elBtnIniciar.textContent = "Iniciar Jogo!";
                }
            })
            .catch(error => {
                console.error("Erro ao iniciar jogo:", error);
                elBtnIniciar.disabled = false;
            });
    });

    buscarAtualizacoes();
    const gameLoop = setInterval(buscarAtualizacoes, 2000);
});