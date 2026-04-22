// Game logic - polling based multiplayer + AI

const Game = (() => {
    let roomId, myNum, pollTimer, lastUpdate = '';

    function init(rId, mNum) {
        roomId = rId; myNum = mNum;
        startPolling();
        renderParticles();
    }

    function startPolling() {
        pollTimer = setInterval(fetchState, 2000);
        fetchState();
    }

    async function fetchState() {
        try {
            const r = await fetch(`api/room.php?action=state&room_id=${roomId}`);
            const data = await r.json();
            if (!data.ok) return;
            render(data);
        } catch(e) {}
    }

    function render(s) {
        const key = JSON.stringify({h:s.myHp,o:s.oppHp,t:s.isMyTurn,st:s.status,la:s.lastAction});
        if (key === lastUpdate) return;
        lastUpdate = key;

        // HP bars
        updateHP('my-hp', s.myHp, s.isP1 ? s.p1MaxHp : s.p2MaxHp, s.myChar);
        updateHP('opp-hp', s.oppHp, s.isP1 ? s.p2MaxHp : s.p1MaxHp, s.oppChar);

        // Names
        const myName  = s.isP1 ? s.p1Name : s.p2Name;
        const oppName = s.isP1 ? s.p2Name : s.p1Name;
        const myC     = CHARACTERS[s.myChar]  || {};
        const oppC    = CHARACTERS[s.oppChar] || {};
        document.getElementById('my-name').textContent  = (myC.emoji||'')  + ' ' + myName;
        document.getElementById('opp-name').textContent = (oppC.emoji||'') + ' ' + oppName;

        // Bleed indicators
        if (s.myBleed>0)  document.getElementById('my-bleed').textContent  = '🩸×'+s.myBleed;
        else               document.getElementById('my-bleed').textContent  = '';
        if (s.oppBleed>0) document.getElementById('opp-bleed').textContent = '🩸×'+s.oppBleed;
        else               document.getElementById('opp-bleed').textContent = '';

        // Turn indicator
        const turnEl = document.getElementById('turn-indicator');
        if (s.status === 'playing') {
            turnEl.textContent = s.isMyTurn ? '⚔️ A TE KÖRÖD' : '⏳ Ellenfél köre...';
            turnEl.className = 'turn-indicator ' + (s.isMyTurn ? 'my-turn' : 'opp-turn');
        }

        // Action log
        if (s.lastAction) {
            const log = document.getElementById('action-log');
            log.textContent = s.lastAction;
            log.classList.add('flash');
            setTimeout(() => log.classList.remove('flash'), 600);
        }

        // Hand
        renderHand(s.myHand, s.isMyTurn && s.status === 'playing');

        // Last played card animation
        if (s.lastCard && s.lastCard !== window._lastCard) {
            window._lastCard = s.lastCard;
            showPlayedCard(s.lastCard);
        }

        // Game over
        if (s.status === 'finished') {
            clearInterval(pollTimer);
            showGameOver(s.winner, s.isP1, myName);
        }

        // Waiting
        if (s.status === 'waiting') {
            document.getElementById('waiting-overlay').style.display = 'flex';
        } else {
            document.getElementById('waiting-overlay').style.display = 'none';
        }
    }

    function updateHP(id, hp, maxHp, charType) {
        const wrap = document.getElementById(id);
        if (!wrap) return;
        const bar  = wrap.querySelector('.hp-fill');
        const txt  = wrap.querySelector('.hp-text');
        const pct  = Math.max(0, Math.min(100, (hp/maxHp)*100));
        bar.style.width = pct + '%';
        bar.style.background = pct > 50 ? 'linear-gradient(90deg,#22c55e,#16a34a)'
                              : pct > 25 ? 'linear-gradient(90deg,#f59e0b,#d97706)'
                              :            'linear-gradient(90deg,#ef4444,#b91c1c)';
        txt.textContent = hp + ' / ' + maxHp + ' HP';
        const c = CHARACTERS[charType];
        if (c) { wrap.querySelector('.char-emoji').textContent = c.emoji; }
    }

    function renderHand(hand, playable) {
        const container = document.getElementById('hand-container');
        container.innerHTML = '';
        if (!hand) return;
        hand.forEach((cardType, i) => {
            const el = buildCardEl(cardType, i, playable);
            if (!el) return;
            if (playable) {
                el.addEventListener('click', () => playCard(i));
                el.addEventListener('mouseenter', () => el.classList.add('hover'));
                el.addEventListener('mouseleave', () => el.classList.remove('hover'));
            }
            container.appendChild(el);
        });
    }

    async function playCard(idx) {
        const cards = document.querySelectorAll('#hand-container .card');
        cards.forEach(c => c.style.pointerEvents='none');
        const playedEl = cards[idx];
        if (playedEl) {
            playedEl.classList.add('card-play-anim');
        }
        try {
            const fd = new FormData();
            fd.append('room_id', roomId);
            fd.append('card_idx', idx);
            const r = await fetch('api/game_action.php', { method:'POST', body:fd });
            const data = await r.json();
            if (!data.ok) { alert(data.error); }
            await fetchState();
        } catch(e) {}
    }

    function showPlayedCard(cardType) {
        const c = CARDS[cardType];
        if (!c) return;
        const el = document.createElement('div');
        el.className = 'played-card-popup';
        el.innerHTML = `<span class="big-emoji">${c.emoji}</span><span>${c.name}</span>`;
        document.body.appendChild(el);
        setTimeout(() => el.remove(), 1500);
    }

    function showGameOver(winner, isP1, myName) {
        const overlay = document.getElementById('gameover-overlay');
        const won = (winner==1 && isP1) || (winner==2 && !isP1);
        overlay.innerHTML = `
            <div class="gameover-box ${won?'win':'lose'}">
                <div class="gameover-emoji">${won?'🏆':'💀'}</div>
                <h1>${won?'GYŐZELEM!':'VERESÉG!'}</h1>
                <p>${won?'+50 XP nyertél!':'+20 XP a részvételért!'}</p>
                <a href="index.php" class="btn-main">Főmenü</a>
                <a href="shop.php" class="btn-secondary">Shop</a>
            </div>`;
        overlay.style.display = 'flex';
    }

    // Particle effects (falling leaves)
    function renderParticles() {
        const container = document.getElementById('particles');
        if (!container) return;
        const emojis = ['🍃','🍂','🌿','🌲','🪨'];
        setInterval(() => {
            const p = document.createElement('div');
            p.className = 'particle';
            p.textContent = emojis[Math.floor(Math.random()*emojis.length)];
            p.style.left = Math.random()*100+'vw';
            p.style.animationDuration = (3+Math.random()*4)+'s';
            p.style.fontSize = (12+Math.random()*16)+'px';
            p.style.opacity = 0.4+Math.random()*0.4;
            container.appendChild(p);
            setTimeout(() => p.remove(), 7000);
        }, 400);
    }

    return { init };
})();
