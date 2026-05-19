<?php

namespace App\Services;

use App\Models\InventoryItem;
use App\Models\Modem;
use App\Models\Phone;
use App\Models\Printer;
use App\Models\SimCard;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

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
        match ($item->item_type) {
            InventoryItem::TYPE_PHONE => $this->syncPhone($item, $data),
            InventoryItem::TYPE_PRINTER => $this->syncPrinter($item, $data),
            InventoryItem::TYPE_MODEM => $this->syncModem($item, $data),
            InventoryItem::TYPE_SIM_CARD => $this->syncSimCard($item, $data),
            default => null,
        };
    }

    private function syncPhone(InventoryItem $item, array $data): void
    {
        $phone = Phone::updateOrCreate(
            ['inventory_item_id' => $item->id],
            Arr::only($data, ['phone_number', 'carrier', 'imei', 'android_version', 'assigned_sim_card_id', 'assigned_printer_id'])
        );

        if ($phone->assigned_sim_card_id) {
            SimCard::whereKey($phone->assigned_sim_card_id)->update(['assigned_inventory_item_id' => $item->id]);
        }
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
        $modem = Modem::updateOrCreate(
            ['inventory_item_id' => $item->id],
            Arr::only($data, ['imei', 'carrier', 'assigned_sim_card_id'])
        );

        if ($modem->assigned_sim_card_id) {
            SimCard::whereKey($modem->assigned_sim_card_id)->update(['assigned_inventory_item_id' => $item->id]);
        }
    }

    private function syncSimCard(InventoryItem $item, array $data): void
    {
        SimCard::updateOrCreate(
            ['inventory_item_id' => $item->id],
            Arr::only($data, ['iccid', 'imsi', 'carrier', 'associated_phone_number', 'assigned_inventory_item_id', 'activation_status'])
        );
    }
}
