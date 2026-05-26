<?php

namespace App\Services;

use App\Events\InventoryMovementRecorded;
use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use App\Models\InventoryMovementLine;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class InventoryMovementService
{
    public function recordMovement(array $data, User $user): InventoryMovement
    {
        $attempts = 5;

        for ($attempt = 1; $attempt <= $attempts; $attempt++) {
            try {
                return DB::transaction(function () use ($data, $user): InventoryMovement {
                    $movement = InventoryMovement::create([
                        'movement_number' => $this->nextMovementNumber(),
                        'movement_type' => $data['movement_type'],
                        'occurred_at' => Carbon::parse($data['occurred_at'] ?? now()),
                        'user_id' => $user->id,
                        'from_location_id' => $data['from_location_id'] ?? null,
                        'to_location_id' => $data['to_location_id'] ?? null,
                        'client_id' => $data['client_id'] ?? null,
                        'notes' => $data['notes'] ?? null,
                        'metadata' => $data['metadata'] ?? null,
                    ]);

                    foreach (array_values($data['item_ids']) as $index => $itemId) {
                        $item = InventoryItem::query()
                            ->with(['client', 'location', 'phone.assignedSimCard.inventoryItem', 'phone.assignedPrinter.inventoryItem', 'printer', 'modem.assignedSimCard.inventoryItem', 'simCard'])
                            ->lockForUpdate()
                            ->findOrFail($itemId);

                        $previousLocationId = $item->location_id;
                        $previousClientId = $item->client_id;
                        $previousStatus = $item->status;
                        $newLocationId = $this->targetLocation($data, $item);
                        $newClientId = $this->targetClient($data, $item);
                        $newStatus = $data['new_status'] ?? $this->statusForMovement($data['movement_type'], $item->status);

                        InventoryMovementLine::create([
                            'inventory_movement_id' => $movement->id,
                            'inventory_item_id' => $item->id,
                            'previous_location_id' => $previousLocationId,
                            'new_location_id' => $newLocationId,
                            'previous_client_id' => $previousClientId,
                            'new_client_id' => $newClientId,
                            'previous_status' => $previousStatus,
                            'new_status' => $newStatus,
                            'item_snapshot' => $this->snapshot($item),
                            'sequence' => $index + 1,
                        ]);

                        $updates = [
                            'location_id' => $newLocationId,
                            'client_id' => $newClientId,
                            'status' => $newStatus,
                        ];

                        if ($data['movement_type'] === InventoryMovement::TYPE_DEPLOYMENT && ! $item->deployed_at) {
                            $updates['deployed_at'] = Carbon::parse($data['occurred_at'] ?? now())->toDateString();
                        }

                        if ($data['movement_type'] === InventoryMovement::TYPE_RETIREMENT) {
                            $updates['retired_at'] = Carbon::parse($data['occurred_at'] ?? now())->toDateString();
                        }

                        $item->update($updates);
                    }

                    $movement->load(['user', 'client', 'fromLocation', 'toLocation', 'lines.inventoryItem.phone.assignedSimCard.inventoryItem', 'lines.inventoryItem.phone.assignedPrinter.inventoryItem', 'lines.inventoryItem.printer', 'lines.inventoryItem.modem.assignedSimCard.inventoryItem', 'lines.inventoryItem.simCard']);
                    event(new InventoryMovementRecorded($movement));

                    return $movement;
                });
            } catch (QueryException $exception) {
                if (! $this->isDuplicateMovementNumberException($exception) || $attempt === $attempts) {
                    throw $exception;
                }
            }
        }

        throw new \RuntimeException('Unable to record inventory movement.');
    }

    private function nextMovementNumber(): string
    {
        $prefix = 'MOV-'.now()->format('Ymd').'-';
        $nextSequence = (InventoryMovement::query()
            ->where('movement_number', 'like', $prefix.'%')
            ->lockForUpdate()
            ->pluck('movement_number')
            ->map(fn (string $movementNumber): int => (int) substr($movementNumber, strlen($prefix)))
            ->max() ?? 0) + 1;

        return $prefix.str_pad((string) $nextSequence, 4, '0', STR_PAD_LEFT);
    }

    private function isDuplicateMovementNumberException(QueryException $exception): bool
    {
        $sqlState = (string) ($exception->errorInfo[0] ?? $exception->getCode());

        return in_array($sqlState, ['23000', '23505'], true)
            && str_contains($exception->getMessage(), 'movement_number');
    }

    private function targetLocation(array $data, InventoryItem $item): ?string
    {
        return array_key_exists('to_location_id', $data) ? $data['to_location_id'] : $item->location_id;
    }

    private function targetClient(array $data, InventoryItem $item): ?string
    {
        return match ($data['movement_type']) {
            InventoryMovement::TYPE_RECEIVING,
            InventoryMovement::TYPE_RETURN,
            InventoryMovement::TYPE_REPAIR_INTAKE,
            InventoryMovement::TYPE_REPAIR_RETURN,
            InventoryMovement::TYPE_RETIREMENT => $data['client_id'] ?? null,
            default => array_key_exists('client_id', $data) ? $data['client_id'] : $item->client_id,
        };
    }

    private function statusForMovement(string $movementType, string $currentStatus): string
    {
        return match ($movementType) {
            InventoryMovement::TYPE_RECEIVING => InventoryItem::STATUS_IN_STOCK,
            InventoryMovement::TYPE_DEPLOYMENT => InventoryItem::STATUS_DEPLOYED,
            InventoryMovement::TYPE_RETURN => InventoryItem::STATUS_RETURNED,
            InventoryMovement::TYPE_REPAIR_INTAKE => InventoryItem::STATUS_IN_REPAIR,
            InventoryMovement::TYPE_REPAIR_RETURN => InventoryItem::STATUS_IN_STOCK,
            InventoryMovement::TYPE_RETIREMENT => InventoryItem::STATUS_RETIRED,
            default => $currentStatus,
        };
    }

    private function snapshot(InventoryItem $item): array
    {
        return [
            'asset_tag' => $item->asset_tag,
            'item_type' => $item->item_type,
            'category' => $item->category,
            'name' => $item->name,
            'description' => $item->description,
            'manufacturer' => $item->manufacturer,
            'model' => $item->model,
            'serial_number' => $item->serial_number,
            'status' => $item->status,
            'client' => $item->client?->only(['id', 'name', 'code']),
            'location' => $item->location?->only(['id', 'name', 'type', 'code']),
            'phone' => $item->phone ? [
                'phone_number' => $item->phone->phone_number,
                'carrier' => $item->phone->carrier,
                'imei' => $item->phone->imei,
                'android_version' => $item->phone->android_version,
                'assigned_sim' => $item->phone->assignedSimCard ? [
                    'iccid' => $item->phone->assignedSimCard->iccid,
                    'phone_number' => $item->phone->assignedSimCard->associated_phone_number,
                    'carrier' => $item->phone->assignedSimCard->carrier,
                ] : null,
                'assigned_printer' => $item->phone->assignedPrinter?->inventoryItem?->only(['asset_tag', 'serial_number', 'manufacturer', 'model']),
            ] : null,
            'printer' => $item->printer ? [
                'printer_identifier' => $item->printer->printer_identifier,
                'printer_color' => $item->printer->printer_color,
                'firmware_version' => $item->printer->firmware_version,
            ] : null,
            'modem' => $item->modem ? [
                'imei' => $item->modem->imei,
                'carrier' => $item->modem->carrier,
                'assigned_sim' => $item->modem->assignedSimCard ? [
                    'iccid' => $item->modem->assignedSimCard->iccid,
                    'phone_number' => $item->modem->assignedSimCard->associated_phone_number,
                    'carrier' => $item->modem->assignedSimCard->carrier,
                ] : null,
            ] : null,
            'sim_card' => $item->simCard ? [
                'iccid' => $item->simCard->iccid,
                'imsi' => $item->simCard->imsi,
                'carrier' => $item->simCard->carrier,
                'associated_phone_number' => $item->simCard->associated_phone_number,
                'activation_status' => $item->simCard->activation_status,
            ] : null,
        ];
    }
}
