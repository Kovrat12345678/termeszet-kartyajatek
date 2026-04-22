<?php
require_once __DIR__ . '/config.php';
$pid    = $_SESSION['player_id'] ?? null;
$roomId = (int)($_GET['room_id'] ?? 0);
$num    = (int)($_GET['num'] ?? 1);

if (!$pid || !$roomId) { header('Location: index.php'); exit; }

$stmt = db()->prepare("SELECT * FROM rooms r JOIN players p ON p.id=? WHERE r.id=?");
$stmt->execute([$pid, $roomId]);
if (!$stmt->fetch()) { header('Location: index.php'); exit; }
?>
<!DOCTYPE html>
<html lang="hu">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Csata - Természet Kártyajáték</title>
<link rel="stylesheet" href="css/style.css">
<style>
body { overflow:hidden; }
#game-wrapper {
    display:flex; flex-direction:column; height:100vh;
    padding:8px; gap:8px; position:relative; z-index:1;
}
#opp-area  { display:flex; justify-content:space-between; align-items:flex-start; padding:8px 12px; }
#mid-area  { flex:1; display:flex; flex-direction:column; justify-content:center; gap:8px; padding:0 12px; }
#my-area   { padding:8px 12px; }
#opp-hand-back { display:flex; gap:6px; padding:8px 0; justify-content:center; }
.card-back {
    width:70px; height:100px; border-radius:10px;
    background:linear-gradient(135deg,#1a3a1a,#0d2010);
    border:1px solid #2d6a2d;
    display:flex; align-items:center; justify-content:center;
    font-size:1.4rem; opacity:0.7;
    box-shadow:0 0 10px rgba(34,197,94,0.1);
}
</style>
</head>
<body>
<div id="particles"></div>

<!-- Waiting overlay -->
<div class="overlay" id="waiting-overlay">
    <div class="waiting-box">
        <div style="font-size:2rem;margin-bottom:16px">⏳</div>
        <h2 style="margin-bottom:12px">Várás a másik játékosra...</h2>
        <div class="spinner"></div>
        <a href="index.php" class="btn-secondary btn-sm" style="margin-top:16px">Vissza a főmenübe</a>
    </div>
</div>

<!-- Game over overlay -->
<div class="overlay" id="gameover-overlay"></div>

<!-- Main game layout -->
<div id="game-wrapper">
    <!-- Opponent -->
    <div id="opp-area">
        <div class="hp-wrapper">
            <div class="hp-header">
                <span class="char-emoji" id="opp-emoji">👑</span>
                <span class="player-name" id="opp-name">Ellenfél</span>
                <span class="bleed-badge" id="opp-bleed"></span>
            </div>
            <div id="opp-hp">
                <div class="hp-bar"><div class="hp-fill" style="width:100%"></div></div>
                <div class="hp-text">200 / 200 HP</div>
            </div>
        </div>
        <div>
            <div id="opp-hand-back"></div>
        </div>
    </div>

    <!-- Middle -->
    <div id="mid-area">
        <div id="action-log">Csata kezdődik! 🌿</div>
        <div class="turn-indicator opp-turn" id="turn-indicator">⏳ Betöltés...</div>
    </div>

    <!-- My area -->
    <div id="my-area">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;padding:0 4px">
            <div class="hp-wrapper" style="flex:1;max-width:300px">
                <div class="hp-header">
                    <span class="char-emoji" id="my-emoji">🌿</span>
                    <span class="player-name" id="my-name">Te</span>
                    <span class="bleed-badge" id="my-bleed"></span>
                </div>
                <div id="my-hp">
                    <div class="hp-bar"><div class="hp-fill" style="width:100%"></div></div>
                    <div class="hp-text">100 / 100 HP</div>
                </div>
            </div>
            <div style="text-align:right;color:var(--text-dim);font-size:.75rem">
                <div>Szoba: <strong style="color:var(--gold);letter-spacing:2px"><?=htmlspecialchars($_GET['room_id']??'')?></strong></div>
                <a href="index.php" class="btn-secondary btn-sm" style="margin-top:6px;display:inline-block">🏠 Főmenü</a>
            </div>
        </div>
        <div id="hand-container"></div>
    </div>
</div>

<script src="js/cards.js"></script>
<script src="js/music.js"></script>
<script src="js/game.js"></script>
<script>
// Fix: hp-wrapper children need to reference wrapper divs correctly
// Override updateHP for this page's structure
Game.init(<?=$roomId?>, <?=$num?>);

// Generate opponent card backs
function renderOppHandBacks(count) {
    const c = document.getElementById('opp-hand-back');
    c.innerHTML = '';
    for (let i=0;i<Math.min(count,8);i++) {
        const d=document.createElement('div');d.className='card-back';d.textContent='🌿';
        c.appendChild(d);
    }
}

// Patch fetchState to also update opp hand backs
const origFetch = Game.init;
setInterval(async()=>{
    try {
        const r = await fetch(`api/room.php?action=state&room_id=<?=$roomId?>`);
        const d = await r.json();
        if (d.ok && d.myHand) renderOppHandBacks(5);
    } catch(e) {}
}, 2500);

// Particles
(function(){
    const c=document.getElementById('particles');
    const em=['🍃','🍂','🌿'];
    setInterval(()=>{
        const p=document.createElement('div');p.className='particle';
        p.textContent=em[Math.floor(Math.random()*em.length)];
        p.style.left=Math.random()*100+'vw';
        p.style.animationDuration=(3+Math.random()*4)+'s';
        p.style.fontSize=(8+Math.random()*10)+'px';
        c.appendChild(p);setTimeout(()=>p.remove(),7000);
    },600);
})();
</script>
</body>
</html>
