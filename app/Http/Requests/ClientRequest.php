<?php

namespace App\Http\Requests;

use App\Models\Client;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can($this->route('client') ? 'update' : 'create', $this->route('client') ?? Client::class) ?? false;
    }

    public function rules(): array
    {
        $client = $this->route('client');

        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:80', Rule::unique('clients', 'code')->ignore($client?->id)],
            'status' => ['required', 'string', 'max:80'],
            'primary_contact_name' => ['nullable', 'string', 'max:255'],
            'primary_contact_email' => ['nullable', 'email', 'max:255'],
            'primary_contact_phone' => ['nullable', 'string', 'max:80'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
