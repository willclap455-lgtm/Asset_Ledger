<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InventoryNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('inventory_item')) ?? false;
    }

    public function rules(): array
    {
        return [
            'note_type' => ['required', 'string', 'max:80'],
            'body' => ['required', 'string'],
        ];
    }
}
