<?php

namespace App\Http\Requests;

use App\Models\InventoryMovement;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InventoryMovementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', InventoryMovement::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'movement_type' => ['required', Rule::in(array_keys(InventoryMovement::typeOptions()))],
            'occurred_at' => ['nullable', 'date'],
            'from_location_id' => ['nullable', 'exists:locations,id'],
            'to_location_id' => ['nullable', 'exists:locations,id'],
            'client_id' => ['nullable', 'exists:clients,id'],
            'new_status' => ['nullable', 'string', 'max:80'],
            'notes' => ['nullable', 'string'],
            'item_ids' => ['required', 'array', 'min:1'],
            'item_ids.*' => ['required', 'exists:inventory_items,id'],
        ];
    }
}
