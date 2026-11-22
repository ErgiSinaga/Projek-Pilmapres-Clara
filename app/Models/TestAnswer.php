<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TestAnswer extends Model
{
    use HasFactory;

    protected $fillable = [
        'attempt_id',
        'test_item_id',
        'pilihan',
        'benar',
        'ms',
        'opsi_urutan',
    ];

    protected function casts(): array
    {
        return [
            'pilihan' => 'integer',
            'benar' => 'boolean',
            'ms' => 'integer',
            'opsi_urutan' => 'array',
        ];
    }

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(TestAttempt::class, 'attempt_id');
    }

    public function testItem(): BelongsTo
    {
        return $this->belongsTo(TestItem::class);
    }
}
