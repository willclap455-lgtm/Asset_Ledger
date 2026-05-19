<?php

namespace App\Policies;

use App\Models\Client;
use App\Models\User;

class ClientPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['Administrator', 'Inventory Staff', 'Repair Technician', 'Read-Only User']);
    }

    public function view(User $user, Client $client): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['Administrator', 'Inventory Staff']);
    }

    public function update(User $user, Client $client): bool
    {
        return $this->create($user);
    }

    public function delete(User $user, Client $client): bool
    {
        return false;
    }
}
