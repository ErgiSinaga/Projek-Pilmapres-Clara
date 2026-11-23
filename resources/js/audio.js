/**
 * Manajer efek suara ringan dan kontrol bisu untuk Dento.
 */
class SoundManager {
  constructor() {
    this.muted = false;
    this.hasUserInteracted = false;

    window.addEventListener('pointerdown', () => { this.hasUserInteracted = true; }, { once: true });
    window.addEventListener('keydown', () => { this.hasUserInteracted = true; }, { once: true });
  }

  isMuted() {
    return this.muted;
  }

  toggleMute() {
    this.muted = !this.muted;
    return this.muted;
  }

  play(name) {
    this.playEffect(name);
  }

  playEffect(name) {
    if (this.muted || !this.hasUserInteracted) return;
    // Audio synthesizer Web Audio API sederhana jika file audio lokal belum tersedia
    try {
      const ctx = new (window.AudioContext || window.webkitAudioContext)();
      const osc = ctx.createOscillator();
      const gain = ctx.createGain();
      osc.connect(gain);
      gain.connect(ctx.destination);

      if (name === 'pop' || name === 'coin') {
        osc.frequency.setValueAtTime(523.25, ctx.currentTime);
        osc.frequency.exponentialRampToValueAtTime(783.99, ctx.currentTime + 0.1);
        gain.gain.setValueAtTime(0.12, ctx.currentTime);
        gain.gain.linearRampToValueAtTime(0.01, ctx.currentTime + 0.12);
        osc.start();
        osc.stop(ctx.currentTime + 0.12);
      } else if (name === 'jump' || name === 'levelUp') {
        osc.frequency.setValueAtTime(320, ctx.currentTime);
        osc.frequency.exponentialRampToValueAtTime(640, ctx.currentTime + 0.15);
        gain.gain.setValueAtTime(0.1, ctx.currentTime);
        gain.gain.linearRampToValueAtTime(0.01, ctx.currentTime + 0.15);
        osc.start();
        osc.stop(ctx.currentTime + 0.15);
      }
    } catch (e) {
      // Abaikan jika Web Audio tidak diizinkan
    }
  }
}

export const sound = new SoundManager();
