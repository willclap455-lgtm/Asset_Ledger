<?php

namespace App\Http\Requests;

use App\Models\InventoryItem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InventoryItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can($this->route('inventory_item') ? 'update' : 'create', $this->route('inventory_item') ?? InventoryItem::class) ?? false;
    }

    public function rules(): array
    {
        $item = $this->route('inventory_item');
        $phone = $item?->phone;
        $printer = $item?->printer;
        $modem = $item?->modem;
        $simCard = $item?->simCard;

        return [
            'asset_tag' => ['required', 'string', 'max:120', Rule::unique('inventory_items', 'asset_tag')->ignore($item?->id)],
            'item_type' => ['required', Rule::in(array_keys(InventoryItem::typeOptions()))],
            'category' => ['nullable', 'string', 'max:120'],
            'name' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'manufacturer' => ['nullable', 'string', 'max:120'],
            'model' => ['nullable', 'string', 'max:120'],
            'serial_number' => ['nullable', 'string', 'max:160', Rule::unique('inventory_items', 'serial_number')->ignore($item?->id)],
            'status' => ['required', Rule::in(array_keys(InventoryItem::statusOptions()))],
            'condition' => ['nullable', 'string', 'max:120'],
            'client_id' => ['nullable', 'exists:clients,id'],
            'location_id' => ['nullable', 'exists:locations,id'],
            'received_at' => ['nullable', 'date'],
            'deployed_at' => ['nullable', 'date'],
            'retired_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'phone_number' => ['nullable', 'string', 'max:64'],
            'carrier' => ['nullable', 'string', 'max:120'],
            'imei' => [Rule::requiredIf(fn () => in_array($this->input('item_type'), [InventoryItem::TYPE_PHONE, InventoryItem::TYPE_MODEM], true) && blank($this->input('serial_number'))), 'nullable', 'string', 'max:120', Rule::unique($this->input('item_type') === InventoryItem::TYPE_MODEM ? 'modems' : 'phones', 'imei')->ignore($this->input('item_type') === InventoryItem::TYPE_MODEM ? $modem?->id : $phone?->id)],
            'android_version' => ['nullable', 'string', 'max:80'],
            'assigned_sim_card_id' => [
                'nullable',
                'exists:sim_cards,id',
                Rule::unique('phones', 'assigned_sim_card_id')->ignore($phone?->id),
                Rule::unique('modems', 'assigned_sim_card_id')->ignore($modem?->id),
            ],
            'assigned_printer_id' => ['nullable', 'exists:printers,id'],
            'printer_identifier' => ['nullable', 'string', 'max:120', Rule::unique('printers', 'printer_identifier')->ignore($printer?->id)],
            'printer_color' => ['nullable', 'string', 'max:80'],
            'firmware_version' => ['nullable', 'string', 'max:120'],
            'iccid' => [Rule::requiredIf($this->input('item_type') === InventoryItem::TYPE_SIM_CARD), 'nullable', 'string', 'max:160', Rule::unique('sim_cards', 'iccid')->ignore($simCard?->id)],
            'imsi' => ['nullable', 'string', 'max:160', Rule::unique('sim_cards', 'imsi')->ignore($simCard?->id)],
            'associated_phone_number' => ['nullable', 'string', 'max:64'],
            'assigned_inventory_item_id' => ['nullable', 'exists:inventory_items,id'],
            'activation_status' => ['nullable', 'string', 'max:80'],
        ];
    }

    public function messages(): array
    {
        return [
            'assigned_sim_card_id.unique' => 'This SIM card is already assigned to another device.',
        ];
    }
}
