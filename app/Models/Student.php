<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Student extends Authenticatable
{
    use HasFactory;

    protected $fillable = [
        'school_id',
        'class_id',
        'kode',
        'pin_hash',
        'pet_name',
        'failed_attempts',
        'locked_until',
        'consent_confirmed_at',
    ];

    protected $hidden = [
        'pin_hash',
    ];

    protected function casts(): array
    {
        return [
            'failed_attempts' => 'integer',
            'locked_until' => 'datetime',
            'consent_confirmed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::addGlobalScope('school', function (Builder $builder) {
            if (auth()->guard('web')->check()) {
                $user = auth()->guard('web')->user();
                if ($user && $user->isGuru()) {
                    $builder->where('students.school_id', $user->school_id);
                }
            }
        });
    }

    public function getAuthPasswordName(): string
    {
        return 'pin_hash';
    }

    public function getAuthPassword(): string
    {
        return $this->pin_hash;
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function petState(): HasOne
    {
        return $this->hasOne(PetState::class);
    }

    public function inventories(): HasMany
    {
        return $this->hasMany(Inventory::class);
    }

    public function items(): BelongsToMany
    {
        return $this->belongsToMany(Item::class, 'inventories')->withPivot('equipped')->withTimestamps();
    }

    public function testAttempts(): HasMany
    {
        return $this->hasMany(TestAttempt::class);
    }

    public function videoProgress(): HasMany
    {
        return $this->hasMany(VideoProgress::class);
    }

    public function gameSessions(): HasMany
    {
        return $this->hasMany(GameSession::class);
    }

    public function dailyClaims(): HasMany
    {
        return $this->hasMany(DailyClaim::class);
    }

    public function surveyResponses(): HasMany
    {
        return $this->hasMany(SurveyResponse::class);
    }

    public function loginLogs(): HasMany
    {
        return $this->hasMany(LoginLog::class);
    }

    public function habitCheckins(): HasMany
    {
        return $this->hasMany(HabitCheckin::class);
    }

    public function habitRecommendations(): HasMany
    {
        return $this->hasMany(HabitRecommendation::class);
    }

    public function getTodayCheckinAttribute(): ?HabitCheckin
    {
        $today = now()->setTimezone('Asia/Jakarta')->toDateString();

        return $this->habitCheckins()->where('tanggal', $today)->first();
    }

    public function getHasCompletedCheckinTodayAttribute(): bool
    {
        return $this->today_checkin?->status === 'selesai';
    }

    public function getCompletedCheckinsCountAttribute(): int
    {
        return (int) $this->habitCheckins()->where('status', 'selesai')->count();
    }

    public function getSchoolDaysStreakAttribute(): int
    {
        $completedDates = $this->habitCheckins()
            ->where('status', 'selesai')
            ->pluck('tanggal')
            ->map(fn ($d) => Carbon::parse($d)->toDateString())
            ->toArray();

        $completedSet = array_flip($completedDates);
        $current = now()->setTimezone('Asia/Jakarta');
        if ($current->isWeekend()) {
            // Mundur ke hari Jumat terakhir
            $current = $current->previous(Carbon::FRIDAY);
        }

        $streak = 0;
        $graceUsed = 0;
        $maxGrace = config('dento.habit.streak_grace_days', 1);

        // Jika hari ini adalah weekday dan belum lapor, cek apakah kemarin lapor
        $todayStr = $current->toDateString();
        if (! isset($completedSet[$todayStr])) {
            $prev = $current->copy()->subDay();
            while ($prev->isWeekend()) {
                $prev->subDay();
            }
            if (isset($completedSet[$prev->toDateString()])) {
                $current = $prev;
            } else {
                return 0;
            }
        }

        while (true) {
            $dateStr = $current->toDateString();
            if (isset($completedSet[$dateStr])) {
                $streak++;
            } else {
                if ($graceUsed < $maxGrace) {
                    $graceUsed++;
                } else {
                    break;
                }
            }

            // Mundur 1 hari sekolah
            $current->subDay();
            while ($current->isWeekend()) {
                $current->subDay();
            }

            // Safety limit 60 hari
            if ($streak > 60) {
                break;
            }
        }

        return $streak;
    }

    public function isLocked(): bool
    {
        return $this->locked_until !== null && $this->locked_until->isFuture();
    }

    public function getActiveDaysCountAttribute(): int
    {
        return (int) ($this->loginLogs()
            ->selectRaw('COUNT(DISTINCT DATE(login_at)) as agg')
            ->value('agg') ?? 0);
    }
}
