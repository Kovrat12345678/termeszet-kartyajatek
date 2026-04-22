// Játék motor - teljes kliens oldali logika

const CARD_DATA = {
    ko:             { name:'Kő',            emoji:'🪨', dmg:15, heal:0,  bleed:0,  rarity:'common',    color:'#6b7280', glow:'#9ca3af', desc:'Kemény ütés a sziklával.' },
    bot:            { name:'Bot',           emoji:'🌿', dmg:12, heal:0,  bleed:3,  rarity:'common',    color:'#92400e', glow:'#d97706', desc:'Sebez és 3 vérzést okoz.' },
    'levél':        { name:'Levél',         emoji:'🍃', dmg:0,  heal:12, bleed:0,  rarity:'common',    color:'#065f46', glow:'#10b981', desc:'Természet gyógyereje.' },
    toboz:          { name:'Toboz',         emoji:'🌲', dmg:10, heal:0,  bleed:5,  rarity:'common',    color:'#7c3aed', glow:'#8b5cf6', desc:'Szúrós támadás, 5 vérzés.' },
    vastag_bot:     { name:'Vastag Bot',    emoji:'🪵', dmg:22, heal:0,  bleed:0,  rarity:'uncommon',  color:'#92400e', glow:'#f59e0b', desc:'Erős csapás a vastag bottal.' },
    hegyes_ko:      { name:'Hegyes Kő',    emoji:'💎', dmg:20, heal:0,  bleed:0,  rarity:'uncommon',  color:'#1e40af', glow:'#3b82f6', desc:'20 sebzés.' },
    'gyógylevél':   { name:'Gyógylevél',   emoji:'💚', dmg:0,  heal:25, bleed:0,  rarity:'uncommon',  color:'#065f46', glow:'#34d399', desc:'Erős gyógyulás.' },
    'tüskés_toboz': { name:'Tüskés Toboz', emoji:'🌵', dmg:30, heal:0,  bleed:8,  rarity:'rare',      color:'#7c2d12', glow:'#ef4444', desc:'Nagy sebzés + erős vérzés.' },
    kiraly_parancs: { name:'Király Parancs',emoji:'👑', dmg:35, heal:10, bleed:0,  rarity:'rare',      color:'#78350f', glow:'#fbbf24', desc:'Királyi csapás: 35 dmg + 10 heal.' },
    termeszet:      { name:'Természet Ereje',emoji:'🌍',dmg:40, heal:10, bleed:0,  rarity:'legendary', color:'#14532d', glow:'#22c55e', desc:'40 sebzés és 10 gyógyulás.' },
};

const CHAR_DATA = {
    kiraly:     { name:'Király',       emoji:'👑', hp:200, passive:'kiraly',     passiveDesc:'+20% kártyasebzés' },
    vastag_bot: { name:'Vastag Bot',   emoji:'🪵', hp:150, passive:'vastag_bot', passiveDesc:'+5 sebzés minden kártyára' },
    ko_szikla:  { name:'Kőszikla',    emoji:'🪨', hp:180, passive:'ko_szikla',  passiveDesc:'Első 2 támadást blokkol' },
    tobozos:    { name:'Tobozos',      emoji:'🌲', hp:130, passive:'tobozos',    passiveDesc:'Vérzés kártyák duplán hatnak' },
    leveles:    { name:'Leveles',      emoji:'🍃', hp:110, passive:'leveles',    passiveDesc:'Minden gyógyulás +50%' },
    bot:        { name:'Bot Harcos',   emoji:'🌿', hp:80,  passive:'bot',        passiveDesc:'Bot kártyák: +50% vérzés' },
};

const SHOP_PACKS = {
    kis:     { name:'Kis Zacskó',     emoji:'👜', xp:50,  pool:['ko','ko','bot','bot','levél','toboz'],                          count:3 },
    kozepes: { name:'Közepes Zacskó', emoji:'🎒', xp:100, pool:['ko','bot','levél','toboz','vastag_bot','hegyes_ko','gyógylevél'], count:4 },
    nagy:    { name:'Nagy Zacskó',    emoji:'💼', xp:200, pool:['vastag_bot','hegyes_ko','gyógylevél','tüskés_toboz'],             count:5 },
    kiraly:  { name:'Király Zacskó',  emoji:'👑', xp:500, pool:['tüskés_toboz','kiraly_parancs','termeszet'],                     count:5 },
};

// Game state
let G = null;

function newGame(playerChar) {
    const deck1 = shuffleDeck(makeDeck());
    const deck2 = shuffleDeck(makeDeck());
    const c1 = CHAR_DATA[playerChar] || CHAR_DATA.kiraly;
    const c2 = CHAR_DATA[pickAiChar()];
    G = {
        p1: { char: playerChar, data: c1, hp: c1.hp, maxHp: c1.hp, hand: dealCards(deck1, 5), deck: deck1, bleed: 0, blockLeft: playerChar==='ko_szikla'?2:0 },
        p2: { char: pickAiChar(), data: c2, hp: c2.hp, maxHp: c2.hp, hand: dealCards(deck2, 5), deck: deck2, bleed: 0, blockLeft: 0 },
        turn: 1,
        log: [],
        over: false,
        winner: null,
    };
    return G;
}

