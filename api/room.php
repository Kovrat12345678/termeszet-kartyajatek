<?php
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$pid = $_SESSION['player_id'] ?? null;

if (!$pid) { echo json_encode(['ok'=>false,'error'=>'Nincs bejelentkezve']); exit; }

function makeDefaultDeck(): array {
    $deck = [];
    for ($i=0;$i<4;$i++) $deck[] = 'ko';
    for ($i=0;$i<4;$i++) $deck[] = 'bot';
    for ($i=0;$i<3;$i++) $deck[] = 'levél';
    for ($i=0;$i<3;$i++) $deck[] = 'toboz';
    for ($i=0;$i<2;$i++) $deck[] = 'vastag_bot';
    for ($i=0;$i<2;$i++) $deck[] = 'hegyes_ko';
    for ($i=0;$i<1;$i++) $deck[] = 'tüskés_toboz';
    for ($i=0;$i<1;$i++) $deck[] = 'gyógylevél';
    shuffle($deck);
    return $deck;
}

function dealHand(array &$deck, int $count=5): array {
    $hand = [];
    for ($i=0;$i<$count && count($deck)>0;$i++) $hand[] = array_shift($deck);
    return $hand;
}

function charHp(string $char): int {
    return match($char) { 'kiraly'=>200, 'ko_szikla'=>180, 'vastag_bot'=>150, 'tobozos'=>130, 'leveles'=>110, 'bot'=>80, default=>100 };
}

try {
    $pdo = db();

    if ($action === 'create') {
        $char = $_POST['char'] ?? 'kiraly';
        $isAi = (int)($_POST['ai'] ?? 0);
        $code = strtoupper(substr(str_shuffle('ABCDEFGHJKMNPQRSTUVWXYZ23456789'), 0, 5));

        $deck1 = makeDefaultDeck();
        $hand1 = dealHand($deck1);
        $deck2 = makeDefaultDeck();
        $hand2 = dealHand($deck2);

        $p2char = $isAi ? 'vastag_bot' : 'bot';
        $p1hp = charHp($char);
        $p2hp = charHp($p2char);

        $pdo->prepare("INSERT INTO rooms (code,player1_id,is_ai,p1_char,p2_char,p1_hp,p2_hp,p1_hand,p2_hand,p1_deck,p2_deck,status)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?)")->execute([
            $code,$pid,$isAi,$char,$p2char,$p1hp,$p2hp,
            json_encode($hand1),json_encode($hand2),
            json_encode($deck1),json_encode($deck2),
            $isAi?'playing':'waiting'
        ]);
        $roomId = $pdo->lastInsertId();
        echo json_encode(['ok'=>true,'code'=>$code,'room_id'=>$roomId]);
    }

    elseif ($action === 'join') {
        $code = strtoupper(trim($_POST['code'] ?? ''));
        $char = $_POST['char'] ?? 'bot';
        $stmt = $pdo->prepare("SELECT * FROM rooms WHERE code=? AND status='waiting'");
        $stmt->execute([$code]);
        $room = $stmt->fetch();
        if (!$room) { echo json_encode(['ok'=>false,'error'=>'Nem található szoba ezzel a kóddal!']); exit; }
        if ($room['player1_id'] == $pid) { echo json_encode(['ok'=>false,'error'=>'Saját szobádhoz nem csatlakozhatsz!']); exit; }

        $deck2 = makeDefaultDeck();
        $hand2 = dealHand($deck2);
        $p2hp = charHp($char);

        $pdo->prepare("UPDATE rooms SET player2_id=?,p2_char=?,p2_hp=?,p2_hand=?,p2_deck=?,status='playing' WHERE id=?")->execute([
            $pid,$char,$p2hp,json_encode($hand2),json_encode($deck2),$room['id']
        ]);
        echo json_encode(['ok'=>true,'room_id'=>$room['id'],'code'=>$code]);
    }

    elseif ($action === 'state') {
        $roomId = $_GET['room_id'] ?? 0;
        $stmt = $pdo->prepare("SELECT r.*, p1.name as p1_name, p2.name as p2_name FROM rooms r
            LEFT JOIN players p1 ON r.player1_id=p1.id
            LEFT JOIN players p2 ON r.player2_id=p2.id
            WHERE r.id=?");
        $stmt->execute([$roomId]);
        $room = $stmt->fetch();
        if (!$room) { echo json_encode(['ok'=>false,'error'=>'Szoba nem található']); exit; }

        $isP1 = ($room['player1_id'] == $pid);
        $isP2 = ($room['player2_id'] == $pid);
        if (!$isP1 && !$isP2) { echo json_encode(['ok'=>false,'error'=>'Nem vagy ebben a szobában']); exit; }

        $myHand = $isP1 ? json_decode($room['p1_hand'],true) : json_decode($room['p2_hand'],true);
        $myHp = $isP1 ? $room['p1_hp'] : $room['p2_hp'];
        $oppHp = $isP1 ? $room['p2_hp'] : $room['p1_hp'];
        $myChar = $isP1 ? $room['p1_char'] : $room['p2_char'];
        $oppChar = $isP1 ? $room['p2_char'] : $room['p1_char'];
        $myBleed = $isP1 ? $room['p1_bleed'] : $room['p2_bleed'];
        $oppBleed = $isP1 ? $room['p2_bleed'] : $room['p1_bleed'];
        $myNum = $isP1 ? 1 : 2;
        $isMyTurn = ($room['current_turn'] == $myNum);

        echo json_encode([
            'ok'=>true,
            'status'=>$room['status'],
            'myHand'=>$myHand,
            'myHp'=>$myHp,
            'oppHp'=>$oppHp,
            'myChar'=>$myChar,
            'oppChar'=>$oppChar,
            'myBleed'=>$myBleed,
            'oppBleed'=>$oppBleed,
            'isMyTurn'=>$isMyTurn,
            'currentTurn'=>$room['current_turn'],
            'winner'=>$room['winner'],
            'isP1'=>$isP1,
            'myNum'=>$myNum,
            'p1Name'=>$room['p1_name'] ?? 'Játékos 1',
            'p2Name'=>$room['p2_name'] ?? ($room['is_ai']?'AI Ellenfél':'Játékos 2'),
            'isAi'=>(bool)$room['is_ai'],
            'lastAction'=>$room['last_action'],
            'lastCard'=>$room['last_played_card'],
            'p1MaxHp'=>charHp($room['p1_char']),
            'p2MaxHp'=>charHp($room['p2_char'])
        ]);
    }
} catch (Exception $e) {
    echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
}
