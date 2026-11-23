<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HabitAnswer extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'checkin_id',
        'item_kode',
        'nilai',
        'nilai_num',
        'ms',
        'answered_at',
    ];

    protected $casts = [
        'nilai_num' => 'float',
        'ms' => 'integer',
        'answered_at' => 'datetime',
    ];

    public function checkin(): BelongsTo
    {
        return $this->belongsTo(HabitCheckin::class, 'checkin_id');
    }
}
