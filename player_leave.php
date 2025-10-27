<?php
session_start();
require __DIR__ . '/vendor/autoload.php';


ignore_user_abort(true);

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
    exit("Erro de BD.");
}


$json_data = file_get_contents('php://input');
$data = json_decode($json_data, true);
$jogador_id = $data['jogador_id'] ?? 0;

if (empty($jogador_id)) {
    // Se não recebemos um ID, provavelmente é da sessão (embora sendBeacon não envie cookies)
    // Vamos checar a sessão como um fallback
    $jogador_id = $_SESSION['quiz_jogador_id'] ?? 0;
}

if (empty($jogador_id)) {
    exit("ID do jogador não fornecido.");
}

$stmt_info = $pdo->prepare("
    SELECT p.id_room, r.room_status, r.host_player
    FROM players p
    JOIN rooms r ON p.id_room = r.id_room
    WHERE p.id_player = ?
");
$stmt_info->execute([$jogador_id]);
$info = $stmt_info->fetch();

if (!$info) {
    exit("Jogador não encontrado.");
}

$id_sala = $info['id_room'];

if ($info['host_player'] == $jogador_id && $info['room_status'] == 'waiting') {


    $stmt_novo_host = $pdo->prepare("
        SELECT id_player 
        FROM players 
        WHERE id_room = ? AND id_player != ? 
        LIMIT 1
    ");
    $stmt_novo_host->execute([$id_sala, $jogador_id]);
    $novo_host = $stmt_novo_host->fetch();

    if ($novo_host) {
        $stmt_update_host = $pdo->prepare("UPDATE rooms SET host_player = ? WHERE id_room = ?");
        $stmt_update_host->execute([$novo_host['id_player'], $id_sala]);
    } else {
        // 5. Ninguém mais está na sala. O host era o último.
        // A sala será deletada (ver passo 7)
    }
}


$stmt_delete_player = $pdo->prepare("DELETE FROM players WHERE id_player = ?");
$stmt_delete_player->execute([$jogador_id]);

// LÓGICA DE LIMPEZA DE SALA
// A sala deve ser deletada se:
// a) O jogador que saiu era o último (independente do status)
// b) A sala já estava 'finish' e agora está vazia (mesmo que outros já tivessem saído)

$stmt_count = $pdo->prepare("SELECT COUNT(*) as total FROM players WHERE id_room = ?");
$stmt_count->execute([$id_sala]);
$total_restante = $stmt_count->fetch()['total'];

if ($total_restante == 0) {
    $stmt_delete_room = $pdo->prepare("DELETE FROM rooms WHERE id_room = ?");
    $stmt_delete_room->execute([$id_sala]);
}
exit("Limpeza concluída.");
