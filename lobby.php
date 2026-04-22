<?php
require_once __DIR__ . '/config.php';
$pid = $_SESSION['player_id'] ?? null;
if (!$pid) { header('Location: index.php'); exit; }
$stmt = db()->prepare("SELECT name, xp FROM players WHERE id=?");
$stmt->execute([$pid]);
$player = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="hu">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Lobby - Természet Kártyajáték</title>
<link rel="stylesheet" href="css/style.css">
</head>
<body>
<div id="particles"></div>

<nav class="top-nav">
    <span class="nav-logo">🌿 TERMÉSZET</span>
    <div class="nav-links">
        <span class="xp-badge">⭐ <?=$player['xp']?> XP</span>
        <a href="index.php">Főmenü</a>
        <a href="shop.php">Shop</a>
    </div>
</nav>

<div class="page mt-nav">
    <div class="panel" style="max-width:560px">
        <h1 class="page-title" style="text-align:center">⚔️ <span>Csata</span> Választó</h1>
        <p class="subtitle" style="text-align:center">Válassz karaktert és indíts csatát!</p>

        <!-- Character selection -->
        <div class="input-group">
            <label>Válassz Karaktert</label>
        </div>
        <div class="char-grid" id="char-grid">
            <!-- Filled by JS -->
        </div>
        <input type="hidden" id="selected-char" value="kiraly">

        <div class="gap"></div>

        <!-- New game -->
        <div style="display:flex;flex-direction:column;gap:10px">
            <button class="btn-main btn-full" onclick="createRoom(false)">⚔️ Új Játék (2 játékos)</button>
            <button class="btn-danger btn-full" onclick="createRoom(true)">🤖 Játék AI ellen</button>
        </div>

        <div class="divider">vagy csatlakozz</div>

        <div class="input-group">
            <label>Szoba Kód</label>
            <input type="text" id="room-code" placeholder="Pl: AB3XK" maxlength="5" style="text-transform:uppercase;letter-spacing:4px;font-size:1.2rem;text-align:center">
        </div>
        <button class="btn-secondary btn-full" onclick="joinRoom()">🔗 Csatlakozás</button>

        <div id="lobby-msg" class="shop-msg"></div>

        <!-- Code display after create -->
        <div id="code-display" style="display:none">
            <div class="code-box">
                <div class="code-value" id="room-code-display"></div>
                <div class="code-hint">Küldd el ezt a kódot a barátodnak!</div>
            </div>
            <div style="text-align:center;color:var(--text-dim);font-size:.85rem">Várás a másik játékosra...</div>
            <div class="spinner" style="margin:16px auto"></div>
            <button class="btn-secondary btn-full btn-sm" onclick="document.getElementById('code-display').style.display='none'">Mégse</button>
        </div>
    </div>
</div>

<script src="js/cards.js"></script>
<script src="js/music.js"></script>
<script>
let currentRoomId = null;
let pollTimer = null;

// Render char grid
const chars = Object.entries(CHARACTERS);
const grid = document.getElementById('char-grid');
chars.forEach(([key, c]) => {
    const el = document.createElement('div');
    el.className = 'char-card' + (key==='kiraly'?' selected':'');
    el.dataset.key = key;
    el.innerHTML = `<span class="big-emoji">${c.emoji}</span><span class="char-name">${c.name}</span><span class="char-hp">❤️ ${c.hp} HP</span><span class="char-pass">${c.passive}</span>`;
    el.onclick = () => selectChar(key);
    grid.appendChild(el);
});

function selectChar(key) {
    document.querySelectorAll('.char-card').forEach(c => c.classList.remove('selected'));
    document.querySelector(`[data-key="${key}"]`)?.classList.add('selected');
    document.getElementById('selected-char').value = key;
}

async function createRoom(isAi) {
    const char = document.getElementById('selected-char').value;
    const fd = new FormData();
    fd.append('action','create'); fd.append('char',char); fd.append('ai',isAi?1:0);
    const r = await fetch('api/room.php',{method:'POST',body:fd});
    const data = await r.json();
    if (!data.ok) { showMsg(data.error,'error'); return; }

    if (isAi) {
        window.location.href = 'game.php?room_id='+data.room_id+'&num=1';
        return;
    }
    currentRoomId = data.room_id;
    document.getElementById('room-code-display').textContent = data.code;
    document.getElementById('code-display').style.display = 'block';
    pollTimer = setInterval(pollRoom, 2000);
}

async function joinRoom() {
    const code = document.getElementById('room-code').value.trim().toUpperCase();
    const char = document.getElementById('selected-char').value;
    if (!code || code.length !== 5) { showMsg('Adj meg egy 5 karakteres kódot!','error'); return; }
    const fd = new FormData();
    fd.append('action','join'); fd.append('code',code); fd.append('char',char);
    const r = await fetch('api/room.php',{method:'POST',body:fd});
    const data = await r.json();
    if (!data.ok) { showMsg(data.error,'error'); return; }
    window.location.href = 'game.php?room_id='+data.room_id+'&num=2';
}

async function pollRoom() {
    if (!currentRoomId) return;
    const r = await fetch(`api/room.php?action=state&room_id=${currentRoomId}`);
    const data = await r.json();
    if (data.ok && data.status === 'playing') {
        clearInterval(pollTimer);
        window.location.href = 'game.php?room_id='+currentRoomId+'&num=1';
    }
}

function showMsg(msg, type='info') {
    const el = document.getElementById('lobby-msg');
    el.textContent = msg; el.className='shop-msg '+type; el.style.display='block';
    setTimeout(()=>el.style.display='none',4000);
}

// Particles
(function(){
    const c = document.getElementById('particles');
    const em = ['🍃','🍂','🌿','🌲','🪨'];
    setInterval(()=>{
        const p=document.createElement('div');p.className='particle';
        p.textContent=em[Math.floor(Math.random()*em.length)];
        p.style.left=Math.random()*100+'vw';
        p.style.animationDuration=(4+Math.random()*5)+'s';
        p.style.fontSize=(10+Math.random()*14)+'px';
        c.appendChild(p);setTimeout(()=>p.remove(),9000);
    },500);
})();
</script>
</body>
</html>
