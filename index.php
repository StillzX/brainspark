<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz em Tempo Real</title>

    <link rel="stylesheet" href="./asset/css/style.css">
</head>
<body>

    <div id="welcome-box">
        <h1>Bem-vindo ao BrainSpark!</h1>
        <p>Participe de quizzes em tempo real com seus amigos.</p>
    </div>
    <div id="login-box">
        <h2>Entrar no Quiz</h2>
        <form id="form-entrada">
            <div>
                <label for="nome_jogador">Seu Nome:</label>
                <input type="text" id="nome_jogador" name="nome_jogador" required>
            </div>
            <div>
                <label for="codigo_sala">Código da Sala (5 dígitos):</label>
                <input type="text" id="codigo_sala" name="codigo_sala" maxlength="5" required>
            </div>
            <button type="submit">Entrar / Criar Sala</button>
        </form>
        
        <p id="mensagem-erro"></p>
    </div>

    <!-- o atributo 'defer' enfatiza que o contéudo do javascript só será executado depois que a página HTML toda já estiver carregada-->
    <script src="app.js" defer></script>
</body>
</html>