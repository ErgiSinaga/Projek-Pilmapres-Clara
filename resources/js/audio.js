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

  playEffect(name) {
    if (this.muted || !this.hasUserInteracted) return;
    // Audio synthesizer Web Audio API sederhana jika file audio lokal belum tersedia
    try {
      const ctx = new (window.AudioContext || window.webkitAudioContext)();
      const osc = ctx.createOscillator();
      const gain = ctx.createGain();
      osc.connect(gain);
      gain.connect(ctx.destination);

      if (name === 'pop') {
        osc.frequency.setValueAtTime(440, ctx.currentTime);
        osc.frequency.exponentialRampToValueAtTime(880, ctx.currentTime + 0.1);
        gain.gain.setValueAtTime(0.1, ctx.currentTime);
        gain.gain.linearRampToValueAtTime(0.01, ctx.currentTime + 0.1);
        osc.start();
        osc.stop(ctx.currentTime + 0.1);
      } else if (name === 'jump') {
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
