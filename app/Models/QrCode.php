<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QrCode extends Model
{
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'batch_id',
        'serial',
        'code',
        'token_hash',
        'verification_token',
        'status',
        'expires_at',
        'revoked_at',
        'revocation_reason',
        'generated_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'revoked_at' => 'datetime',
            'generated_at' => 'datetime',
        ];
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(QrBatch::class, 'batch_id');
    }

    public function scanEvents(): HasMany
    {
        return $this->hasMany(ScanEvent::class, 'qr_code_id');
    }
}
