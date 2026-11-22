<?php

namespace App\Models;

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
