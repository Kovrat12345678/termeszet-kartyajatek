const CARDS = {
    ko:            { name:'Kő',            emoji:'🪨', dmg:15, heal:0,  bleed:0,  rarity:'common',    color:'#6b7280', glow:'#9ca3af', desc:'Kemény ütés a sziklával.' },
    bot:           { name:'Bot',           emoji:'🌿', dmg:12, heal:0,  bleed:3,  rarity:'common',    color:'#92400e', glow:'#d97706', desc:'Sebez és 3 vérzést okoz.' },
    'levél':       { name:'Levél',         emoji:'🍃', dmg:0,  heal:12, bleed:0,  rarity:'common',    color:'#065f46', glow:'#10b981', desc:'Természet gyógyereje.' },
    toboz:         { name:'Toboz',         emoji:'🌲', dmg:10, heal:0,  bleed:5,  rarity:'common',    color:'#7c3aed', glow:'#8b5cf6', desc:'Szúrós támadás, 5 vérzés.' },
    vastag_bot:    { name:'Vastag Bot',    emoji:'🪵', dmg:22, heal:0,  bleed:0,  rarity:'uncommon',  color:'#92400e', glow:'#f59e0b', desc:'Erős csapás a vastag bottal.' },
    hegyes_ko:     { name:'Hegyes Kő',    emoji:'💎', dmg:20, heal:0,  bleed:0,  rarity:'uncommon',  color:'#1e40af', glow:'#3b82f6', desc:'20 sebzés + blokk következő támadásra.' },
    'gyógylevél':  { name:'Gyógylevél',   emoji:'💚', dmg:0,  heal:25, bleed:0,  rarity:'uncommon',  color:'#065f46', glow:'#34d399', desc:'Erős gyógyulás természet erejével.' },
    'tüskés_toboz':{ name:'Tüskés Toboz', emoji:'🌵', dmg:30, heal:0,  bleed:8,  rarity:'rare',      color:'#7c2d12', glow:'#ef4444', desc:'Nagy sebzés + erős vérzés.' },
    kiraly_parancs:{ name:'Király Parancs',emoji:'👑', dmg:35, heal:10, bleed:0,  rarity:'rare',      color:'#78350f', glow:'#fbbf24', desc:'Királyi csapás: 35 sebzés és 10 gyógyulás.' },
    termeszet:     { name:'Természet Ereje',emoji:'🌍',dmg:40, heal:10, bleed:0,  rarity:'legendary', color:'#14532d', glow:'#22c55e', desc:'A természet teljes ereje: 40 sebzés, 10 gyógyulás.' },
};

const CHARACTERS = {
    kiraly:     { name:'Király',       emoji:'👑', hp:200, color:'#fbbf24', glow:'#ffd700', passive:'Király: +20% kártyasebzés', desc:'A legerősebb harcos, arany koronával.' },
    vastag_bot: { name:'Vastag Bot',   emoji:'🪵', hp:150, color:'#d97706', glow:'#f59e0b', passive:'Vastag Bot: +5 sebzés minden kártyára', desc:'Masszív fabot harcos.' },
    ko_szikla:  { name:'Kőszikla',    emoji:'🪨', hp:180, color:'#6b7280', glow:'#9ca3af', passive:'Kőszikla: blokkolja az első 2 támadást', desc:'Sziklaszilárd védekezés.' },
    tobozos:    { name:'Tobozos',      emoji:'🌲', hp:130, color:'#8b5cf6', glow:'#7c3aed', passive:'Tobozos: vérzés kártyák duplán hatnak', desc:'A tüskés erdő mestere.' },
    leveles:    { name:'Leveles',      emoji:'🍃', hp:110, color:'#10b981', glow:'#34d399', passive:'Leveles: minden gyógyulás +50%', desc:'A természet gyógyítója.' },
    bot:        { name:'Bot Harcos',   emoji:'🌿', hp:80,  color:'#b45309', glow:'#d97706', passive:'Bot: vérzés kártyák +50% hatás', desc:'Gyors és vérzős bot harcos.' },
};

const RARITY_COLORS = {
    common:    { bg:'#1f2937', border:'#4b5563', glow:'rgba(156,163,175,0.3)' },
    uncommon:  { bg:'#1a2744', border:'#3b82f6', glow:'rgba(59,130,246,0.4)' },
    rare:      { bg:'#2d1a3a', border:'#a855f7', glow:'rgba(168,85,247,0.5)' },
    legendary: { bg:'#2d1a0a', border:'#f59e0b', glow:'rgba(245,158,11,0.6)' },
};

function buildCardEl(cardType, index, isPlayable=true) {
    const c = CARDS[cardType];
    if (!c) return null;
    const r = RARITY_COLORS[c.rarity];
    const el = document.createElement('div');
    el.className = 'card' + (isPlayable ? ' playable' : '');
    el.dataset.idx = index;
    el.dataset.type = cardType;
    el.style.cssText = `--card-border:${r.border};--card-bg:${r.bg};--card-glow:${r.glow};--card-color:${c.glow};`;
    el.innerHTML = `
        <div class="card-rarity">${c.rarity.toUpperCase()}</div>
        <div class="card-emoji">${c.emoji}</div>
        <div class="card-name">${c.name}</div>
        <div class="card-stats">
            ${c.dmg  ? `<span class="stat dmg">⚔️ ${c.dmg}</span>` : ''}
            ${c.heal ? `<span class="stat heal">💚 ${c.heal}</span>` : ''}
            ${c.bleed? `<span class="stat bleed">🩸 ${c.bleed}</span>` : ''}
        </div>
        <div class="card-desc">${c.desc}</div>
    `;
    return el;
}
