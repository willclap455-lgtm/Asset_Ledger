<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryMovementLine extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'inventory_movement_id', 'inventory_item_id', 'previous_location_id', 'new_location_id',
        'previous_client_id', 'new_client_id', 'previous_status', 'new_status', 'item_snapshot',
        'sequence',
    ];

    protected function casts(): array
    {
        return ['item_snapshot' => 'array'];
    }

    public function movement(): BelongsTo
    {
        return $this->belongsTo(InventoryMovement::class, 'inventory_movement_id');
    }

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    public function previousLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'previous_location_id');
    }

    public function newLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'new_location_id');
    }
}
