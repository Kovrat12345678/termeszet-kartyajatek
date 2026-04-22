// Procedural background music - Web Audio API
// Starts on first user interaction (browser policy), cannot be stopped

const GameMusic = (() => {
    let ctx, master, reverb;
    let started = false;
    let scheduleTimers = [];

    const BPM = 90;
    const BEAT = 60 / BPM;
    const BAR = BEAT * 4;

    // MIDI note to Hz
    function hz(midi) { return 440 * Math.pow(2, (midi - 69) / 12); }

    // Pentatonic minor scale root C3 (MIDI 48): 48,51,53,55,58,60,63,65,67,70
    const SCALE = [48,51,53,55,58,60,63,65,67,70];

    async function makeReverb() {
        const len = ctx.sampleRate * 3;
        const buf = ctx.createBuffer(2, len, ctx.sampleRate);
        for (let c=0; c<2; c++) {
            const d = buf.getChannelData(c);
            for (let i=0; i<len; i++) d[i] = (Math.random()*2-1) * Math.pow(1-i/len, 2.5);
        }
        const conv = ctx.createConvolver();
        conv.buffer = buf;
        return conv;
    }

    function envelope(gainNode, t, atk, dec, sus, rel, dur) {
        gainNode.gain.setValueAtTime(0, t);
        gainNode.gain.linearRampToValueAtTime(1, t + atk);
        gainNode.gain.linearRampToValueAtTime(sus, t + atk + dec);
        gainNode.gain.setValueAtTime(sus, t + dur - rel);
        gainNode.gain.linearRampToValueAtTime(0, t + dur);
    }

    function playNote(freq, t, dur, type='triangle', vol=0.15, useReverb=true) {
        const osc = ctx.createOscillator();
        const g   = ctx.createGain();
        osc.type = type;
        osc.frequency.value = freq;
        envelope(g, t, 0.02, 0.1, 0.6, 0.2, dur);
        g.gain.value = vol;
        osc.connect(g);
        if (useReverb && reverb) g.connect(reverb);
        g.connect(master);
        osc.start(t);
        osc.stop(t + dur + 0.05);
    }

    function playDrum(t, type) {
        const g = ctx.createGain();
        g.connect(master);
        if (type === 'kick') {
            const osc = ctx.createOscillator();
            osc.frequency.setValueAtTime(150, t);
            osc.frequency.exponentialRampToValueAtTime(1, t + 0.4);
            g.gain.setValueAtTime(1, t);
            g.gain.exponentialRampToValueAtTime(0.001, t + 0.4);
            osc.connect(g); osc.start(t); osc.stop(t+0.5);
        } else if (type === 'snare') {
            const buf = ctx.createBuffer(1, ctx.sampleRate*0.2, ctx.sampleRate);
            const d = buf.getChannelData(0);
            for (let i=0;i<d.length;i++) d[i]=Math.random()*2-1;
            const src = ctx.createBufferSource();
            src.buffer = buf;
            const flt = ctx.createBiquadFilter();
            flt.type='bandpass'; flt.frequency.value=3000;
            g.gain.setValueAtTime(0.3, t);
            g.gain.exponentialRampToValueAtTime(0.001, t+0.15);
            src.connect(flt); flt.connect(g); src.start(t);
        } else if (type === 'hihat') {
            const buf = ctx.createBuffer(1, ctx.sampleRate*0.05, ctx.sampleRate);
            const d = buf.getChannelData(0);
            for (let i=0;i<d.length;i++) d[i]=Math.random()*2-1;
            const src = ctx.createBufferSource();
            src.buffer = buf;
            const flt = ctx.createBiquadFilter();
            flt.type='highpass'; flt.frequency.value=8000;
            g.gain.setValueAtTime(0.15, t);
            g.gain.exponentialRampToValueAtTime(0.001, t+0.04);
            src.connect(flt); flt.connect(g); src.start(t);
        }
    }

    // Melody patterns (scale indices)
    const MELODIES = [
        [7,5,3,5,7,9,7,5,  3,5,3,1,0,1,3,5],
        [0,3,5,7,5,3,5,7,  9,7,5,7,5,3,1,0],
        [5,7,9,7,5,3,5,7,  5,3,1,3,5,7,5,3],
    ];
    const BASS = [0,0,3,0, 1,0,5,0, 3,0,1,0, 5,0,3,0];

    let barNum = 0;

    function scheduleBar(startT) {
        const mel = MELODIES[barNum % MELODIES.length];
        // Melody
        mel.forEach((si, i) => {
            const t = startT + i * BEAT * 0.5;
            const note = SCALE[si % SCALE.length];
            playNote(hz(note + 12), t, BEAT*0.45, 'triangle', 0.12);
        });
        // Bass
        BASS.forEach((si, i) => {
            const t = startT + i * BEAT;
            if (i % 2 === 0) playNote(hz(SCALE[si % SCALE.length] - 12), t, BEAT*0.8, 'sawtooth', 0.15, false);
        });
        // Drums
        for (let i=0;i<4;i++) {
            playDrum(startT + i*BEAT, 'kick');
            if (i===1||i===3) playDrum(startT + i*BEAT, 'snare');
            for (let j=0;j<4;j++) playDrum(startT + i*BEAT + j*BEAT/4, 'hihat');
        }
        // Pad chord
        const chordNotes = [SCALE[0], SCALE[3], SCALE[5]];
        chordNotes.forEach(n => playNote(hz(n+24), startT, BAR*0.9, 'sine', 0.07));

        barNum++;
        const nextStart = startT + BAR;
        const delay = Math.max(0, (nextStart - ctx.currentTime - 0.5) * 1000);
        scheduleTimers.push(setTimeout(() => scheduleBar(nextStart), delay));
    }

    async function start() {
        if (started) return;
        started = true;
        ctx = new (window.AudioContext || window.webkitAudioContext)();
        master = ctx.createGain();
        master.gain.value = 0.25;
        master.connect(ctx.destination);
        reverb = await makeReverb();
        reverb.connect(master);
        scheduleBar(ctx.currentTime + 0.1);
    }

    // Start on first interaction
    ['click','touchstart','keydown'].forEach(ev => {
        document.addEventListener(ev, function init() {
            start();
            document.removeEventListener(ev, init);
        }, { once: true });
    });

    return { start };
})();
