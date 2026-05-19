<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RepairRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('repair', $this->route('inventory_item')) ?? false;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'string', 'max:80'],
            'issue_description' => ['required', 'string'],
            'repair_performed' => ['nullable', 'string'],
            'opened_at' => ['nullable', 'date'],
            'completed_at' => ['nullable', 'date'],
            'resolution_details' => ['nullable', 'string'],
        ];
    }
}
