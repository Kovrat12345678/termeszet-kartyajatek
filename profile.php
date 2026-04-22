<?php
require_once __DIR__ . '/config.php';
$pid = $_SESSION['player_id'] ?? null;
if (!$pid) { header('Location: index.php'); exit; }
$pdo = db();
$stmt = $pdo->prepare("SELECT name, xp FROM players WHERE id=?");
$stmt->execute([$pid]);
$player = $stmt->fetch();
$c = $pdo->prepare("SELECT card_type, quantity FROM player_cards WHERE player_id=? ORDER BY quantity DESC");
$c->execute([$pid]);
$cards = $c->fetchAll();
?>
<!DOCTYPE html>
<html lang="hu">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Profil - Természet Kártyajáték</title>
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
        <a href="lobby.php">Játék</a>
    </div>
</nav>

<div class="page mt-nav" style="padding-top:80px">
    <div class="panel" style="max-width:600px;width:100%">
        <div style="text-align:center;margin-bottom:28px">
            <div style="font-size:3rem">🌿</div>
            <h1 style="font-size:1.8rem;font-weight:900;margin:8px 0"><?=htmlspecialchars($player['name'])?></h1>
            <div class="xp-badge" style="font-size:1rem;padding:8px 20px">⭐ <?=$player['xp']?> XP</div>
        </div>

        <h2 style="margin-bottom:12px;font-size:1rem;color:var(--text-dim);letter-spacing:2px;text-transform:uppercase">📦 Kártyáim</h2>
        <?php if (empty($cards)): ?>
            <p style="color:var(--text-dim);text-align:center;padding:20px">Még nincs kártyád. Menj a shopba!</p>
            <a href="shop.php" class="btn-main btn-full">🛒 Shop</a>
        <?php else: ?>
        <div id="my-cards" style="display:flex;flex-wrap:wrap;gap:10px;justify-content:center;padding:8px 0"></div>
        <?php endif; ?>

        <div class="gap"></div>
        <div style="display:flex;gap:10px;flex-wrap:wrap">
            <a href="lobby.php" class="btn-main" style="flex:1">⚔️ Játék</a>
            <a href="shop.php" class="btn-secondary" style="flex:1">🛒 Shop</a>
        </div>
    </div>
</div>

<script src="js/cards.js"></script>
<script src="js/music.js"></script>
<script>
const myCards = <?=json_encode($cards)?>;
const container = document.getElementById('my-cards');
if (container && myCards) {
    myCards.forEach((row, i) => {
        const el = buildCardEl(row.card_type, i, false);
        if (!el) return;
        const badge = document.createElement('div');
        badge.style.cssText='position:absolute;top:4px;right:4px;background:var(--gold);color:#000;font-size:.65rem;font-weight:900;padding:2px 6px;border-radius:8px';
        badge.textContent = '×'+row.quantity;
        el.style.position='relative';
        el.appendChild(badge);
        container.appendChild(el);
    });
}
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
</script>
</body>
</html>
