<?php
header('Content-Type: application/json');

require __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$host = $_ENV['DB_HOST'];
$db = $_ENV['DB_NAME'];
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
    echo json_encode(['status' => 'erro', 'mensagem' => 'Erro interno do servidor.']);
    exit;
}

$sala_id = $_GET['sala_id'] ?? 0;

if (empty($sala_id)) {
    echo json_encode(['status' => 'erro', 'mensagem' => 'ID da sala não fornecido.']);
    exit;
}

// 3. Preparar a resposta
$resposta_json = [
    'sala' => null,
    'jogadores' => [],
    'pergunta_atual' => null
];

$stmt_sala = $pdo->prepare("SELECT * FROM rooms WHERE id_room = ?");
$stmt_sala->execute([$sala_id]);
$sala = $stmt_sala->fetch();

if (!$sala) {
    echo json_encode(['status' => 'erro', 'mensagem' => 'Sala não encontrada.']);
    exit;
}
$resposta_json['sala'] = $sala;

// 5. Buscar dados dos Jogadores na sala
$stmt_jogadores = $pdo->prepare("SELECT id_player, player_name, player_pontuation FROM players WHERE id_room = ? ORDER BY player_pontuation DESC");
$stmt_jogadores->execute([$sala_id]);
$jogadores = $stmt_jogadores->fetchAll();
$resposta_json['jogadores'] = $jogadores;

// 6. (Futuro) Buscar dados da Pergunta Atual (se o estado for 'jogando')
// if ($sala['estado'] == 'jogando' && !empty($sala['pergunta_atual_id'])) {
//    // ... lógica para buscar a pergunta e as opções ...
//    $resposta_json['pergunta_atual'] = ...
// }


// 7. Enviar a resposta completa em JSON
header('Content-Type: application/json');
echo json_encode($resposta_json);

?>