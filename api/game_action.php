<?php
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json');

$pid = $_SESSION['player_id'] ?? null;
$roomId = (int)($_POST['room_id'] ?? 0);
$cardIdx = (int)($_POST['card_idx'] ?? 0);

if (!$pid || !$roomId) { echo json_encode(['ok'=>false,'error'=>'Érvénytelen kérés']); exit; }

$CARDS = [
    'ko'         => ['name'=>'Kő',          'dmg'=>15,'heal'=>0,'bleed'=>0,'block'=>0,'emoji'=>'🪨'],
    'bot'        => ['name'=>'Bot',          'dmg'=>12,'heal'=>0,'bleed'=>3,'block'=>0,'emoji'=>'🌿'],
    'levél'      => ['name'=>'Levél',        'dmg'=>0, 'heal'=>12,'bleed'=>0,'block'=>0,'emoji'=>'🍃'],
    'toboz'      => ['name'=>'Toboz',        'dmg'=>10,'heal'=>0,'bleed'=>5,'block'=>0,'emoji'=>'🌲'],
    'vastag_bot' => ['name'=>'Vastag Bot',   'dmg'=>22,'heal'=>0,'bleed'=>0,'block'=>0,'emoji'=>'🪵'],
    'hegyes_ko'  => ['name'=>'Hegyes Kő',   'dmg'=>20,'heal'=>0,'bleed'=>0,'block'=>5,'emoji'=>'💎'],
    'tüskés_toboz'=>['name'=>'Tüskés Toboz','dmg'=>30,'heal'=>0,'bleed'=>8,'block'=>0,'emoji'=>'🌵'],
    'gyógylevél' => ['name'=>'Gyógylevél',  'dmg'=>0, 'heal'=>25,'bleed'=>0,'block'=>0,'emoji'=>'💚'],
    'kiraly_parancs'=>['name'=>'Király Parancs','dmg'=>35,'heal'=>10,'bleed'=>0,'block'=>0,'emoji'=>'👑'],
    'termeszet'  => ['name'=>'Természet Ereje','dmg'=>40,'heal'=>10,'bleed'=>0,'block'=>0,'emoji'=>'🌍'],
];

function aiPickCard(array $hand, int $myHp, int $oppHp): int {
    // AI: if low hp, prefer heal; else prefer high damage
    $best = 0;
    $bestScore = -1;
    global $CARDS;
    foreach ($hand as $i => $c) {
        $card = $CARDS[$c] ?? ['dmg'=>10,'heal'=>0,'bleed'=>0];
        $score = $card['dmg'] + $card['bleed'] * 2 + ($myHp < 50 ? $card['heal'] * 3 : $card['heal']);
        if ($score > $bestScore) { $bestScore = $score; $best = $i; }
    }
    return $best;
}

