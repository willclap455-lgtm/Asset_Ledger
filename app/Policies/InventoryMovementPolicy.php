<?php

namespace App\Policies;

use App\Models\InventoryMovement;
use App\Models\User;

class InventoryMovementPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['Administrator', 'Inventory Staff', 'Repair Technician', 'Read-Only User']);
    }

    public function view(User $user, InventoryMovement $movement): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['Administrator', 'Inventory Staff', 'Repair Technician']);
    }

    public function update(User $user, InventoryMovement $movement): bool
    {
        return false;
    }

    public function delete(User $user, InventoryMovement $movement): bool
    {
        return false;
    }
}
