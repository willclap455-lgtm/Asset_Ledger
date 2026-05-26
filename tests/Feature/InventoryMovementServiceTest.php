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

    public function test_it_generates_the_next_number_from_the_highest_daily_sequence(): void
    {
        $user = User::factory()->create();
        $prefix = 'MOV-'.now()->format('Ymd').'-';

        InventoryMovement::create([
            'movement_number' => $prefix.'9999',
            'movement_type' => InventoryMovement::TYPE_RECEIVING,
            'occurred_at' => now(),
            'user_id' => $user->id,
        ]);
        InventoryMovement::create([
            'movement_number' => $prefix.'10000',
            'movement_type' => InventoryMovement::TYPE_RECEIVING,
            'occurred_at' => now(),
            'user_id' => $user->id,
        ]);

        $warehouse = Location::create(['name' => 'Warehouse', 'code' => 'WH', 'type' => 'internal']);
        $item = InventoryItem::create([
            'asset_tag' => 'PHONE-002',
            'item_type' => InventoryItem::TYPE_PHONE,
            'status' => InventoryItem::STATUS_RECEIVED,
            'location_id' => $warehouse->id,
        ]);

        $movement = app(InventoryMovementService::class)->recordMovement([
            'movement_type' => InventoryMovement::TYPE_RECEIVING,
            'to_location_id' => $warehouse->id,
            'item_ids' => [$item->id],
        ], $user);

        $this->assertSame($prefix.'10001', $movement->movement_number);
    }
}
