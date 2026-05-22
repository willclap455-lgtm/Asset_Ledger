<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class InventoryAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_users_can_load_the_inventory_index(): void
    {
        $user = User::factory()->create();
        Role::findOrCreate('Administrator');
        $user->assignRole('Administrator');

        $this
            ->actingAs($user)
            ->get(route('inventory-items.index'))
            ->assertOk();
    }

    public function test_users_without_an_operational_role_cannot_load_the_inventory_index(): void
    {
        $user = User::factory()->create();

        $this
            ->actingAs($user)
            ->get(route('inventory-items.index'))
            ->assertForbidden();
    }
}
