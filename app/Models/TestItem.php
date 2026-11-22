<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TestItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'set',
        'urutan',
        'tp',
        'teks',
        'opsi',
        'benar',
        'gambar',
        'audio',
        'penjelasan_internal',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'urutan' => 'integer',
            'opsi' => 'array',
            'benar' => 'integer',
        ];
    }

    public function answers(): HasMany
    {
        return $this->hasMany(TestAnswer::class);
    }
}
