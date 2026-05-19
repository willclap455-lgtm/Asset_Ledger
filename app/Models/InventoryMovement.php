<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryMovement extends Model
{
    use HasFactory, HasUuid;

    public const TYPE_RECEIVING = 'receiving';

    public const TYPE_DEPLOYMENT = 'deployment';

    public const TYPE_TRANSFER = 'transfer';

    public const TYPE_RETURN = 'return';

    public const TYPE_REPAIR_INTAKE = 'repair_intake';

    public const TYPE_REPAIR_RETURN = 'repair_return';

    public const TYPE_SWAP = 'swap';

    public const TYPE_RETIREMENT = 'retirement';

    protected $fillable = [
        'movement_number', 'movement_type', 'occurred_at', 'user_id', 'from_location_id',
        'to_location_id', 'client_id', 'notes', 'metadata',
    ];

    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function fromLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'from_location_id');
    }

    public function toLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'to_location_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(InventoryMovementLine::class)->orderBy('sequence');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(GeneratedDocument::class);
    }

    public static function typeOptions(): array
    {
        return [
            self::TYPE_RECEIVING => 'Incoming Inventory',
            self::TYPE_DEPLOYMENT => 'Outgoing Deployment',
            self::TYPE_TRANSFER => 'Transfer',
            self::TYPE_RETURN => 'Return',
            self::TYPE_REPAIR_INTAKE => 'Repair Intake',
            self::TYPE_REPAIR_RETURN => 'Repair Return',
            self::TYPE_SWAP => 'Equipment Swap',
            self::TYPE_RETIREMENT => 'Retirement',
        ];
    }
}
