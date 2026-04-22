<?php
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json');

$pid = $_SESSION['player_id'] ?? null;
$pack = $_POST['pack'] ?? '';

if (!$pid) { echo json_encode(['ok'=>false,'error'=>'Nincs bejelentkezve']); exit; }

$PACKS = [
    'kis'    => ['name'=>'Kis Zacskó',    'xp'=>50,  'count'=>3, 'pool'=>['ko','ko','bot','bot','levél','toboz','toboz']],
    'kozepes'=> ['name'=>'Közepes Zacskó','xp'=>100, 'count'=>4, 'pool'=>['ko','bot','levél','toboz','vastag_bot','hegyes_ko','gyógylevél']],
    'nagy'   => ['name'=>'Nagy Zacskó',   'xp'=>200, 'count'=>5, 'pool'=>['vastag_bot','hegyes_ko','gyógylevél','tüskés_toboz','kiraly_parancs']],
    'kiraly' => ['name'=>'Király Zacskó', 'xp'=>500, 'count'=>5, 'pool'=>['tüskés_toboz','kiraly_parancs','termeszet','kiraly_parancs','termeszet']],
];

if (!isset($PACKS[$pack])) { echo json_encode(['ok'=>false,'error'=>'Érvénytelen csomag']); exit; }

try {
    $pdo = db();
    $stmt = $pdo->prepare("SELECT xp FROM players WHERE id=?");
    $stmt->execute([$pid]);
    $player = $stmt->fetch();
    if (!$player) { echo json_encode(['ok'=>false,'error'=>'Játékos nem található']); exit; }

    $p = $PACKS[$pack];
    if ($player['xp'] < $p['xp']) {
        echo json_encode(['ok'=>false,'error'=>'Nincs elég XP! Kell: '.$p['xp'].', nálad: '.$player['xp']]); exit;
    }

    $pool = $p['pool'];
    $got = [];
    for ($i=0; $i<$p['count']; $i++) {
        $card = $pool[array_rand($pool)];
        $got[] = $card;
        $pdo->prepare("INSERT INTO player_cards (player_id,card_type,quantity) VALUES (?,?,1) ON DUPLICATE KEY UPDATE quantity=quantity+1")->execute([$pid,$card]);
    }

    $pdo->prepare("UPDATE players SET xp=xp-? WHERE id=?")->execute([$p['xp'],$pid]);
    $newXp = $player['xp'] - $p['xp'];

    echo json_encode(['ok'=>true,'cards'=>$got,'newXp'=>$newXp,'packName'=>$p['name']]);
} catch (Exception $e) {
    echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
}
