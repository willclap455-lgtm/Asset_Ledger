<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\GeneratedDocument;
use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ApplicationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_complete_client_location_and_inventory_crud_flows(): void
    {
        $user = $this->adminUser();

        $this->actingAs($user)->get(route('clients.index'))->assertOk();
        $this->actingAs($user)->get(route('clients.create'))->assertOk();

        $this->actingAs($user)->post(route('clients.store'), [
            'name' => 'Workflow Parking',
            'code' => 'WF',
            'status' => 'active',
            'primary_contact_name' => 'Taylor Ops',
            'primary_contact_email' => 'taylor@example.com',
            'primary_contact_phone' => '555-0101',
            'notes' => 'Created during workflow test.',
        ])->assertRedirect();

        $client = Client::where('code', 'WF')->firstOrFail();
        $this->actingAs($user)->get(route('clients.show', $client))->assertOk()->assertSee('Workflow Parking');

        $this->actingAs($user)->put(route('clients.update', $client), [
            'name' => 'Workflow Parking Updated',
            'code' => 'WF',
            'status' => 'active',
            'primary_contact_email' => 'ops-updated@example.com',
        ])->assertRedirect(route('clients.show', $client));

        $this->actingAs($user)->post(route('clients.import'), [
            'csv_file' => UploadedFile::fake()->createWithContent('clients.csv', "name,code,status,primary_contact_name,primary_contact_email,primary_contact_phone,notes\nImported Parking,IMP,active,Import Ops,import@example.com,555-0900,CSV client\n"),
        ])->assertRedirect(route('clients.index'));
        $this->assertDatabaseHas('clients', [
            'code' => 'IMP',
            'name' => 'Imported Parking',
            'primary_contact_email' => 'import@example.com',
        ]);

        $this->actingAs($user)->get(route('locations.create'))->assertOk();
        $this->actingAs($user)->post(route('locations.store'), [
            'client_id' => $client->id,
            'type' => 'client',
            'name' => 'Workflow Lot A',
            'code' => 'WF-LOT-A',
            'city' => 'Atlanta',
            'state' => 'GA',
            'is_active' => '1',
        ])->assertRedirect(route('locations.index'));

        $location = Location::where('code', 'WF-LOT-A')->firstOrFail();
        $this->actingAs($user)->get(route('locations.edit', $location))->assertOk()->assertSee('Workflow Lot A');

        $this->actingAs($user)->put(route('locations.update', $location), [
            'client_id' => $client->id,
            'type' => 'client',
            'name' => 'Workflow Lot B',
            'code' => 'WF-LOT-A',
            'city' => 'Atlanta',
            'state' => 'GA',
            'is_active' => '1',
        ])->assertRedirect(route('locations.index'));

        $this->actingAs($user)->post(route('locations.import'), [
            'csv_file' => UploadedFile::fake()->createWithContent('locations.csv', "name,code,type,client_code,city,state,is_active\nImported Lot,IMP-LOT,client,WF,Tampa,FL,yes\nImported Shelf,IMP-SHELF,internal,,Atlanta,GA,no\n"),
        ])->assertRedirect(route('locations.index'));
        $this->assertDatabaseHas('locations', [
            'code' => 'IMP-LOT',
            'name' => 'Imported Lot',
            'client_id' => $client->id,
            'is_active' => true,
        ]);
        $this->assertDatabaseHas('locations', [
            'code' => 'IMP-SHELF',
            'name' => 'Imported Shelf',
            'is_active' => false,
        ]);

        $this->actingAs($user)->get(route('inventory-items.create'))->assertOk();
        $this->actingAs($user)->post(route('inventory-items.store'), [
            'asset_tag' => 'WF-ASSET-001',
            'item_type' => InventoryItem::TYPE_PHONE,
            'status' => InventoryItem::STATUS_RECEIVED,
            'name' => 'Workflow Phone',
            'manufacturer' => 'Kyocera',
            'model' => 'DuraForce',
            'serial_number' => 'WF-SN-001',
            'client_id' => $client->id,
            'location_id' => $location->id,
            'phone_number' => '555-0190',
            'carrier' => 'Verizon',
            'imei' => '990000862471854',
            'android_version' => '13',
        ])->assertRedirect();

        $item = InventoryItem::where('asset_tag', 'WF-ASSET-001')->firstOrFail();
        $this->actingAs($user)->get(route('inventory-items.show', $item))
            ->assertOk()
            ->assertSee('WF-ASSET-001')
            ->assertSee('Add Note');

        $this->actingAs($user)->post(route('inventory-items.notes.store', $item), [
            'note_type' => 'operational',
            'body' => 'Workflow note body.',
        ])->assertRedirect();

        $this->assertDatabaseHas('inventory_notes', [
            'inventory_item_id' => $item->id,
            'body' => 'Workflow note body.',
        ]);
        $this->actingAs($user)->get(route('inventory-items.show', $item))->assertOk()->assertSee('Workflow note body.');

        $this->actingAs($user)->post(route('inventory-items.repairs.store', $item), [
            'status' => 'open',
            'issue_description' => 'Workflow repair issue.',
        ])->assertRedirect();

        $this->assertSame(InventoryItem::STATUS_IN_REPAIR, $item->fresh()->status);
        $this->actingAs($user)->delete(route('inventory-items.destroy', $item))
            ->assertRedirect()
            ->assertSessionHasErrors();
        $this->assertDatabaseHas('inventory_items', ['id' => $item->id]);

        $deleteOnlyItem = InventoryItem::create([
            'asset_tag' => 'WF-ASSET-DELETE',
            'item_type' => InventoryItem::TYPE_GENERIC,
            'status' => InventoryItem::STATUS_IN_STOCK,
            'client_id' => $client->id,
            'location_id' => $location->id,
        ]);

        $this->actingAs($user)->delete(route('inventory-items.destroy', $deleteOnlyItem))
            ->assertRedirect(route('inventory-items.index'));
        $this->assertDatabaseMissing('inventory_items', ['id' => $deleteOnlyItem->id]);

        $this->actingAs($user)->delete(route('locations.destroy', $location))
            ->assertRedirect(route('locations.index'));
        $this->assertDatabaseMissing('locations', ['id' => $location->id]);
        $this->assertNull($item->fresh()->location_id);

        $this->actingAs($user)->delete(route('clients.destroy', $client))
            ->assertRedirect(route('clients.index'));
        $this->assertDatabaseMissing('clients', ['id' => $client->id]);
        $this->assertNull($item->fresh()->client_id);
    }

    public function test_admin_can_record_movement_generate_document_and_export_reports(): void
    {
        $user = $this->adminUser();
        $client = Client::create(['name' => 'Movement Client', 'code' => 'MOVE', 'status' => 'active']);
        $warehouse = Location::create(['name' => 'Workflow Warehouse', 'code' => 'WF-WH', 'type' => 'internal', 'is_active' => true]);
        $destination = Location::create(['client_id' => $client->id, 'name' => 'Destination Lot', 'code' => 'WF-DEST', 'type' => 'client', 'is_active' => true]);
        $simItem = InventoryItem::create([
            'asset_tag' => 'WF-MOVE-SIM',
            'item_type' => InventoryItem::TYPE_SIM_CARD,
            'status' => InventoryItem::STATUS_RECEIVED,
            'location_id' => $warehouse->id,
        ]);
        $sim = $simItem->simCard()->create([
            'iccid' => '89014103211118510799',
            'carrier' => 'AT&T',
            'associated_phone_number' => '555-0199',
            'activation_status' => 'active',
        ]);
        $item = InventoryItem::create([
            'asset_tag' => 'WF-MOVE-001',
            'item_type' => InventoryItem::TYPE_PHONE,
            'status' => InventoryItem::STATUS_RECEIVED,
            'location_id' => $warehouse->id,
        ]);
        $item->phone()->create([
            'imei' => '990000862471855',
            'assigned_sim_card_id' => $sim->id,
        ]);
        $sim->update(['assigned_inventory_item_id' => $item->id]);

        $this->actingAs($user)->get(route('movements.create', ['item_ids' => [$item->id]]))
            ->assertOk()
            ->assertSee('WF-MOVE-001');

        $this->actingAs($user)->post(route('movements.store'), [
            'movement_type' => InventoryMovement::TYPE_DEPLOYMENT,
            'occurred_at' => now()->format('Y-m-d\TH:i'),
            'from_location_id' => $warehouse->id,
            'to_location_id' => $destination->id,
            'client_id' => $client->id,
            'notes' => 'Workflow deployment.',
            'item_ids' => [$item->id, $simItem->id],
        ])->assertRedirect();

        $movement = InventoryMovement::where('notes', 'Workflow deployment.')->firstOrFail();
        $this->actingAs($user)->get(route('movements.show', $movement))
            ->assertOk()
            ->assertSee($movement->movement_number)
            ->assertSee('WF-MOVE-001')
            ->assertSee('555-0199')
            ->assertDontSee('WF-MOVE-SIM');

        $this->assertSame(InventoryItem::STATUS_DEPLOYED, $item->fresh()->status);
        $this->assertSame($destination->id, $item->fresh()->location_id);
        $this->assertSame($client->id, $item->fresh()->client_id);
        $this->assertSame(InventoryItem::STATUS_DEPLOYED, $simItem->fresh()->status);
        $this->assertSame($destination->id, $simItem->fresh()->location_id);
        $this->assertCount(1, $movement->lines);

        $this->actingAs($user)->post(route('movements.documents.store', $movement))->assertRedirect();
        $document = GeneratedDocument::where('inventory_movement_id', $movement->id)->firstOrFail();
        $this->actingAs($user)->get(route('generated-documents.download', $document))->assertOk();

        $this->actingAs($user)->get(route('reports.index'))->assertOk()->assertSee('Movement Client');
        $this->actingAs($user)->get(route('reports.inventory-export'))->assertOk();
    }

    private function adminUser(): User
    {
        $user = User::factory()->create();
        Role::findOrCreate('Administrator');
        $user->assignRole('Administrator');

        return $user;
    }
}
