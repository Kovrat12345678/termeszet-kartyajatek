<?php
require_once __DIR__ . '/config.php';

// Auto-setup DB on first visit
try { db(); } catch(Exception $e) {
    // DB doesn't exist yet, run setup
    include __DIR__ . '/db/init.php';
    exit;
}

$pid = $_SESSION['player_id'] ?? null;
$playerData = null;
if ($pid) {
    try {
        $stmt = db()->prepare("SELECT name, xp FROM players WHERE id=?");
        $stmt->execute([$pid]);
        $playerData = $stmt->fetch();
    } catch(Exception $e) {}
}
?>
<!DOCTYPE html>
<html lang="hu">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Természet Kártyajáték</title>
<link rel="stylesheet" href="css/style.css">
</head>
<body>
<div id="particles"></div>

<div class="page">
    <div class="panel" style="max-width:440px">
        <div style="text-align:center;margin-bottom:28px">
            <div class="logo">🌿 TERMÉSZET</div>
            <div class="logo-sub">Kártyajáték</div>
        </div>

        <?php if (!$playerData): ?>
        <!-- Name input screen -->
        <div id="name-screen">
            <div class="input-group">
                <label>Neved</label>
                <input type="text" id="player-name" placeholder="Írd be a neved..." maxlength="30" autocomplete="off">
            </div>
            <button class="btn-main btn-full" onclick="startGame()">🎮 Játék Indítása</button>
            <div id="name-error" style="color:#fca5a5;text-align:center;margin-top:8px;font-size:.85rem;display:none"></div>
        </div>
        <?php else: ?>
        <!-- Main menu -->
        <div class="name-section">
            <div style="font-size:.85rem;color:var(--text-dim)">Üdvözöljük,</div>
            <div style="font-size:1.4rem;font-weight:900;margin:4px 0"><?=htmlspecialchars($playerData['name'])?></div>
            <div class="xp-badge">⭐ <?=$playerData['xp']?> XP</div>
        </div>
        <div class="menu-buttons">
            <a href="lobby.php" class="btn-main">⚔️ Játék Indítása</a>
            <a href="shop.php"  class="btn-secondary">🛒 Shop - Kártyák</a>
            <a href="profile.php" class="btn-secondary">📊 Profil &amp; Kártyáim</a>
            <button class="btn-secondary btn-sm" onclick="document.getElementById('name-screen-change').style.display='block';this.style.display='none'">✏️ Név Módosítása</button>
        </div>
        <div id="name-screen-change" style="display:none;margin-top:16px">
            <div class="input-group">
                <label>Új Neved</label>
                <input type="text" id="player-name" placeholder="Írd be az új neved..." maxlength="30" value="<?=htmlspecialchars($playerData['name'])?>">
            </div>
            <button class="btn-main btn-full" onclick="startGame()">✅ Módosítás</button>
        </div>
        <?php endif; ?>
    </div>
</div>

<script src="js/music.js"></script>
<script>
function startGame() {
    const name = document.getElementById('player-name')?.value?.trim();
    if (!name || name.length < 2) {
        const err = document.getElementById('name-error');
        if (err) { err.textContent = 'Adj meg egy nevet (min 2 karakter)!'; err.style.display='block'; }
        return;
    }
    const btn = event.target;
    btn.disabled = true; btn.textContent = 'Betöltés...';
    const fd = new FormData();
    fd.append('name', name);
    fetch('api/init.php', { method:'POST', body:fd })
        .then(r => r.json())
        .then(data => {
            if (data.ok) location.reload();
            else {
                btn.disabled = false; btn.textContent = '🎮 Játék Indítása';
                const err = document.getElementById('name-error');
                if (err) { err.textContent = data.error; err.style.display='block'; }
            }
        });
}
document.getElementById('player-name')?.addEventListener('keydown', e => { if(e.key==='Enter') startGame(); });

// Falling particles
(function(){
    const c = document.getElementById('particles');
    const em = ['🍃','🍂','🌿','🌲','🪨','🌱'];
    setInterval(()=>{
        const p = document.createElement('div');
        p.className='particle'; p.textContent=em[Math.floor(Math.random()*em.length)];
        p.style.left=Math.random()*100+'vw';
        p.style.animationDuration=(4+Math.random()*5)+'s';
        p.style.fontSize=(10+Math.random()*14)+'px';
        c.appendChild(p);
        setTimeout(()=>p.remove(),9000);
    },500);
})();
</script>
</body>
</html>
