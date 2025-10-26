<?php
session_start();

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

header('Content-Type: application/json');

$sala_id = $_POST['sala_id'] ?? 0;

if (empty($sala_id)) {
    exit(json_encode(['status' => 'erro', 'mensagem' => 'ID da sala não fornecido.']));
}

$stmt = $pdo->prepare("SELECT host_player FROM rooms WHERE id_room = ?");
$stmt->execute([$sala_id]);
$sala = $stmt->fetch();

if (!$sala || $sala['host_player'] != $jogador_id) {
    exit(json_encode(['status' => 'erro', 'mensagem' => 'Apenas o host pode iniciar o jogo.']));
}

// --- LÓGICA PARA INICIAR O JOGO ---

// 1. Sortear 10 perguntas aleatórias da sua tabela de perguntas
// (Assumindo que a tabela se chama 'questions')
$stmt_perguntas = $pdo->query("SELECT id_question FROM questions ORDER BY RAND() LIMIT 10");
$perguntas = $stmt_perguntas->fetchAll(PDO::FETCH_COLUMN, 0);

if (count($perguntas) < 1) {
    exit(json_encode(['status' => 'erro', 'mensagem' => 'Não há perguntas no banco de dados para iniciar o quiz.']));
}

$primeira_pergunta_id = $perguntas[0];
$sequencia_de_ids = implode(',', $perguntas); // Transforma o array [5,12,3] na string "5,12,3"

// 2. Atualizar o estado da sala no banco
$sql_update = "UPDATE rooms SET room_status = 'jogando', current_question_id = ?, question_sequence = ? WHERE id_room = ?";
$stmt_update = $pdo->prepare($sql_update);
$stmt_update->execute([$primeira_pergunta_id, $sequencia_de_ids, $sala_id]);

echo json_encode(['status' => 'sucesso', 'mensagem' => 'O jogo começou!']);

?>