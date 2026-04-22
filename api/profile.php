<?php
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json');

$pid = $_SESSION['player_id'] ?? null;
if (!$pid) { echo json_encode(['ok'=>false,'error'=>'Nincs bejelentkezve']); exit; }

try {
    $pdo = db();
    $p = $pdo->prepare("SELECT id,name,xp FROM players WHERE id=?");
    $p->execute([$pid]);
    $player = $p->fetch();

    $c = $pdo->prepare("SELECT card_type, quantity FROM player_cards WHERE player_id=? ORDER BY quantity DESC");
    $c->execute([$pid]);
    $cards = $c->fetchAll();

    echo json_encode(['ok'=>true,'player'=>$player,'cards'=>$cards]);
} catch (Exception $e) {
    echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
}