try {
    $pdo = db();
    $stmt = $pdo->prepare("SELECT * FROM rooms WHERE id=? FOR UPDATE");
    $pdo->beginTransaction();
    $stmt->execute([$roomId]);
    $room = $stmt->fetch();

    if (!$room || $room['status'] !== 'playing') {
        $pdo->rollBack();
        echo json_encode(['ok'=>false,'error'=>'A játék nem aktív']); exit;
    }

    $isP1 = ($room['player1_id'] == $pid);
    $isP2 = ($room['player2_id'] == $pid);
    if (!$isP1 && !$isP2) { $pdo->rollBack(); echo json_encode(['ok'=>false,'error'=>'Nem vagy ebben a szobában']); exit; }

    $myNum = $isP1 ? 1 : 2;
    if ($room['current_turn'] != $myNum) { $pdo->rollBack(); echo json_encode(['ok'=>false,'error'=>'Nem a te köröd!']); exit; }

    $myHand = json_decode($isP1 ? $room['p1_hand'] : $room['p2_hand'], true) ?: [];
    $myDeck = json_decode($isP1 ? $room['p1_deck'] : $room['p2_deck'], true) ?: [];

    if (!isset($myHand[$cardIdx])) { $pdo->rollBack(); echo json_encode(['ok'=>false,'error'=>'Érvénytelen kártya']); exit; }

    $playedCard = $myHand[$cardIdx];
    $card = $CARDS[$playedCard] ?? ['name'=>$playedCard,'dmg'=>10,'heal'=>0,'bleed'=>0,'block'=>0,'emoji'=>'❓'];

    // Remove played card from hand, draw new one
    array_splice($myHand, $cardIdx, 1);
    if (count($myDeck) > 0) $myHand[] = array_shift($myDeck);

    // Apply effects
    $p1hp = (int)$room['p1_hp'];
    $p2hp = (int)$room['p2_hp'];
    $p1bleed = (int)$room['p1_bleed'];
    $p2bleed = (int)$room['p2_bleed'];

    // Apply bleed damage at start of turn
    if ($isP1) { $p1hp -= $p1bleed; $p1bleed = max(0, $p1bleed - 1); }
    else       { $p2hp -= $p2bleed; $p2bleed = max(0, $p2bleed - 1); }

    $actionMsg = '';
    if ($card['dmg'] > 0) {
        $dmg = $card['dmg'];
        // Király karakter: +20% dmg
        $myChar = $isP1 ? $room['p1_char'] : $room['p2_char'];
        if ($myChar === 'kiraly') $dmg = (int)($dmg * 1.2);
        if ($myChar === 'vastag_bot') $dmg += 5;
        $actionMsg .= $card['emoji'].' '.$card['name'].': '.$dmg.' sebzés! ';
        if ($isP1) $p2hp -= $dmg; else $p1hp -= $dmg;
    }
    if ($card['heal'] > 0) {
        $heal = $card['heal'];
        $myChar = $isP1 ? $room['p1_char'] : $room['p2_char'];
        if ($myChar === 'leveles') $heal = (int)($heal * 1.5);
        $actionMsg .= '💚 Gyógyulás: +'.$heal.'hp! ';
        $maxHp = $isP1 ? (int)$room['p1_hp'] : (int)$room['p2_hp'];
        if ($isP1) $p1hp = min($p1hp + $heal, 200); else $p2hp = min($p2hp + $heal, 200);
    }
    if ($card['bleed'] > 0) {
        $actionMsg .= '🩸 Vérzés: +'.$card['bleed'].'! ';
        if ($isP1) $p2bleed += $card['bleed']; else $p1bleed += $card['bleed'];
    }

    $p1hp = max(0, $p1hp);
    $p2hp = max(0, $p2hp);

    $winner = null;
    $status = 'playing';
    if ($p1hp <= 0) { $winner = 2; $status = 'finished'; }
    if ($p2hp <= 0) { $winner = 1; $status = 'finished'; }

    $nextTurn = $isP1 ? 2 : 1;

    // Handle AI turn if needed
    $aiAction = '';
    if ($status === 'playing' && $room['is_ai'] && $nextTurn === 2) {
        $aiHand = json_decode($room['p2_hand'], true) ?: [];
        // Remove the card we just modified from p2 perspective - no, ai's hand unchanged
        $aiDeck = $myDeck; // reuse variable name — ai deck
        $aiDeck = json_decode($room['p2_deck'], true) ?: [];

        // Apply p2 bleed
        $p2hp -= $p2bleed;
        $p2bleed = max(0, $p2bleed - 1);

        $aiIdx = aiPickCard($aiHand, $p2hp, $p1hp);
        $aiCard = $aiHand[$aiIdx] ?? 'ko';
        $aiCardData = $CARDS[$aiCard] ?? ['dmg'=>10,'heal'=>0,'bleed'=>0,'emoji'=>'❓','name'=>$aiCard];
        array_splice($aiHand, $aiIdx, 1);
        if (count($aiDeck) > 0) $aiHand[] = array_shift($aiDeck);

        $aiDmg = $aiCardData['dmg'];
        $aiChar = $room['p2_char'];
        if ($aiChar === 'kiraly') $aiDmg = (int)($aiDmg * 1.2);
        if ($aiChar === 'vastag_bot') $aiDmg += 5;

        if ($aiDmg > 0) {
            $p1hp -= $aiDmg;
            $aiAction .= $aiCardData['emoji'].' AI: '.$aiCardData['name'].': '.$aiDmg.' sebzés! ';
        }
        if ($aiCardData['heal'] > 0) {
            $heal = $aiCardData['heal'];
            $p2hp = min($p2hp + $heal, 200);
            $aiAction .= '💚 AI gyógyul: +'.$heal.'hp! ';
        }
        if ($aiCardData['bleed'] > 0) {
            $p1bleed += $aiCardData['bleed'];
            $aiAction .= '🩸 AI vérzést okoz! ';
        }
        $p1hp = max(0, $p1hp);
        $p2hp = max(0, $p2hp);
        if ($p1hp <= 0) { $winner = 2; $status = 'finished'; }
        if ($p2hp <= 0) { $winner = 1; $status = 'finished'; }
        $nextTurn = 1;

        $pdo->prepare("UPDATE rooms SET p2_hand=?,p2_deck=?,p2_bleed=? WHERE id=?")->execute([
            json_encode($aiHand),json_encode($aiDeck),$p2bleed,$roomId
        ]);
    }

    // XP reward on finish
    if ($status === 'finished' && $winner) {
        $w = $winner==1 ? $room['player1_id'] : $room['player2_id'];
        $l = $winner==1 ? $room['player2_id'] : $room['player1_id'];
        if ($w) $pdo->prepare("UPDATE players SET xp=xp+50 WHERE id=?")->execute([$w]);
        if ($l && !$room['is_ai']) $pdo->prepare("UPDATE players SET xp=xp+20 WHERE id=?")->execute([$l]);
    }

    $handField = $isP1 ? 'p1_hand' : 'p2_hand';
    $deckField = $isP1 ? 'p1_deck' : 'p2_deck';

    $pdo->prepare("UPDATE rooms SET p1_hp=?,p2_hp=?,p1_bleed=?,p2_bleed=?,
        $handField=?,$deckField=?,current_turn=?,status=?,winner=?,last_action=?,last_played_card=? WHERE id=?"
    )->execute([$p1hp,$p2hp,$p1bleed,$p2bleed,
        json_encode($myHand),json_encode($myDeck),
        $nextTurn,$status,$winner,
        trim($actionMsg.' '.$aiAction),$playedCard,$roomId
    ]);

    $pdo->commit();
    echo json_encode(['ok'=>true,'action'=>trim($actionMsg.' '.$aiAction),'status'=>$status,'winner'=>$winner]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
}
