# 🧠 BrainSparks - Jogo de Quiz em Tempo Real

BrainSparks é uma aplicação web de quiz multijogador em tempo real. Inspirado em jogos como Kahoot, ele permite que usuários criem ou entrem em salas de quiz usando um código de 5 dígitos, sem necessidade de cadastro.

O jogo é totalmente sincronizado. Todos os jogadores veem as perguntas, o tempo restante e a lista de participantes atualizados em tempo real, culminando em um ranking final para premiar os vencedores.

## ✨ Funcionalidades Principais

* **Salas sem Cadastro:** Entrada rápida apenas com um nome e um código de sala de 5 dígitos.
* **Criação Automática de Sala:** Se o código digitado não existir, uma nova sala é criada com o usuário como "host".
* **Lobby (Sala de Espera):** Jogadores veem quem está na sala enquanto aguardam o host iniciar o jogo.
* **Controle de Host:** Apenas o host (criador da sala) pode iniciar a partida.
* **Migração de Host:** Se o host sair da sala de espera, o cargo é automaticamente transferido para outro jogador.
* **Perguntas Sincronizadas:** O servidor controla o jogo com um timer (20 segundos por pergunta). Todos os jogadores avançam para a próxima pergunta ao mesmo tempo.
* **Pontuação em Tempo Real:** Respostas corretas atualizam a pontuação no placar lateral para todos verem.
* **Ranking Final:** Ao fim das 10 perguntas, uma tela de ranking é exibida, com destaques para o 1º, 2º e 3º lugar.
* **Auto-limpeza (Garbage Collection):**
    * Jogadores que fecham a aba são removidos da sala.
    * Salas vazias são automaticamente deletadas do banco de dados para economizar espaço.

## 🚀 Tecnologias Utilizadas

* **Backend:** PHP 8+
* **Banco de Dados:** MySQL (MariaDB)
* **Frontend:** HTML5, CSS3, JavaScript (ES6+ Fetch API)
* **Gerenciador de Dependências:** Composer
* **Variáveis de Ambiente:** `vlucas/phpdotenv` (para segurança das credenciais do banco)

## 🗃️ Estrutura do Banco de Dados

O banco de dados (chamado `brainsparks`) é composto por 4 tabelas principais:

1.  **`rooms`**
    * Armazena as salas ativas.
    * `id_room` (PK)
    * `room_code` (UNIQUE, 5 dígitos)
    * `room_status` (enum: 'waiting', 'playing', 'finish')
    * `current_question` (FK para `questions.id_question`)
    * `host_player` (FK para `players.id_player`)
    * `question_sequence` (TEXT, armazena a string de IDs das perguntas, ex: "5,12,3,8")
    * `question_start_time` (DATETIME, armazena o timestamp de quando a pergunta atual começou)

2.  **`players`**
    * Armazena os jogadores em cada sala.
    * `id_player` (PK)
    * `id_room` (FK para `rooms.id_room`)
    * `player_name`
    * `player_pontuation`

3.  **`questions`**
    * Armazena o banco de perguntas.
    * `id_question` (PK)
    * `question_category`
    * `question_text`

4.  **`options`**
    * Armazena as alternativas para cada pergunta.
    * `id_option` (PK)
    * `id_question` (FK para `questions.id_question`)
    * `option_text`
    * `is_correct` (TINYINT, 0 ou 1)

## 🔌 Fluxo da API (Endpoints)

O sistema funciona com 5 scripts PHP principais que respondem a requisições AJAX:

1.  **`POST /index.php`**
    * **Ação:** Entrar ou Criar Sala.
    * **Entrada (POST):** `nome_jogador`, `codigo_sala`.
    * **Lógica:** Verifica se a sala existe e está em `waiting`. Se sim, adiciona o jogador. Se não, cria a sala e adiciona o jogador como host.
    * **Sessão:** Salva o `jogador_id` recém-criado em `$_SESSION['quiz_jogador_id']`.
    * **Saída (JSON):** `{ status: 'sucesso', acao: 'entrou' | 'criou', sala_id, jogador_id }`

2.  **`GET /get_estado_sala.php`**
    * **Ação:** O "coração" do jogo (Polling). É chamado a cada 2 segundos.
    * **Entrada (GET):** `sala_id`.
    * **Lógica:**
        * Verifica o estado da sala (`waiting`, `playing`, `finish`).
        * **Se 'playing'**: Verifica o timer (`question_start_time`). Se o tempo (20s) acabou, avança para a próxima pergunta na `question_sequence`. Se não há mais perguntas, muda o status para `finish`.
    * **Saída (JSON):** Um objeto JSON gigante com todo o estado do jogo: `{ sala, jogadores, pergunta_atual, opcoes_atuais, tempo_restante }`.

3.  **`POST /start_game.php`**
    * **Ação:** Iniciar a partida (apenas host).
    * **Entrada (POST):** `sala_id`.
    * **Lógica:** Verifica se o `$_SESSION['quiz_jogador_id']` é o mesmo que `host_player` no banco. Sorteia 10 perguntas, salva a sequência em `question_sequence`, define a primeira `current_question` e inicia o `question_start_time` (NOW()).
    * **Saída (JSON):** `{ status: 'sucesso' }`

4.  **`POST /processar_resposta.php`**
    * **Ação:** Receber e pontuar a resposta de um jogador.
    * **Entrada (POST):** `sala_id`, `question_id`, `option_id`.
    * **Lógica:** Verifica `$_SESSION['quiz_jogador_id']`. Checa se a `option_id` está correta (`is_correct == 1`). Se sim, adiciona pontos ao `player_pontuation`.
    * **Saída (JSON):** `{ status: 'sucesso', correta: true | false }`

5.  **`POST /player_leave.php`**
    * **Ação:** "Faxineiro" (Garbage Collector). Chamado via `navigator.sendBeacon` (JavaScript) quando o usuário fecha a aba.
    * **Entrada (JSON Body):** `{ jogador_id }`.
    * **Lógica:**
        * Deleta o jogador da tabela `players`.
        * Se esse jogador era o `host` de uma sala em `waiting`, promove outro jogador a `host`.
        * Verifica quantos jogadores restam na sala. Se for 0, deleta a sala da tabela `rooms`.
    * **Saída:** Nenhuma (o `sendBeacon` não espera resposta).

## ⚙️ Instalação e Execução Local

1.  Clone este repositório: `git clone <url-do-seu-repositorio>`
2.  Instale as dependências do PHP: `composer install`
3.  Crie um banco de dados no seu MySQL (ex: `brainsparks`).
4.  Importe o arquivo `brainsparks.sql` para criar as tabelas.
5.  Importe o arquivo `.sql` com as 50 perguntas.
6.  Crie um arquivo `.env` na raiz do projeto (copiando o `.env.example` se houver, ou criando do zero).
7.  Adicione suas credenciais do banco ao `.env`:
    ```ini
    DB_HOST=127.0.0.1
    DB_NAME=brainsparks
    DB_USER=root
    DB_PASS=
    ```
8.  Inicie seu servidor local (XAMPP, WAMP, MAMP ou o servidor embutido do PHP: `php -S localhost:8000`).
9.  Abra o `index.html` no seu navegador.

## 💡 Próximos Passos (Possíveis Melhorias)

* Permitir que o Host escolha a categoria e o número de perguntas no lobby.
* Adicionar um sistema de "vidas" ou "power-ups".
* Migrar a comunicação de "polling" (AJAX) para **WebSockets** para um tempo real ainda mais eficiente e com menos carga no servidor.
* Adicionar sons de resposta (correta/incorreta) e música de suspense.