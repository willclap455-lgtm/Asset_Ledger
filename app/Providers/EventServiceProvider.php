<?php

namespace App\Providers;

use App\Events\InventoryMovementRecorded;
use App\Listeners\LogInventoryMovementActivity;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        InventoryMovementRecorded::class => [
            LogInventoryMovementActivity::class,
        ],
    ];
}
