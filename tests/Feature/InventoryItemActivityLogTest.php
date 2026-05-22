<?php

namespace Tests\Feature;

use App\Models\InventoryItem;
use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class InventoryItemActivityLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_inventory_item_creation_logs_integer_user_causer_for_uuid_subject(): void
    {
        $user = User::factory()->create();
        Role::findOrCreate('Administrator');
        $user->assignRole('Administrator');

        $location = Location::create([
            'type' => 'internal',
            'name' => 'Warehouse',
            'code' => 'WH',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->post(route('inventory-items.store'), [
            'asset_tag' => 'A832',
            'item_type' => InventoryItem::TYPE_PHONE,
            'name' => 'A832',
            'manufacturer' => 'Kyocera',
            'model' => 'E7110',
            'serial_number' => '572600144783',
            'status' => InventoryItem::STATUS_RECEIVED,
            'condition' => 'USED',
            'location_id' => $location->id,
            'received_at' => '2025-05-01',
            'notes' => "Aaron's Desk.",
        ]);

        $item = InventoryItem::where('asset_tag', 'A832')->firstOrFail();

        $response->assertRedirect(route('inventory-items.show', $item));
        $this->assertDatabaseHas('activity_log', [
            'causer_id' => $user->id,
            'causer_type' => User::class,
            'subject_id' => $item->id,
            'subject_type' => InventoryItem::class,
            'event' => 'created',
        ]);
    }
}