function pickAiChar() {
    const chars = Object.keys(CHAR_DATA);
    return chars[Math.floor(Math.random() * chars.length)];
}

function makeDeck() {
    const d = [];
    for (let i=0;i<4;i++) d.push('ko','bot','levél','toboz');
    d.push('vastag_bot','vastag_bot','hegyes_ko','gyógylevél','tüskés_toboz','kiraly_parancs');
    return d;
}

function shuffleDeck(d) {
    for (let i=d.length-1;i>0;i--) { const j=Math.floor(Math.random()*(i+1)); [d[i],d[j]]=[d[j],d[i]]; }
    return d;
}

function dealCards(deck, n) {
    return deck.splice(0, n);
}

function drawCard(player) {
    if (player.deck.length > 0) player.hand.push(player.deck.shift());
}

function applyBleed(player) {
    if (player.bleed > 0) {
        player.hp -= player.bleed;
        player.bleed = Math.max(0, player.bleed - 1);
        player.hp = Math.max(0, player.hp);
    }
}

function calcDmg(card, attacker, baseVal) {
    let v = baseVal;
    if (attacker.char === 'kiraly')     v = Math.floor(v * 1.2);
    if (attacker.char === 'vastag_bot') v += 5;
    return v;
}

function calcHeal(card, healer, baseVal) {
    let v = baseVal;
    if (healer.char === 'leveles') v = Math.floor(v * 1.5);
    return v;
}

function calcBleed(card, attacker, baseVal) {
    let v = baseVal;
    if (attacker.char === 'tobozos') v *= 2;
    if (attacker.char === 'bot')     v = Math.floor(v * 1.5);
    return v;
}

function playCard(playerNum, cardIdx) {
    if (!G || G.over || G.turn !== playerNum) return null;
    const atk = playerNum === 1 ? G.p1 : G.p2;
    const def = playerNum === 1 ? G.p2 : G.p1;
    if (cardIdx < 0 || cardIdx >= atk.hand.length) return null;

    const cardType = atk.hand.splice(cardIdx, 1)[0];
    drawCard(atk);
    const card = CARD_DATA[cardType];
    if (!card) return null;

    applyBleed(atk);
    let msg = card.emoji + ' ' + card.name + ': ';

    if (card.dmg > 0) {
        const dmg = calcDmg(cardType, atk, card.dmg);
        if (def.blockLeft > 0) {
            def.blockLeft--;
            msg += '🛡️ Blokkolva! ';
        } else {
            def.hp = Math.max(0, def.hp - dmg);
            msg += dmg + ' sebzés! ';
        }
    }
    if (card.heal > 0) {
        const h = calcHeal(cardType, atk, card.heal);
        atk.hp = Math.min(atk.maxHp, atk.hp + h);
        msg += '💚 +' + h + 'hp! ';
    }
    if (card.bleed > 0) {
        const b = calcBleed(cardType, atk, card.bleed);
        def.bleed += b;
        msg += '🩸 +' + b + ' vérzés! ';
    }

    G.log.unshift(msg.trim());
    if (G.log.length > 5) G.log.pop();

    checkWin();
    if (!G.over) {
        G.turn = playerNum === 1 ? 2 : 1;
        if (G.turn === 2) setTimeout(aiTurn, 800);
    }
    return { msg, card: cardType };
}

function aiTurn() {
    if (!G || G.over || G.turn !== 2) return;
    applyBleed(G.p2);
    if (G.over) { checkWin(); render(); return; }

    // AI pick best card
    let bestIdx = 0, bestScore = -1;
    G.p2.hand.forEach((c, i) => {
        const cd = CARD_DATA[c];
        if (!cd) return;
        const score = cd.dmg * 1 + cd.bleed * 2 + (G.p2.hp < 60 ? cd.heal * 4 : cd.heal);
        if (score > bestScore) { bestScore = score; bestIdx = i; }
    });

    const cardType = G.p2.hand.splice(bestIdx, 1)[0];
    drawCard(G.p2);
    const card = CARD_DATA[cardType];
    let msg = '🤖 AI: ' + card.emoji + ' ' + card.name + ': ';

    if (card.dmg > 0) {
        const dmg = calcDmg(cardType, G.p2, card.dmg);
        if (G.p1.blockLeft > 0) {
            G.p1.blockLeft--;
            msg += '🛡️ Blokkolva! ';
        } else {
            G.p1.hp = Math.max(0, G.p1.hp - dmg);
            msg += dmg + ' sebzés! ';
        }
    }
    if (card.heal > 0) {
        const h = calcHeal(cardType, G.p2, card.heal);
        G.p2.hp = Math.min(G.p2.maxHp, G.p2.hp + h);
        msg += '💚 +' + h + 'hp! ';
    }
    if (card.bleed > 0) {
        const b = calcBleed(cardType, G.p2, card.bleed);
        G.p1.bleed += b;
        msg += '🩸 +' + b + ' vérzés! ';
    }

    G.log.unshift(msg.trim());
    if (G.log.length > 5) G.log.pop();
    checkWin();
    if (!G.over) G.turn = 1;
    render();
}

function checkWin() {
    if (G.p1.hp <= 0) { G.over = true; G.winner = 2; }
    if (G.p2.hp <= 0) { G.over = true; G.winner = 1; }
}
