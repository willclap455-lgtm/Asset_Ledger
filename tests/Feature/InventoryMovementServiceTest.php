<?php

namespace Tests\Feature;

use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use App\Models\Location;
use App\Models\User;
use App\Services\InventoryMovementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class InventoryMovementServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_records_an_immutable_movement_line_and_updates_current_assignment(): void
    {
        $user = User::factory()->create();
        Role::findOrCreate('Administrator');
        $user->assignRole('Administrator');

        $warehouse = Location::create(['name' => 'Warehouse', 'code' => 'WH', 'type' => 'internal']);
        $repair = Location::create(['name' => 'Repair Bench', 'code' => 'REPAIR', 'type' => 'internal']);
        $item = InventoryItem::create([
            'asset_tag' => 'PHONE-001',
            'item_type' => InventoryItem::TYPE_PHONE,
            'status' => InventoryItem::STATUS_IN_STOCK,
            'location_id' => $warehouse->id,
            'manufacturer' => 'Samsung',
            'model' => 'Galaxy A15',
        ]);
        $item->phone()->create([
            'phone_number' => '555-0100',
            'carrier' => 'Verizon',
            'imei' => '123456789012345',
            'android_version' => '14',
        ]);

        $movement = app(InventoryMovementService::class)->recordMovement([
            'movement_type' => InventoryMovement::TYPE_REPAIR_INTAKE,
            'to_location_id' => $repair->id,
            'item_ids' => [$item->id],
            'notes' => 'Screen cracked during field use.',
        ], $user);

        $this->assertDatabaseHas('inventory_movements', ['id' => $movement->id, 'movement_type' => InventoryMovement::TYPE_REPAIR_INTAKE]);
        $this->assertDatabaseHas('inventory_movement_lines', [
            'inventory_movement_id' => $movement->id,
            'inventory_item_id' => $item->id,
            'previous_location_id' => $warehouse->id,
            'new_location_id' => $repair->id,
            'previous_status' => InventoryItem::STATUS_IN_STOCK,
            'new_status' => InventoryItem::STATUS_IN_REPAIR,
        ]);
        $this->assertSame($repair->id, $item->fresh()->location_id);
        $this->assertSame(InventoryItem::STATUS_IN_REPAIR, $item->fresh()->status);
        $this->assertSame('555-0100', $movement->lines()->first()->item_snapshot['phone']['phone_number']);
    }
}
