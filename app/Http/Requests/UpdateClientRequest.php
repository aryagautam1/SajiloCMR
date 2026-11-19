<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        $client = $this->route('client');

        return $client && $this->user()?->can('update', $client);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'company_name' => ['required', 'string', 'max:150'],
            'email' => [
                'required', 'string', 'email', 'max:150',
                Rule::unique('clients', 'email')->ignore($this->route('client')),
            ],
            'phone' => ['required', 'string', 'max:30', 'regex:/^[0-9+\-\s().]{6,30}$/'],
            'address' => ['nullable', 'string', 'max:500'],
            'status' => ['required', Rule::in(array_keys(config('crm.client_statuses')))],
            'notes' => ['nullable', 'string', 'max:2000'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:'.config('crm.max_logo_kb')],
            'remove_logo' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'phone.regex' => 'The phone number may only contain digits, spaces and + - ( ) characters.',
        ];
    }
}
