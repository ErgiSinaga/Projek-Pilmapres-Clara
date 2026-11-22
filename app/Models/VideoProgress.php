<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VideoProgress extends Model
{
    use HasFactory;

    protected $table = 'video_progress';

    protected $fillable = [
        'student_id',
        'video_id',
        'detik_ditonton',
        'selesai_at',
        'skor_tantangan',
    ];

    protected function casts(): array
    {
        return [
            'detik_ditonton' => 'integer',
            'selesai_at' => 'datetime',
            'skor_tantangan' => 'integer',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
