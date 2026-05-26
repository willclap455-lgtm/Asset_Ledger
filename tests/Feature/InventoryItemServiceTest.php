<?php

namespace Tests\Feature;

use App\Models\InventoryItem;
use App\Models\User;
use App\Services\InventoryItemService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class InventoryItemServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_changing_item_type_removes_obsolete_typed_details_and_clears_sim_assignment(): void
    {
        $service = app(InventoryItemService::class);
        $assignedSimItem = InventoryItem::create([
            'asset_tag' => 'SIM-ASSIGNED',
            'item_type' => InventoryItem::TYPE_SIM_CARD,
            'status' => InventoryItem::STATUS_IN_STOCK,
        ]);
        $assignedSim = $assignedSimItem->simCard()->create([
            'iccid' => '89014103211118510799',
            'carrier' => 'Verizon',
            'associated_phone_number' => '555-0100',
        ]);
        $item = InventoryItem::create([
            'asset_tag' => 'PHONE-CHANGE',
            'item_type' => InventoryItem::TYPE_PHONE,
            'status' => InventoryItem::STATUS_IN_STOCK,
        ]);
        $item->phone()->create([
            'phone_number' => '555-0100',
            'carrier' => 'Verizon',
            'imei' => '111111111111111',
            'assigned_sim_card_id' => $assignedSim->id,
        ]);
        $assignedSim->update(['assigned_inventory_item_id' => $item->id]);

        $service->update($item, [
            'asset_tag' => 'PHONE-CHANGE',
            'item_type' => InventoryItem::TYPE_SIM_CARD,
            'status' => InventoryItem::STATUS_IN_STOCK,
            'iccid' => '89014103211118510800',
            'carrier' => 'AT&T',
            'associated_phone_number' => '555-0200',
        ]);

        $this->assertDatabaseMissing('phones', ['inventory_item_id' => $item->id]);
        $this->assertDatabaseHas('sim_cards', [
            'inventory_item_id' => $item->id,
            'iccid' => '89014103211118510800',
        ]);
        $this->assertNull($assignedSim->fresh()->assigned_inventory_item_id);
    }

    public function test_sim_card_cannot_be_assigned_to_multiple_devices(): void
    {
        $service = app(InventoryItemService::class);
        $simItem = InventoryItem::create([
            'asset_tag' => 'SIM-UNIQUE',
            'item_type' => InventoryItem::TYPE_SIM_CARD,
            'status' => InventoryItem::STATUS_IN_STOCK,
        ]);
        $sim = $simItem->simCard()->create([
            'iccid' => '89014103211118510801',
            'carrier' => 'T-Mobile',
            'associated_phone_number' => '555-0300',
        ]);
        $firstPhone = InventoryItem::create([
            'asset_tag' => 'PHONE-ONE',
            'item_type' => InventoryItem::TYPE_PHONE,
            'status' => InventoryItem::STATUS_IN_STOCK,
        ]);
        $firstPhone->phone()->create([
            'phone_number' => '555-0301',
            'imei' => '222222222222222',
            'assigned_sim_card_id' => $sim->id,
        ]);
        $sim->update(['assigned_inventory_item_id' => $firstPhone->id]);
        $secondPhone = InventoryItem::create([
            'asset_tag' => 'PHONE-TWO',
            'item_type' => InventoryItem::TYPE_PHONE,
            'status' => InventoryItem::STATUS_IN_STOCK,
        ]);

        $this->expectException(ValidationException::class);

        $service->update($secondPhone, [
            'asset_tag' => 'PHONE-TWO',
            'item_type' => InventoryItem::TYPE_PHONE,
            'status' => InventoryItem::STATUS_IN_STOCK,
            'phone_number' => '555-0302',
            'imei' => '333333333333333',
            'assigned_sim_card_id' => $sim->id,
        ]);
    }

    public function test_inventory_search_matches_phone_numbers_without_dashes(): void
    {
        $phoneItem = InventoryItem::create([
            'asset_tag' => 'PHONE-SEARCH',
            'item_type' => InventoryItem::TYPE_PHONE,
            'status' => InventoryItem::STATUS_IN_STOCK,
        ]);
        $phoneItem->phone()->create([
            'phone_number' => '555-0100',
            'imei' => '444444444444444',
        ]);
        $simItem = InventoryItem::create([
            'asset_tag' => 'SIM-SEARCH',
            'item_type' => InventoryItem::TYPE_SIM_CARD,
            'status' => InventoryItem::STATUS_IN_STOCK,
        ]);
        $sim = $simItem->simCard()->create([
            'iccid' => '89014103211118510802',
            'associated_phone_number' => '555-0199',
        ]);
        $assignedPhoneItem = InventoryItem::create([
            'asset_tag' => 'PHONE-ASSIGNED-SIM',
            'item_type' => InventoryItem::TYPE_PHONE,
            'status' => InventoryItem::STATUS_IN_STOCK,
        ]);
        $assignedPhoneItem->phone()->create([
            'imei' => '555555555555555',
            'assigned_sim_card_id' => $sim->id,
        ]);

        $directMatches = InventoryItem::query()->search('5550100')->pluck('asset_tag')->all();
        $assignedSimMatches = InventoryItem::query()->search('5550199')->pluck('asset_tag')->all();

        $this->assertContains('PHONE-SEARCH', $directMatches);
        $this->assertContains('SIM-SEARCH', $assignedSimMatches);
        $this->assertContains('PHONE-ASSIGNED-SIM', $assignedSimMatches);
    }

    public function test_device_show_page_displays_assigned_sim_details(): void
    {
        $user = $this->adminUser();
        $simItem = InventoryItem::create([
            'asset_tag' => 'SIM-SHOW',
            'item_type' => InventoryItem::TYPE_SIM_CARD,
            'status' => InventoryItem::STATUS_IN_STOCK,
        ]);
        $sim = $simItem->simCard()->create([
            'iccid' => '89014103211118510803',
            'carrier' => 'AT&T',
            'associated_phone_number' => '555-0400',
        ]);
        $phoneItem = InventoryItem::create([
            'asset_tag' => 'PHONE-SHOW',
            'item_type' => InventoryItem::TYPE_PHONE,
            'status' => InventoryItem::STATUS_IN_STOCK,
        ]);
        $phoneItem->phone()->create([
            'imei' => '666666666666666',
            'assigned_sim_card_id' => $sim->id,
        ]);

        $this->actingAs($user)
            ->get(route('inventory-items.show', $phoneItem))
            ->assertOk()
            ->assertSee('SIM Phone #')
            ->assertSee('555-0400')
            ->assertSee('AT&amp;T', false)
            ->assertSee('666666666666666');
    }

    private function adminUser(): User
    {
        $user = User::factory()->create();
        Role::findOrCreate('Administrator');
        $user->assignRole('Administrator');

        return $user;
    }
}
