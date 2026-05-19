<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GeneratedDocument extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'inventory_movement_id', 'user_id', 'document_type', 'template_key', 'file_path',
        'original_filename', 'checksum', 'generated_at', 'metadata',
    ];

    protected function casts(): array
    {
        return [
            'generated_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function movement(): BelongsTo
    {
        return $this->belongsTo(InventoryMovement::class, 'inventory_movement_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
