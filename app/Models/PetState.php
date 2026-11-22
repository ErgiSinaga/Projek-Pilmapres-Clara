<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PetState extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'kebersihan',
        'kebahagiaan',
        'koin',
        'xp',
        'level',
        'last_seen_at',
        'last_decay_at',
    ];

    protected function casts(): array
    {
        return [
            'kebersihan' => 'integer',
            'kebahagiaan' => 'integer',
            'koin' => 'integer',
            'xp' => 'integer',
            'level' => 'integer',
            'last_seen_at' => 'datetime',
            'last_decay_at' => 'datetime',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Terapkan peluruhan status (kebersihan & kebahagiaan) berdasarkan selisih waktu.
     */
    public function applyDecay(): void
    {
        $now = Carbon::now();
        $lastDecay = $this->last_decay_at ?? $this->created_at ?? $now;
        $hoursElapsed = $lastDecay->diffInMinutes($now) / 60.0;

        if ($hoursElapsed <= 0.05) {
            $this->last_seen_at = $now;
            $this->save();

            return;
        }

        $decayConfig = config('dento.decay', [
            'kebersihan_per_hour' => 0.4,
            'kebahagiaan_per_hour' => 0.5,
            'floor' => 30,
            'max_drop_per_absence' => 40,
        ]);

        $dropClean = min($decayConfig['max_drop_per_absence'], $hoursElapsed * $decayConfig['kebersihan_per_hour']);
        $dropHappy = min($decayConfig['max_drop_per_absence'], $hoursElapsed * $decayConfig['kebahagiaan_per_hour']);

        $newClean = max($decayConfig['floor'], (int) round($this->kebersihan - $dropClean));
        $newHappy = max($decayConfig['floor'], (int) round($this->kebahagiaan - $dropHappy));

        $this->kebersihan = $newClean;
        $this->kebahagiaan = $newHappy;
        $this->last_decay_at = $now;
        $this->last_seen_at = $now;
        $this->save();
    }

    /**
     * Tambah XP dan hitung level baru secara otomatis.
     */
    public function addXp(int $amount): void
    {
        $this->xp += $amount;
        $thresholds = config('dento.level_thresholds', [0, 50, 120, 220, 350, 500]);
        $calculatedLevel = 1;

        foreach ($thresholds as $lvl => $minXp) {
            if ($this->xp >= $minXp) {
                $calculatedLevel = $lvl + 1;
            }
        }

        $this->level = $calculatedLevel;
        $this->save();
    }
}
