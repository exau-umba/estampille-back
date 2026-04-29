<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CounterfeitReport extends Model
{
    use HasFactory;
    use HasUlids;

    protected $fillable = [
        'location',
        'description',
        'contact',
        'image_path',
        'image_url',
        'status',
        'reported_at',
    ];

    protected function casts(): array
    {
        return [
            'reported_at' => 'datetime',
        ];
    }
}
