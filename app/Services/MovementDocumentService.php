<?php

namespace App\Services;

use App\Models\GeneratedDocument;
use App\Models\InventoryMovement;
use App\Models\User;
use Illuminate\Support\Facades\File;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\SimpleType\Jc;

class MovementDocumentService
{
    public function generate(InventoryMovement $movement, User $user, string $templateKey = 'standard_movement'): GeneratedDocument
    {
        $movement->loadMissing(['user', 'client', 'fromLocation', 'toLocation', 'lines.inventoryItem', 'lines.previousLocation.client', 'lines.newLocation.client']);

        $phpWord = new PhpWord;
        $phpWord->setDefaultFontName('Arial');
        $phpWord->setDefaultFontSize(10);
        $phpWord->addFontStyle('header', ['bold' => true, 'size' => 10]);

        $section = $phpWord->addSection(['marginTop' => 720, 'marginBottom' => 720, 'marginLeft' => 720, 'marginRight' => 720]);
        $section->addText('INVENTORY MOVEMENT LOG', ['bold' => true, 'size' => 11], ['alignment' => Jc::CENTER]);
        $section->addTextBreak();
        $section->addText('DATE: '.now()->format('n/j/Y'));

        $table = $section->addTable(['borderSize' => 6, 'borderColor' => '000000', 'cellMargin' => 70]);
        $headers = ['UNIT ID', 'PHONE', 'DESCRIPTION', 'FROM', 'TO'];
        $widths = [1200, 1500, 4300, 1400, 1400];
        $table->addRow();
        foreach ($headers as $index => $header) {
            $table->addCell($widths[$index], ['valign' => 'center'])->addText($header, 'header');
        }

        foreach ($movement->lines->reject(fn ($line): bool => ($line->item_snapshot['item_type'] ?? null) === 'sim_card') as $line) {
            $snapshot = $line->item_snapshot;
            $table->addRow();
            $table->addCell($widths[0], ['valign' => 'top'])->addText($snapshot['asset_tag'] ?? '');
            $table->addCell($widths[1], ['valign' => 'top'])->addText($this->phoneColumn($snapshot));
            $table->addCell($widths[2], ['valign' => 'top'])->addText($this->descriptionColumn($snapshot, $movement));
            $table->addCell($widths[3], ['valign' => 'top'])->addText($this->locationCode($line->previousLocation) ?: $this->locationCode($movement->fromLocation));
            $table->addCell($widths[4], ['valign' => 'top'])->addText($this->locationCode($line->newLocation) ?: $this->locationCode($movement->toLocation));
        }

        $section->addTextBreak();
        $section->addText($movement->occurred_at->format('n/j/Y').' '."\u{2013}".$this->initials($movement->user?->name ?? $user->name));

        $directory = storage_path('app/generated-documents');
        File::ensureDirectoryExists($directory);
        $filename = $movement->movement_number.'-'.str($movement->movement_type)->slug().'.docx';
        $absolutePath = $directory.'/'.$filename;
        IOFactory::createWriter($phpWord, 'Word2007')->save($absolutePath);

        $document = GeneratedDocument::create([
            'inventory_movement_id' => $movement->id,
            'user_id' => $user->id,
            'document_type' => $movement->movement_type,
            'template_key' => $templateKey,
            'file_path' => 'generated-documents/'.$filename,
            'original_filename' => $filename,
            'checksum' => hash_file('sha256', $absolutePath),
            'generated_at' => now(),
            'metadata' => [
                'template_source' => 'inventory_movement_log_examples',
                'matched_examples' => [
                    'BANTANJ_20260501.doc',
                    'MetAnschutz_20260520.doc',
                    'UnifiedLA_20260505.doc',
                    'WVState_20260511.doc',
                ],
            ],
        ]);

        activity('documents')
            ->performedOn($movement)
            ->causedBy($user)
            ->withProperties(['document_id' => $document->id, 'filename' => $filename])
            ->event('document_generated')
            ->log('Movement document generated');

        return $document;
    }

    private function phoneColumn(array $snapshot): string
    {
        $phone = $snapshot['phone'] ?? null;
        $sim = $phone['assigned_sim'] ?? ($snapshot['sim_card'] ?? null);

        return $sim['associated_phone_number'] ?? ($sim['phone_number'] ?? '');
    }

    private function descriptionColumn(array $snapshot, InventoryMovement $movement): string
    {
        if (! blank($snapshot['description'] ?? null)) {
            return $snapshot['description'];
        }

        $base = trim(collect([$snapshot['manufacturer'] ?? null, $snapshot['model'] ?? null])->filter()->implode(' '));

        if (! blank($base)) {
            return $base;
        }

        return match ($snapshot['item_type'] ?? null) {
            'printer' => 'PRINTER',
            'phone' => 'PHONE',
            'modem' => 'MODEM',
            'sim_card' => 'SIM CARD',
            default => $movement->notes ?: str($snapshot['item_type'] ?? 'Equipment')->headline()->toString(),
        };
    }

    private function locationCode($location): string
    {
        if (! $location) {
            return '';
        }

        return $location->code ?: ($location->client?->code ?: $location->name);
    }

    private function initials(string $name): string
    {
        $words = preg_split('/\s+/', trim($name)) ?: [];
        $initials = collect($words)
            ->filter()
            ->map(fn (string $word): string => str($word)->substr(0, 1)->upper()->toString())
            ->implode('');

        return $initials ?: 'STAFF';
    }
}
