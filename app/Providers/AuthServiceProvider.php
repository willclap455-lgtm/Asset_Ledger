<?php

namespace App\Providers;

use App\Models\Client;
use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use App\Models\Location;
use App\Policies\ClientPolicy;
use App\Policies\InventoryItemPolicy;
use App\Policies\InventoryMovementPolicy;
use App\Policies\LocationPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Client::class => ClientPolicy::class,
        Location::class => LocationPolicy::class,
        InventoryItem::class => InventoryItemPolicy::class,
        InventoryMovement::class => InventoryMovementPolicy::class,
    ];
}
