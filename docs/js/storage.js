// localStorage alapú adattárolás
const Store = {
    get: (key, def=null) => { try { return JSON.parse(localStorage.getItem(key)) ?? def; } catch { return def; } },
    set: (key, val) => localStorage.setItem(key, JSON.stringify(val)),

    getPlayer() {
        return this.get('player', null);
    },
    savePlayer(p) { this.set('player', p); },

    initPlayer(name) {
        const existing = this.getPlayer();
        if (existing && existing.name === name) return existing;
        const p = { name, xp: 100, cards: ['ko','ko','ko','ko','bot','bot','bot','levél','levél','levél','toboz','toboz','toboz','ko','bot'] };
        this.savePlayer(p);
        return p;
    },

    addXp(amount) {
        const p = this.getPlayer();
        if (!p) return;
        p.xp = (p.xp || 0) + amount;
        this.savePlayer(p);
        return p.xp;
    },

    spendXp(amount) {
        const p = this.getPlayer();
        if (!p || p.xp < amount) return false;
        p.xp -= amount;
        this.savePlayer(p);
        return true;
    },

    addCards(cardList) {
        const p = this.getPlayer();
        if (!p) return;
        p.cards = [...(p.cards || []), ...cardList];
        this.savePlayer(p);
    },

    getCards() {
        const p = this.getPlayer();
        return p?.cards || [];
    },
};
