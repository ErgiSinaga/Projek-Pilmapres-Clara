<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HabitCheckin extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'tanggal',
        'status',
        'dilewati_count',
        'started_at',
        'finished_at',
        'durasi_detik',
    ];

    protected $casts = [
        'tanggal' => 'date:Y-m-d',
        'dilewati_count' => 'integer',
        'durasi_detik' => 'integer',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(HabitAnswer::class, 'checkin_id');
    }

    public function recommendations(): HasMany
    {
        return $this->hasMany(HabitRecommendation::class, 'checkin_id');
    }
}
