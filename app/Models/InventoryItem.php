<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class InventoryItem extends Model
{
    use HasFactory, HasUuid, LogsActivity;

    public const TYPE_PHONE = 'phone';

    public const TYPE_PRINTER = 'printer';

    public const TYPE_MODEM = 'modem';

    public const TYPE_SIM_CARD = 'sim_card';

    public const TYPE_GENERIC = 'generic';

    public const STATUS_RECEIVED = 'received';

    public const STATUS_IN_STOCK = 'in_stock';

    public const STATUS_DEPLOYED = 'deployed';

    public const STATUS_IN_REPAIR = 'in_repair';

    public const STATUS_RETURNED = 'returned';

    public const STATUS_RETIRED = 'retired';

    public const STATUS_LOST = 'lost';

    protected $fillable = [
        'asset_tag', 'item_type', 'category', 'name', 'description', 'manufacturer', 'model',
        'serial_number', 'status', 'condition', 'client_id', 'location_id', 'received_at',
        'deployed_at', 'retired_at', 'notes', 'extra_attributes',
    ];

    protected function casts(): array
    {
        return [
            'received_at' => 'date',
            'deployed_at' => 'date',
            'retired_at' => 'date',
            'extra_attributes' => 'array',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function phone(): HasOne
    {
        return $this->hasOne(Phone::class);
    }

    public function printer(): HasOne
    {
        return $this->hasOne(Printer::class);
    }

    public function modem(): HasOne
    {
        return $this->hasOne(Modem::class);
    }

    public function simCard(): HasOne
    {
        return $this->hasOne(SimCard::class);
    }

    public function movementLines(): HasMany
    {
        return $this->hasMany(InventoryMovementLine::class);
    }

    public function inventoryNotes(): HasMany
    {
        return $this->hasMany(InventoryNote::class)->latest();
    }

    public function repairs(): HasMany
    {
        return $this->hasMany(Repair::class)->latest('opened_at');
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (! $term) {
            return $query;
        }

        $like = '%'.strtolower($term).'%';

        return $query->where(function (Builder $query) use ($like): void {
            $query->whereRaw('lower(asset_tag) like ?', [$like])
                ->orWhereRaw('lower(name) like ?', [$like])
                ->orWhereRaw('lower(serial_number) like ?', [$like])
                ->orWhereRaw('lower(manufacturer) like ?', [$like])
                ->orWhereRaw('lower(model) like ?', [$like])
                ->orWhereHas('phone', fn (Builder $q) => $q->whereRaw('lower(phone_number) like ?', [$like])->orWhereRaw('lower(imei) like ?', [$like]))
                ->orWhereHas('simCard', fn (Builder $q) => $q->whereRaw('lower(iccid) like ?', [$like])->orWhereRaw('lower(imsi) like ?', [$like]));
        });
    }

    public function typeLabel(): string
    {
        return str(self::typeOptions()[$this->item_type] ?? $this->item_type)->headline()->toString();
    }

    public function statusLabel(): string
    {
        return str(self::statusOptions()[$this->status] ?? $this->status)->headline()->toString();
    }

    public static function typeOptions(): array
    {
        return [
            self::TYPE_PHONE => 'Phone',
            self::TYPE_PRINTER => 'Printer',
            self::TYPE_MODEM => 'Modem',
            self::TYPE_SIM_CARD => 'SIM Card',
            self::TYPE_GENERIC => 'Generic / Other',
        ];
    }

    public static function statusOptions(): array
    {
        return [
            self::STATUS_RECEIVED => 'Received',
            self::STATUS_IN_STOCK => 'In Stock',
            self::STATUS_DEPLOYED => 'Deployed',
            self::STATUS_IN_REPAIR => 'In Repair',
            self::STATUS_RETURNED => 'Returned',
            self::STATUS_RETIRED => 'Retired',
            self::STATUS_LOST => 'Lost',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
