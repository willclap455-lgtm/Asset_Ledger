<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class InventoryAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_users_can_load_authorized_inventory_pages(): void
    {
        $user = $this->userWithRole('Administrator');

        foreach ($this->authorizedPageUrls($user) as $page => $url) {
            $response = $this->actingAs($user)->get($url);

            $this->assertSame(200, $response->getStatusCode(), "{$page} should load for authorized users.");
        }
    }

    public function test_users_without_an_operational_role_cannot_load_authorized_inventory_pages(): void
    {
        $user = User::factory()->create();

        foreach ($this->authorizedPageUrls($user) as $page => $url) {
            $response = $this->actingAs($user)->get($url);

            $this->assertSame(403, $response->getStatusCode(), "{$page} should reject users without an operational role.");
        }
    }

    private function userWithRole(string $roleName): User
    {
        $user = User::factory()->create();
        Role::findOrCreate($roleName);
        $user->assignRole($roleName);

        return $user;
    }

    /**
     * @return array<string, string>
     */
    private function authorizedPageUrls(User $movementOwner): array
    {
        $client = Client::create([
            'name' => 'Acme Parking',
            'code' => 'ACME',
        ]);

        $location = Location::create([
            'client_id' => $client->id,
            'type' => 'client_site',
            'name' => 'Main Garage',
            'code' => 'GARAGE',
            'is_active' => true,
        ]);

        $item = InventoryItem::create([
            'asset_tag' => 'ASSET-001',
            'item_type' => InventoryItem::TYPE_GENERIC,
            'name' => 'Test Asset',
            'status' => InventoryItem::STATUS_IN_STOCK,
            'client_id' => $client->id,
            'location_id' => $location->id,
        ]);

        $movement = InventoryMovement::create([
            'movement_number' => 'MOVE-001',
            'movement_type' => InventoryMovement::TYPE_RECEIVING,
            'occurred_at' => now(),
            'user_id' => $movementOwner->id,
            'to_location_id' => $location->id,
            'client_id' => $client->id,
        ]);

        return [
            'clients.index' => route('clients.index'),
            'clients.create' => route('clients.create'),
            'clients.show' => route('clients.show', $client),
            'clients.edit' => route('clients.edit', $client),
            'locations.index' => route('locations.index'),
            'locations.create' => route('locations.create'),
            'locations.edit' => route('locations.edit', $location),
            'inventory-items.index' => route('inventory-items.index'),
            'inventory-items.create' => route('inventory-items.create'),
            'inventory-items.show' => route('inventory-items.show', $item),
            'inventory-items.edit' => route('inventory-items.edit', $item),
            'movements.index' => route('movements.index'),
            'movements.create' => route('movements.create'),
            'movements.show' => route('movements.show', $movement),
            'reports.index' => route('reports.index'),
            'reports.inventory-export' => route('reports.inventory-export'),
        ];
    }
}
