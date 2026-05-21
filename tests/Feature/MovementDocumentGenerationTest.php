<?php

namespace Tests\Feature;

use App\Models\GeneratedDocument;
use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use App\Models\Location;
use App\Models\User;
use App\Services\InventoryMovementService;
use App\Services\MovementDocumentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use ZipArchive;

class MovementDocumentGenerationTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_generates_and_archives_a_docx_for_a_movement(): void
    {
        $user = User::factory()->create();
        Role::findOrCreate('Administrator');
        $user->assignRole('Administrator');

        $home = Location::create(['name' => 'Home Office', 'code' => 'HOME', 'type' => 'internal']);
        $item = InventoryItem::create([
            'asset_tag' => 'SIM-001',
            'item_type' => InventoryItem::TYPE_SIM_CARD,
            'status' => InventoryItem::STATUS_RECEIVED,
            'location_id' => $home->id,
        ]);
        $item->simCard()->create([
            'iccid' => '89014103211118510720',
            'carrier' => 'AT&T',
            'associated_phone_number' => '555-0199',
            'activation_status' => 'active',
        ]);

        $movement = app(InventoryMovementService::class)->recordMovement([
            'movement_type' => InventoryMovement::TYPE_RECEIVING,
            'to_location_id' => $home->id,
            'item_ids' => [$item->id],
        ], $user);

        $document = app(MovementDocumentService::class)->generate($movement, $user);

        $this->assertInstanceOf(GeneratedDocument::class, $document);
        $this->assertDatabaseHas('generated_documents', ['id' => $document->id, 'inventory_movement_id' => $movement->id]);
        $path = storage_path('app/'.$document->file_path);
        $this->assertFileExists($path);
        $this->assertGreaterThan(0, File::size($path));
        $this->assertStringContainsString('inventory_movement_log_examples', $document->metadata['template_source']);

        $zip = new ZipArchive;
        $this->assertTrue($zip->open($path));
        $xml = $zip->getFromName('word/document.xml');
        $zip->close();

        $this->assertStringContainsString('INVENTORY MOVEMENT LOG', $xml);
        $this->assertStringContainsString('UNIT ID', $xml);
        $this->assertStringContainsString('PHONE', $xml);
        $this->assertStringContainsString('DESCRIPTION', $xml);
        $this->assertStringContainsString('SIM-001', $xml);
        $this->assertStringContainsString('555-0199', $xml);
        $this->assertStringContainsString('HOME', $xml);
    }
}
