<?php
session_start();
require __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$host = $_ENV['DB_HOST'];
$db = $_ENV['DB_NAME'];
$user = $_ENV['DB_USER'];
$pass = $_ENV['DB_PASS'];
$dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";
$options = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC];
try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    exit(json_encode(['status' => 'erro', 'mensagem' => 'Erro de BD.']));
}

header('Content-Type: application/json');
$sala_id = $_GET['sala_id'] ?? 0;
if (empty($sala_id)) {
    exit(json_encode(['status' => 'erro', 'mensagem' => 'ID da sala não fornecido.']));
}

// Estrutura de resposta
$resposta_json = [
    'sala' => null,
    'jogadores' => [],
    'pergunta_atual' => null,
    'opcoes_atuais' => [],
    'tempo_restante' => 0
];

$stmt_sala = $pdo->prepare("SELECT * FROM rooms WHERE id_room = ?");
$stmt_sala->execute([$sala_id]);
$sala = $stmt_sala->fetch();
if (!$sala) {
    exit(json_encode(['status' => 'erro', 'mensagem' => 'Sala não encontrada.']));
}

$resposta_json['sala'] = $sala;

$stmt_jogadores = $pdo->prepare("SELECT id_player, player_name, player_pontuation FROM players WHERE id_room = ? ORDER BY player_pontuation DESC");
$stmt_jogadores->execute([$sala_id]);
$resposta_json['jogadores'] = $stmt_jogadores->fetchAll();

if ($sala['room_status'] == 'playing') {

    $tempo_limite_pergunta = 20;

    $stmt_time = $pdo->query("SELECT NOW() AS agora");
    $agora = new DateTime($stmt_time->fetch()['agora']);
    $start_time = new DateTime($sala['question_start_time']);
    $segundos_passados = $agora->getTimestamp() - $start_time->getTimestamp();
    $tempo_restante = $tempo_limite_pergunta - $segundos_passados;
    $resposta_json['tempo_restante'] = $tempo_restante;

    if ($tempo_restante <= 0) {
        $sequencia = explode(',', $sala['question_sequence']);
        $index_atual = array_search($sala['current_question'], $sequencia);

        if ($index_atual !== false && isset($sequencia[$index_atual + 1])) {
            $proxima_pergunta_id = $sequencia[$index_atual + 1];
            $sql_update = "UPDATE rooms SET current_question = ?, question_start_time = NOW() WHERE id_room = ?";
            $pdo->prepare($sql_update)->execute([$proxima_pergunta_id, $sala_id]);

            $sala['current_question'] = $proxima_pergunta_id;
            $resposta_json['tempo_restante'] = $tempo_limite_pergunta;

        } else {
            $sql_update = "UPDATE rooms SET room_status = 'finish' WHERE id_room = ?";
            $pdo->prepare($sql_update)->execute([$sala_id]);
            $sala['room_status'] = 'finish';
        }
        $resposta_json['sala']['room_status'] = $sala['room_status'];
    }

    if ($sala['room_status'] == 'playing') {
        $stmt_q = $pdo->prepare("SELECT id_question, question_text, question_category FROM questions WHERE id_question = ?");
        $stmt_q->execute([$sala['current_question']]);
        $resposta_json['pergunta_atual'] = $stmt_q->fetch();

        $stmt_o = $pdo->prepare("SELECT id_option, option_text FROM options WHERE id_question = ?");
        $stmt_o->execute([$sala['current_question']]);
        $resposta_json['opcoes_atuais'] = $stmt_o->fetchAll();
    }
}

echo json_encode($resposta_json);