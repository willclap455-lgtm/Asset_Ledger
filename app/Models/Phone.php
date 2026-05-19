<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Phone extends Model
{
    use HasFactory, HasUuid, LogsActivity;

    protected $fillable = [
        'inventory_item_id', 'phone_number', 'carrier', 'imei', 'android_version',
        'assigned_sim_card_id', 'assigned_printer_id',
    ];

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    public function assignedSimCard(): BelongsTo
    {
        return $this->belongsTo(SimCard::class, 'assigned_sim_card_id');
    }

    public function assignedPrinter(): BelongsTo
    {
        return $this->belongsTo(Printer::class, 'assigned_printer_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()->dontSubmitEmptyLogs();
    }
}
