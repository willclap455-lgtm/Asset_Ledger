<?php

namespace Tests\Feature;

use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use App\Models\Location;
use App\Models\User;
use App\Services\InventoryMovementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
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
        $simItem = InventoryItem::create([
            'asset_tag' => 'SIM-MOVE',
            'item_type' => InventoryItem::TYPE_SIM_CARD,
            'status' => InventoryItem::STATUS_IN_STOCK,
            'location_id' => $warehouse->id,
        ]);
        $sim = $simItem->simCard()->create([
            'iccid' => '89014103211118510755',
            'associated_phone_number' => '555-0100',
            'activation_status' => 'active',
        ]);
        $item = InventoryItem::create([
            'asset_tag' => 'PHONE-001',
            'item_type' => InventoryItem::TYPE_PHONE,
            'status' => InventoryItem::STATUS_IN_STOCK,
            'location_id' => $warehouse->id,
            'manufacturer' => 'Samsung',
            'model' => 'Galaxy A15',
        ]);
        $item->phone()->create([
            'carrier' => 'Verizon',
            'imei' => '123456789012345',
            'android_version' => '14',
            'assigned_sim_card_id' => $sim->id,
        ]);
        $sim->update(['assigned_inventory_item_id' => $item->id]);

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
        $snapshot = $movement->lines()->first()->item_snapshot;
        $this->assertArrayNotHasKey('phone_number', $snapshot['phone']);
        $this->assertSame('555-0100', $snapshot['phone']['assigned_sim']['associated_phone_number']);
        $this->assertSame('active', $snapshot['phone']['assigned_sim']['activation_status']);
    }

    public function test_sim_cards_are_updated_but_not_logged_as_movement_lines(): void
    {
        $user = User::factory()->create();
        Role::findOrCreate('Administrator');
        $user->assignRole('Administrator');

        $warehouse = Location::create(['name' => 'Warehouse', 'code' => 'WH', 'type' => 'internal']);
        $destination = Location::create(['name' => 'Lot A', 'code' => 'LOT-A', 'type' => 'client']);
        $simItem = InventoryItem::create([
            'asset_tag' => 'SIM-ONLY',
            'item_type' => InventoryItem::TYPE_SIM_CARD,
            'status' => InventoryItem::STATUS_IN_STOCK,
            'location_id' => $warehouse->id,
        ]);
        $simItem->simCard()->create([
            'iccid' => '89014103211118510888',
            'associated_phone_number' => '555-0888',
            'activation_status' => 'active',
        ]);

        $movement = app(InventoryMovementService::class)->recordMovement([
            'movement_type' => InventoryMovement::TYPE_DEPLOYMENT,
            'to_location_id' => $destination->id,
            'item_ids' => [$simItem->id],
        ], $user);

        $this->assertSame($destination->id, $simItem->fresh()->location_id);
        $this->assertSame(InventoryItem::STATUS_DEPLOYED, $simItem->fresh()->status);
        $this->assertCount(0, $movement->lines);
        $this->assertDatabaseMissing('inventory_movement_lines', [
            'inventory_movement_id' => $movement->id,
            'inventory_item_id' => $simItem->id,
        ]);
    }

    public function test_movement_numbers_increment_without_aggregate_locks(): void
    {
        $user = User::factory()->create();
        Role::findOrCreate('Administrator');
        $user->assignRole('Administrator');
        $firstItem = InventoryItem::create([
            'asset_tag' => 'MOVE-001',
            'item_type' => InventoryItem::TYPE_GENERIC,
            'status' => InventoryItem::STATUS_RECEIVED,
        ]);
        $secondItem = InventoryItem::create([
            'asset_tag' => 'MOVE-002',
            'item_type' => InventoryItem::TYPE_GENERIC,
            'status' => InventoryItem::STATUS_RECEIVED,
        ]);
        $service = app(InventoryMovementService::class);

        $firstMovement = $service->recordMovement([
            'movement_type' => InventoryMovement::TYPE_RECEIVING,
            'item_ids' => [$firstItem->id],
        ], $user);
        $secondMovement = $service->recordMovement([
            'movement_type' => InventoryMovement::TYPE_RECEIVING,
            'item_ids' => [$secondItem->id],
        ], $user);

        $this->assertStringEndsWith('-0001', $firstMovement->movement_number);
        $this->assertStringEndsWith('-0002', $secondMovement->movement_number);
    }

    public function test_it_updates_a_movement_and_reapplies_asset_state(): void
    {
        $user = User::factory()->create();
        Role::findOrCreate('Administrator');
        $user->assignRole('Administrator');
        $warehouse = Location::create(['name' => 'Warehouse', 'code' => 'WH', 'type' => 'internal']);
        $repair = Location::create(['name' => 'Repair Bench', 'code' => 'REPAIR', 'type' => 'internal']);
        $destination = Location::create(['name' => 'Shelf B', 'code' => 'SHELF-B', 'type' => 'internal']);
        $item = InventoryItem::create([
            'asset_tag' => 'MOVE-EDIT-001',
            'item_type' => InventoryItem::TYPE_GENERIC,
            'status' => InventoryItem::STATUS_IN_STOCK,
            'location_id' => $warehouse->id,
        ]);
        $service = app(InventoryMovementService::class);
        $movement = $service->recordMovement([
            'movement_type' => InventoryMovement::TYPE_REPAIR_INTAKE,
            'occurred_at' => now()->format('Y-m-d\TH:i'),
            'to_location_id' => $repair->id,
            'item_ids' => [$item->id],
            'notes' => 'Wrong movement.',
        ], $user);

        $updated = $service->updateMovement($movement, [
            'movement_type' => InventoryMovement::TYPE_TRANSFER,
            'occurred_at' => now()->format('Y-m-d\TH:i'),
            'to_location_id' => $destination->id,
            'item_ids' => [$item->id],
            'notes' => 'Corrected movement.',
        ]);

        $this->assertSame(InventoryMovement::TYPE_TRANSFER, $updated->movement_type);
        $this->assertSame('Corrected movement.', $updated->notes);
        $this->assertSame($destination->id, $item->fresh()->location_id);
        $this->assertSame(InventoryItem::STATUS_IN_STOCK, $item->fresh()->status);
        $this->assertDatabaseHas('inventory_movement_lines', [
            'inventory_movement_id' => $movement->id,
            'inventory_item_id' => $item->id,
            'previous_location_id' => $warehouse->id,
            'new_location_id' => $destination->id,
            'previous_status' => InventoryItem::STATUS_IN_STOCK,
            'new_status' => InventoryItem::STATUS_IN_STOCK,
        ]);
    }

    public function test_it_deletes_a_movement_and_rolls_back_all_selected_assets(): void
    {
        $user = User::factory()->create();
        Role::findOrCreate('Administrator');
        $user->assignRole('Administrator');
        $warehouse = Location::create(['name' => 'Warehouse', 'code' => 'WH', 'type' => 'internal']);
        $destination = Location::create(['name' => 'Lot A', 'code' => 'LOT-A', 'type' => 'client']);
        $item = InventoryItem::create([
            'asset_tag' => 'MOVE-DELETE-001',
            'item_type' => InventoryItem::TYPE_GENERIC,
            'status' => InventoryItem::STATUS_RECEIVED,
            'location_id' => $warehouse->id,
        ]);
        $simItem = InventoryItem::create([
            'asset_tag' => 'MOVE-DELETE-SIM',
            'item_type' => InventoryItem::TYPE_SIM_CARD,
            'status' => InventoryItem::STATUS_RECEIVED,
            'location_id' => $warehouse->id,
        ]);
        $simItem->simCard()->create(['iccid' => '89014103211118510999']);
        $service = app(InventoryMovementService::class);
        $movement = $service->recordMovement([
            'movement_type' => InventoryMovement::TYPE_DEPLOYMENT,
            'occurred_at' => now()->format('Y-m-d\TH:i'),
            'to_location_id' => $destination->id,
            'item_ids' => [$item->id, $simItem->id],
        ], $user);

        $service->deleteMovement($movement);

        $this->assertDatabaseMissing('inventory_movements', ['id' => $movement->id]);
        $this->assertSame($warehouse->id, $item->fresh()->location_id);
        $this->assertSame(InventoryItem::STATUS_RECEIVED, $item->fresh()->status);
        $this->assertSame($warehouse->id, $simItem->fresh()->location_id);
        $this->assertSame(InventoryItem::STATUS_RECEIVED, $simItem->fresh()->status);
    }

    public function test_it_blocks_changes_when_an_asset_has_newer_movement_history(): void
    {
        $user = User::factory()->create();
        Role::findOrCreate('Administrator');
        $user->assignRole('Administrator');
        $warehouse = Location::create(['name' => 'Warehouse', 'code' => 'WH', 'type' => 'internal']);
        $firstDestination = Location::create(['name' => 'Lot A', 'code' => 'LOT-A', 'type' => 'client']);
        $secondDestination = Location::create(['name' => 'Lot B', 'code' => 'LOT-B', 'type' => 'client']);
        $item = InventoryItem::create([
            'asset_tag' => 'MOVE-GUARD-001',
            'item_type' => InventoryItem::TYPE_GENERIC,
            'status' => InventoryItem::STATUS_RECEIVED,
            'location_id' => $warehouse->id,
        ]);
        $service = app(InventoryMovementService::class);
        $firstMovement = $service->recordMovement([
            'movement_type' => InventoryMovement::TYPE_DEPLOYMENT,
            'occurred_at' => now()->subHour()->format('Y-m-d\TH:i'),
            'to_location_id' => $firstDestination->id,
            'item_ids' => [$item->id],
        ], $user);
        $service->recordMovement([
            'movement_type' => InventoryMovement::TYPE_TRANSFER,
            'occurred_at' => now()->format('Y-m-d\TH:i'),
            'to_location_id' => $secondDestination->id,
            'item_ids' => [$item->id],
        ], $user);

        $this->expectException(ValidationException::class);

        $service->updateMovement($firstMovement, [
            'movement_type' => InventoryMovement::TYPE_RETURN,
            'occurred_at' => now()->subHour()->format('Y-m-d\TH:i'),
            'to_location_id' => $warehouse->id,
            'item_ids' => [$item->id],
        ]);
    }
}
