<?php

namespace App\Http\Requests;

use App\Models\Location;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can($this->route('location') ? 'update' : 'create', $this->route('location') ?? Location::class) ?? false;
    }

    public function rules(): array
    {
        $location = $this->route('location');

        return [
            'client_id' => ['nullable', 'exists:clients,id'],
            'type' => ['required', Rule::in(['internal', 'client'])],
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:80', Rule::unique('locations', 'code')->ignore($location?->id)],
            'address_line_1' => ['nullable', 'string', 'max:255'],
            'address_line_2' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:120'],
            'state' => ['nullable', 'string', 'max:32'],
            'postal_code' => ['nullable', 'string', 'max:32'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:80'],
            'notes' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
