<?php

require __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$host = $_ENV['DB_HOST'];
$db   = $_ENV['DB_NAME'];
$user = $_ENV['DB_USER'];
$pass = $_ENV['DB_PASS'];
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
     $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
     http_response_code(500);
     echo json_encode(['status' => 'erro', 'mensagem' => 'Erro interno do servidor.']);
     exit;
}

$code_room_user = $_POST['room_code'] ?? '';
$username = $_POST['username'] ?? '';

if (empty($code_room_user) || empty($username)) {
    echo json_encode(['status' => 'erro', 'mensagem' => 'Nome e código da sala são obrigatórios.']);
    exit;
}


function searchRoom($pdo, $code) {
    $sql = "SELECT * FROM rooms WHERE room_code = ? LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$code]);
    return $stmt->fetch(); // Retorna a linha (array) ou false se não encontrar
}


function joinRoom($pdo, $id_room, $username) {
    $sql = "INSERT INTO players (id_room, player_name, player_pontuation) VALUES (?, ?, 0)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id_room, $username]);

    return $pdo->lastInsertId(); // Retorna o ID do jogador recém-criado
}

function createRoomAndJoin($pdo, $code, $username) {
    $sql_sala = "INSERT INTO rooms (room_code, room_status) VALUES (?, 'waiting')";
    $stmt_sala = $pdo->prepare($sql_sala);
    $stmt_sala->execute([$code]);
    $nova_sala_id = $pdo->lastInsertId(); // Pega o ID da sala que acabamos de criar

    $novo_jogador_id = joinRoom($pdo, $nova_sala_id, $username);

    return ['sala_id' => $nova_sala_id, 'jogador_id' => $novo_jogador_id];
}


header('Content-Type: application/json');
$resposta_json = [];

$salaEncontrada = searchRoom($pdo, $code_room_user);

if ($salaEncontrada) {
    if ($salaEncontrada['room_status'] == 'waiting') {
        $jogador_id = joinRoom($pdo, $salaEncontrada['id_room'], $username);
        
        $resposta_json = [
            'status' => 'sucesso', 
            'acao' => 'entrou', 
            'sala_id' => $salaEncontrada['id_room'], 
            'jogador_id' => $jogador_id
        ];
        
    } else {
        $resposta_json = [
            'status' => 'erro', 
            'mensagem' => 'Essa sala não está aceitando novos jogadores.'
        ];
    }

} else {
    // retorna o id da sala criada e o id do primeiro jogador (você)
    $info = createRoomAndJoin($pdo, $code_room_user, $username);
    
    $resposta_json = [
        'status' => 'sucesso', 
        'acao' => 'criou', 
        'sala_id' => $info['sala_id'], 
        'jogador_id' => $info['jogador_id']
    ];
}

echo json_encode($resposta_json);

?>