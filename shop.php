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
<title>Shop - Természet Kártyajáték</title>
<link rel="stylesheet" href="css/style.css">
</head>
<body>
<div id="particles"></div>

<nav class="top-nav">
    <span class="nav-logo">🌿 TERMÉSZET</span>
    <div class="nav-links">
        <span class="xp-badge" id="xp-display">⭐ <?=$player['xp']?> XP</span>
        <a href="index.php">Főmenü</a>
        <a href="lobby.php">Játék</a>
    </div>
</nav>

<!-- Pack opening overlay -->
<div class="overlay" id="pack-result" style="display:none"></div>

<div class="page mt-nav" style="padding-top:80px">
    <div style="text-align:center;margin-bottom:28px">
        <h1 class="page-title">🛒 <span>Kártya</span> Shop</h1>
        <p class="subtitle">Vásárolj kártyacsomagokat XP-ért és erősítsd a paklidat!</p>
        <div class="xp-badge" style="font-size:1rem;padding:8px 20px" id="xp-display-big">⭐ <?=$player['xp']?> XP</div>
    </div>

    <div id="shop-msg" class="shop-msg"></div>

    <div class="shop-grid">
        <!-- Kis Zacskó -->
        <div class="pack-card" style="--pack-glow:rgba(156,163,175,0.15);--pack-border:#6b7280" onclick="buyPack('kis')">
            <div class="pack-emoji">👜</div>
            <div class="pack-name">Kis Zacsgó</div>
            <div class="pack-desc">3 közönséges kártya. Tökéletes kezdéshez.</div>
            <div class="pack-count">📦 3 kártya</div>
            <div class="pack-xp">50 XP</div>
            <button class="btn-secondary btn-sm" data-pack="kis">Megvesz</button>
        </div>

        <!-- Közepes Zacskó -->
        <div class="pack-card" style="--pack-glow:rgba(59,130,246,0.15);--pack-border:#3b82f6" onclick="buyPack('kozepes')">
            <div class="pack-emoji">🎒</div>
            <div class="pack-name">Közepes Zacsgó</div>
            <div class="pack-desc">4 kártya, közte ritka is lehet!</div>
            <div class="pack-count">📦 4 kártya</div>
            <div class="pack-xp">100 XP</div>
            <button class="btn-secondary btn-sm" data-pack="kozepes">Megvesz</button>
        </div>

        <!-- Nagy Zacskó -->
        <div class="pack-card" style="--pack-glow:rgba(168,85,247,0.15);--pack-border:#a855f7" onclick="buyPack('nagy')">
            <div class="pack-emoji">💼</div>
            <div class="pack-name">Nagy Zacsgó</div>
            <div class="pack-desc">5 kártya, garantált ritka kártya!</div>
            <div class="pack-count">📦 5 kártya</div>
            <div class="pack-xp">200 XP</div>
            <button class="btn-main btn-sm" data-pack="nagy">Megvesz</button>
        </div>

        <!-- Király Zacskó -->
        <div class="pack-card" style="--pack-glow:rgba(251,191,36,0.2);--pack-border:#fbbf24;background:rgba(20,10,0,0.7)" onclick="buyPack('kiraly')">
            <div class="pack-emoji" style="filter:drop-shadow(0 0 15px gold)">👑</div>
            <div class="pack-name" style="color:var(--gold)">Király Zacsgó</div>
            <div class="pack-desc">Legendás kártyák garantálva!</div>
            <div class="pack-count">📦 5 kártya</div>
            <div class="pack-xp" style="font-size:1.3rem">500 XP</div>
            <button class="btn-main btn-sm" style="background:linear-gradient(135deg,#92400e,#fbbf24)" data-pack="kiraly">👑 Megvesz</button>
        </div>
    </div>

    <!-- All cards reference -->
    <div style="margin-top:40px;width:100%;max-width:900px">
        <h2 style="text-align:center;margin-bottom:16px;font-size:1.3rem">📖 Összes Kártya</h2>
        <div id="all-cards" style="display:flex;flex-wrap:wrap;gap:10px;justify-content:center;padding:16px"></div>
    </div>
</div>

<script src="js/cards.js"></script>
<script src="js/music.js"></script>
<script src="js/shop.js"></script>
<script>
// Render all cards
const allCards = document.getElementById('all-cards');
Object.entries(CARDS).forEach(([type, _], i) => {
    const el = buildCardEl(type, i, false);
    if (el) allCards.appendChild(el);
});

// Particles
(function(){
    const c=document.getElementById('particles');
    const em=['🍃','💎','👑','🌲','🪨'];
    setInterval(()=>{
        const p=document.createElement('div');p.className='particle';
        p.textContent=em[Math.floor(Math.random()*em.length)];
        p.style.left=Math.random()*100+'vw';
        p.style.animationDuration=(4+Math.random()*5)+'s';
        p.style.fontSize=(10+Math.random()*14)+'px';
        c.appendChild(p);setTimeout(()=>p.remove(),9000);
    },500);
})();

// Sync xp displays
function syncXp(xp) {
    document.querySelectorAll('#xp-display,#xp-display-big').forEach(el => el.textContent = '⭐ '+xp+' XP');
}
</script>
</body>
</html>
