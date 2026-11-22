<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TestAttempt extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'jenis',
        'started_at',
        'finished_at',
        'skor',
        'total',
        'durasi_detik',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'skor' => 'integer',
            'total' => 'integer',
            'durasi_detik' => 'integer',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(TestAnswer::class, 'attempt_id');
    }
}
