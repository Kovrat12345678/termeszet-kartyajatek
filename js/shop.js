const PACKS = {
    kis:     { name:'Kis Zacskó',     emoji:'👜', xp:50,  count:3, color:'#4b5563', glow:'#9ca3af', desc:'3 közönséges kártya. Jó kezdéshez.' },
    kozepes: { name:'Közepes Zacskó', emoji:'🎒', xp:100, count:4, color:'#1d4ed8', glow:'#3b82f6', desc:'4 kártya, közte ritka is lehet.' },
    nagy:    { name:'Nagy Zacskó',    emoji:'💼', xp:200, count:5, color:'#6d28d9', glow:'#a855f7', desc:'5 kártya, garantált ritka.' },
    kiraly:  { name:'Király Zacsgó',  emoji:'👑', xp:500, count:5, color:'#92400e', glow:'#f59e0b', desc:'Legendás lehetőség - Király kártyák!' },
};

async function buyPack(packId) {
    const btn = document.querySelector(`[data-pack="${packId}"]`);
    if (btn) btn.disabled = true;
    try {
        const fd = new FormData();
        fd.append('pack', packId);
        const r = await fetch('api/shop_action.php', { method:'POST', body:fd });
        const data = await r.json();
        if (!data.ok) {
            showMsg(data.error, 'error');
            if (btn) btn.disabled = false;
            return;
        }
        showPackResult(data.cards, data.packName, data.newXp);
        document.getElementById('xp-display').textContent = data.newXp + ' XP';
    } catch(e) {
        showMsg('Hiba történt!', 'error');
        if (btn) btn.disabled = false;
    }
}

function showPackResult(cards, packName, newXp) {
    const overlay = document.getElementById('pack-result');
    const list = cards.map(c => {
        const card = CARDS[c];
        return card ? `<div class="result-card" style="--card-glow:${card.glow}"><span class="big-emoji">${card.emoji}</span><span>${card.name}</span></div>` : '';
    }).join('');
    overlay.innerHTML = `
        <div class="pack-result-box">
            <h2>📦 ${packName}</h2>
            <div class="result-cards">${list}</div>
            <p>Maradék XP: <strong>${newXp}</strong></p>
            <button class="btn-main" onclick="document.getElementById('pack-result').style.display='none'">Bezárás</button>
        </div>`;
    overlay.style.display = 'flex';
    // Animate cards in
    setTimeout(() => {
        overlay.querySelectorAll('.result-card').forEach((el, i) => {
            el.style.animationDelay = i*0.15+'s';
            el.classList.add('result-card-in');
        });
    }, 50);
}

function showMsg(msg, type='info') {
    const el = document.getElementById('shop-msg');
    el.textContent = msg;
    el.className = 'shop-msg ' + type;
    el.style.display = 'block';
    setTimeout(() => el.style.display='none', 3000);
}
