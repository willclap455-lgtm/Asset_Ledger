<?php

namespace App\Services;

use App\Models\GeneratedDocument;
use App\Models\InventoryMovement;
use App\Models\User;
use Illuminate\Support\Facades\File;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpWord\Style\Cell;

class MovementDocumentService
{
    public function generate(InventoryMovement $movement, User $user, string $templateKey = 'standard_movement'): GeneratedDocument
    {
        $movement->loadMissing(['user', 'client', 'fromLocation', 'toLocation', 'lines.inventoryItem']);

        $phpWord = new PhpWord();
        $phpWord->setDefaultFontName('Arial');
        $phpWord->setDefaultFontSize(10);
        $phpWord->addTitleStyle(1, ['bold' => true, 'size' => 16], ['alignment' => Jc::CENTER]);
        $phpWord->addFontStyle('label', ['bold' => true, 'size' => 9]);
        $phpWord->addFontStyle('small', ['size' => 8]);

        $section = $phpWord->addSection(['marginTop' => 720, 'marginBottom' => 720, 'marginLeft' => 720, 'marginRight' => 720]);
        $section->addTitle('Inventory Movement Form', 1);
        $section->addText(InventoryMovement::typeOptions()[$movement->movement_type] ?? str($movement->movement_type)->headline(), ['bold' => true, 'size' => 12], ['alignment' => Jc::CENTER]);
        $section->addTextBreak();

        $summary = $section->addTable(['borderSize' => 6, 'borderColor' => '333333', 'cellMargin' => 80]);
        $this->summaryRow($summary, 'Movement #', $movement->movement_number, 'Date', $movement->occurred_at->format('m/d/Y g:i A'));
        $this->summaryRow($summary, 'Prepared By', $movement->user->name, 'Client', $movement->client?->name ?? 'Internal / Unassigned');
        $this->summaryRow($summary, 'From', $movement->fromLocation?->label() ?? 'N/A', 'To', $movement->toLocation?->label() ?? 'N/A');
        if ($movement->notes) {
            $summary->addRow();
            $summary->addCell(1800, ['bgColor' => 'E7E6E6'])->addText('Notes', 'label');
            $summary->addCell(8200, ['gridSpan' => 3])->addText($movement->notes);
        }

        $section->addTextBreak();
        $section->addText('Equipment Detail', ['bold' => true, 'size' => 12]);
        $table = $section->addTable(['borderSize' => 6, 'borderColor' => '777777', 'cellMargin' => 70]);
        $headers = ['Asset ID', 'Type', 'Serial / IMEI', 'Phone #', 'Carrier', 'SIM ICCID', 'Status'];
        $table->addRow();
        foreach ($headers as $header) {
            $table->addCell(1450, ['bgColor' => 'D9EAF7', 'valign' => Cell::VALIGN_CENTER])->addText($header, 'label');
        }

        foreach ($movement->lines as $line) {
            $snapshot = $line->item_snapshot;
            $phone = $snapshot['phone'] ?? null;
            $modem = $snapshot['modem'] ?? null;
            $sim = $snapshot['sim_card'] ?? ($phone['assigned_sim'] ?? ($modem['assigned_sim'] ?? null));
            $serial = $snapshot['serial_number'] ?? ($phone['imei'] ?? ($modem['imei'] ?? ''));
            $table->addRow();
            $table->addCell(1450)->addText($snapshot['asset_tag'] ?? '');
            $table->addCell(1450)->addText(str($snapshot['item_type'] ?? '')->headline()->toString());
            $table->addCell(1450)->addText($serial ?: '');
            $table->addCell(1450)->addText($phone['phone_number'] ?? ($sim['phone_number'] ?? ''));
            $table->addCell(1450)->addText($phone['carrier'] ?? ($modem['carrier'] ?? ($sim['carrier'] ?? '')));
            $table->addCell(1450)->addText($sim['iccid'] ?? '');
            $table->addCell(1450)->addText($line->new_status ?: $line->previous_status ?: '');
        }

        $section->addTextBreak();
        $section->addText('Operational Sign-Off', ['bold' => true, 'size' => 12]);
        $sign = $section->addTable(['borderSize' => 6, 'borderColor' => '333333', 'cellMargin' => 120]);
        $this->signatureRow($sign, 'Released By');
        $this->signatureRow($sign, 'Received By');

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
                'template_source' => 'standard_generated_layout',
                'note' => 'Replace or extend this profile after production DOCX templates are supplied.',
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

    private function summaryRow($table, string $leftLabel, string $leftValue, string $rightLabel, string $rightValue): void
    {
        $table->addRow();
        $table->addCell(1800, ['bgColor' => 'E7E6E6'])->addText($leftLabel, 'label');
        $table->addCell(3200)->addText($leftValue);
        $table->addCell(1800, ['bgColor' => 'E7E6E6'])->addText($rightLabel, 'label');
        $table->addCell(3200)->addText($rightValue);
    }

    private function signatureRow($table, string $label): void
    {
        $table->addRow(600);
        $table->addCell(2000, ['bgColor' => 'E7E6E6'])->addText($label, 'label');
        $table->addCell(3500)->addText('Signature: ______________________________');
        $table->addCell(2500)->addText('Date: ______________');
    }
}
