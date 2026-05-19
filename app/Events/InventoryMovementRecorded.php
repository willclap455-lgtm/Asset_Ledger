<?php

namespace App\Events;

use App\Models\InventoryMovement;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class InventoryMovementRecorded
{
    use Dispatchable, SerializesModels;

    public function __construct(public InventoryMovement $movement)
    {
    }
}
