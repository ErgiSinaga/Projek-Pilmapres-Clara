<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Item extends Model
{
    use HasFactory;

    protected $fillable = [
        'kode',
        'nama',
        'slot',
        'harga',
        'svg_key',
    ];

    protected function casts(): array
    {
        return [
            'harga' => 'integer',
        ];
    }

    public function inventories(): HasMany
    {
        return $this->hasMany(Inventory::class);
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(Student::class, 'inventories')->withPivot('equipped')->withTimestamps();
    }
}
