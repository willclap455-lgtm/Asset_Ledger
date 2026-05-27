<?php

namespace App\Services;

use App\Models\InventoryItem;
use App\Models\Modem;
use App\Models\Phone;
use App\Models\Printer;
use App\Models\SimCard;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventoryItemService
{
    public function create(array $data): InventoryItem
    {
        return DB::transaction(function () use ($data): InventoryItem {
            $item = InventoryItem::create($this->baseAttributes($data));
            $this->syncTypedDetails($item, $data);

            return $item->refresh()->load(['phone.assignedSimCard.inventoryItem', 'phone.assignedPrinter.inventoryItem', 'printer', 'modem.assignedSimCard.inventoryItem', 'simCard']);
        });
    }

    public function update(InventoryItem $item, array $data): InventoryItem
    {
        return DB::transaction(function () use ($item, $data): InventoryItem {
            $item->update($this->baseAttributes($data));
            $this->syncTypedDetails($item, $data);

            return $item->refresh()->load(['phone.assignedSimCard.inventoryItem', 'phone.assignedPrinter.inventoryItem', 'printer', 'modem.assignedSimCard.inventoryItem', 'simCard']);
        });
    }

    private function baseAttributes(array $data): array
    {
        return Arr::only($data, [
            'asset_tag', 'item_type', 'category', 'name', 'description', 'manufacturer', 'model',
            'serial_number', 'status', 'condition', 'client_id', 'location_id', 'received_at',
            'deployed_at', 'retired_at', 'notes', 'extra_attributes',
        ]);
    }

    private function syncTypedDetails(InventoryItem $item, array $data): void
    {
        $this->pruneObsoleteTypedDetails($item);

        match ($item->item_type) {
            InventoryItem::TYPE_PHONE => $this->syncPhone($item, $data),
            InventoryItem::TYPE_PRINTER => $this->syncPrinter($item, $data),
            InventoryItem::TYPE_MODEM => $this->syncModem($item, $data),
            InventoryItem::TYPE_SIM_CARD => $this->syncSimCard($item, $data),
            default => null,
        };
    }

    private function pruneObsoleteTypedDetails(InventoryItem $item): void
    {
        if ($item->item_type !== InventoryItem::TYPE_PHONE && $phone = $item->phone()->first()) {
            $this->clearSimCardAssignment($item, $phone->assigned_sim_card_id);
            $phone->delete();
        }

        if ($item->item_type !== InventoryItem::TYPE_MODEM && $modem = $item->modem()->first()) {
            $this->clearSimCardAssignment($item, $modem->assigned_sim_card_id);
            $modem->delete();
        }

        if ($item->item_type !== InventoryItem::TYPE_PRINTER) {
            $item->printer()->delete();
        }

        if ($item->item_type !== InventoryItem::TYPE_SIM_CARD) {
            $item->simCard()->delete();
        }
    }

    private function syncPhone(InventoryItem $item, array $data): void
    {
        $existingPhone = $item->phone()->first();
        $previousSimCardId = $existingPhone?->assigned_sim_card_id;
        $attributes = Arr::only($data, ['carrier', 'imei', 'android_version', 'assigned_sim_card_id', 'assigned_printer_id']);
        $newSimCardId = $attributes['assigned_sim_card_id'] ?? null;

        $this->ensureSimCardIsAvailable($newSimCardId, $item);

        $phone = Phone::updateOrCreate(
            ['inventory_item_id' => $item->id],
            $attributes
        );

        $this->syncSimCardAssignment($item, $previousSimCardId, $phone->assigned_sim_card_id);
    }

    private function syncPrinter(InventoryItem $item, array $data): void
    {
        Printer::updateOrCreate(
            ['inventory_item_id' => $item->id],
            Arr::only($data, ['printer_identifier', 'printer_color', 'firmware_version'])
        );
    }

    private function syncModem(InventoryItem $item, array $data): void
    {
        $existingModem = $item->modem()->first();
        $previousSimCardId = $existingModem?->assigned_sim_card_id;
        $attributes = Arr::only($data, ['imei', 'carrier', 'assigned_sim_card_id']);
        $newSimCardId = $attributes['assigned_sim_card_id'] ?? null;

        $this->ensureSimCardIsAvailable($newSimCardId, $item);

        $modem = Modem::updateOrCreate(
            ['inventory_item_id' => $item->id],
            $attributes
        );

        $this->syncSimCardAssignment($item, $previousSimCardId, $modem->assigned_sim_card_id);
    }

    private function syncSimCard(InventoryItem $item, array $data): void
    {
        SimCard::updateOrCreate(
            ['inventory_item_id' => $item->id],
            Arr::only($data, ['iccid', 'imsi', 'carrier', 'associated_phone_number', 'assigned_inventory_item_id', 'activation_status'])
        );
    }

    private function ensureSimCardIsAvailable(?string $simCardId, InventoryItem $item): void
    {
        if (! $simCardId) {
            return;
        }

        $assignedToAnotherPhone = Phone::where('assigned_sim_card_id', $simCardId)
            ->where('inventory_item_id', '!=', $item->id)
            ->exists();
        $assignedToAnotherModem = Modem::where('assigned_sim_card_id', $simCardId)
            ->where('inventory_item_id', '!=', $item->id)
            ->exists();

        if ($assignedToAnotherPhone || $assignedToAnotherModem) {
            throw ValidationException::withMessages([
                'assigned_sim_card_id' => 'This SIM card is already assigned to another device.',
            ]);
        }
    }

    private function syncSimCardAssignment(InventoryItem $item, ?string $previousSimCardId, ?string $newSimCardId): void
    {
        SimCard::where('assigned_inventory_item_id', $item->id)
            ->when($newSimCardId, fn ($query) => $query->whereKeyNot($newSimCardId))
            ->update(['assigned_inventory_item_id' => null]);

        if ($previousSimCardId && $previousSimCardId !== $newSimCardId) {
            $this->clearSimCardAssignment($item, $previousSimCardId);
        }

        if ($newSimCardId) {
            SimCard::whereKey($newSimCardId)->update(['assigned_inventory_item_id' => $item->id]);
        }
    }

    private function clearSimCardAssignment(InventoryItem $item, ?string $simCardId): void
    {
        if (! $simCardId) {
            return;
        }

        SimCard::whereKey($simCardId)
            ->where('assigned_inventory_item_id', $item->id)
            ->update(['assigned_inventory_item_id' => null]);
    }
}
