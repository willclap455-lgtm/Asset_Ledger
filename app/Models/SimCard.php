<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class SimCard extends Model
{
    use HasFactory, HasUuid, LogsActivity;

    protected $fillable = [
        'inventory_item_id', 'iccid', 'imsi', 'carrier', 'associated_phone_number',
        'assigned_inventory_item_id', 'activation_status',
    ];

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    public function assignedInventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'assigned_inventory_item_id');
    }

    public function assignedPhones(): HasMany
    {
        return $this->hasMany(Phone::class, 'assigned_sim_card_id');
    }

    public function assignedModems(): HasMany
    {
        return $this->hasMany(Modem::class, 'assigned_sim_card_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()->dontSubmitEmptyLogs();
    }
}
