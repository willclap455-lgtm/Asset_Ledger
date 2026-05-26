<?php

namespace App\Policies;

use App\Models\InventoryItem;
use App\Models\User;

class InventoryItemPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['Administrator', 'Inventory Staff', 'Repair Technician', 'Read-Only User']);
    }

    public function view(User $user, InventoryItem $item): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['Administrator', 'Inventory Staff']);
    }

    public function update(User $user, InventoryItem $item): bool
    {
        return $user->hasAnyRole(['Administrator', 'Inventory Staff']);
    }

    public function repair(User $user, InventoryItem $item): bool
    {
        return $user->hasAnyRole(['Administrator', 'Inventory Staff', 'Repair Technician']);
    }

    public function delete(User $user, InventoryItem $item): bool
    {
        return $user->hasAnyRole(['Administrator', 'Inventory Staff']);
    }
}
