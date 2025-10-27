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

$sala_id = $_POST['sala_id'] ?? 0;
$question_id = $_POST['question_id'] ?? 0;
$option_id = $_POST['option_id'] ?? 0;
$jogador_id =  $_POST['jogador_id'] ?? 0;

if (empty($sala_id) || empty($question_id) || empty($option_id) || empty($jogador_id)) {
    exit(json_encode(['status' => 'erro', 'mensagem' => 'Dados inválidos.']));
}


$stmt_opt = $pdo->prepare("SELECT is_correct FROM options WHERE id_option = ? AND id_question = ?");
$stmt_opt->execute([$option_id, $question_id]);
$option = $stmt_opt->fetch();

if ($option && $option['is_correct'] == 1) {
    $pontos_ganhos = 10;

    $stmt_update = $pdo->prepare("UPDATE players SET player_pontuation = player_pontuation + ? WHERE id_player = ?");
    $stmt_update->execute([$pontos_ganhos, $jogador_id]);

    echo json_encode(['status' => 'sucesso', 'correta' => true]);
} else {
    echo json_encode(['status' => 'sucesso', 'correta' => false]);
}
