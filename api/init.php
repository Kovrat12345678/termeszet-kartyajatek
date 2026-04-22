<?php
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json');

$name = trim($_POST['name'] ?? '');
if (!$name || mb_strlen($name) < 2) {
    echo json_encode(['ok' => false, 'error' => 'Adj meg egy nevet (min 2 karakter)!']);
    exit;
}
$name = mb_substr($name, 0, 30);
$sid = session_id();

try {
    $pdo = db();
    $stmt = $pdo->prepare("SELECT id, name, xp FROM players WHERE session_id = ?");
    $stmt->execute([$sid]);
    $player = $stmt->fetch();

    if (!$player) {
        $pdo->prepare("INSERT INTO players (session_id, name, xp) VALUES (?, ?, 100)")->execute([$sid, $name]);
        $id = $pdo->lastInsertId();
        // Give starter cards
        $starterCards = ['ko','ko','ko','bot','bot','levél','levél','toboz','toboz','ko'];
        foreach ($starterCards as $card) {
            $pdo->prepare("INSERT INTO player_cards (player_id, card_type, quantity) VALUES (?,?,1) ON DUPLICATE KEY UPDATE quantity=quantity+1")->execute([$id, $card]);
        }
        $player = ['id' => $id, 'name' => $name, 'xp' => 100];
    } else {
        $pdo->prepare("UPDATE players SET name=? WHERE session_id=?")->execute([$name, $sid]);
        $player['name'] = $name;
    }
    $_SESSION['player_id'] = $player['id'];
    $_SESSION['player_name'] = $player['name'];
    echo json_encode(['ok' => true, 'player' => $player]);
} catch (Exception $e) {
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
