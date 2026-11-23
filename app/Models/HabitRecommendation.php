<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HabitRecommendation extends Model
{
    use HasFactory;

    protected $fillable = [
        'checkin_id',
        'student_id',
        'tipe',
        'target_kode',
        'alasan_item',
        'shown_at',
        'accepted_at',
        'completed_at',
    ];

    protected $casts = [
        'shown_at' => 'datetime',
        'accepted_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function checkin(): BelongsTo
    {
        return $this->belongsTo(HabitCheckin::class, 'checkin_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
