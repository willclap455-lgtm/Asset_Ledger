<?php

namespace App\Listeners;

use App\Events\InventoryMovementRecorded;

class LogInventoryMovementActivity
{
    public function handle(InventoryMovementRecorded $event): void
    {
        $movement = $event->movement;

        activity('inventory_movements')
            ->performedOn($movement)
            ->causedBy($movement->user)
            ->withProperties([
                'movement_number' => $movement->movement_number,
                'movement_type' => $movement->movement_type,
                'line_count' => $movement->lines()->count(),
            ])
            ->event('movement_recorded')
            ->log('Inventory movement recorded');
    }
}
