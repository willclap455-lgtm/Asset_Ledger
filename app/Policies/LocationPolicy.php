<?php

namespace App\Policies;

use App\Models\Location;
use App\Models\User;

class LocationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['Administrator', 'Inventory Staff', 'Repair Technician', 'Read-Only User']);
    }

    public function view(User $user, Location $location): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['Administrator', 'Inventory Staff']);
    }

    public function update(User $user, Location $location): bool
    {
        return $this->create($user);
    }

    public function delete(User $user, Location $location): bool
    {
        return false;
    }
}
