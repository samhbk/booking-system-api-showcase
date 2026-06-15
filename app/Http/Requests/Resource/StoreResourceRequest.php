<?php

namespace App\Http\Requests\Resource;

use Illuminate\Foundation\Http\FormRequest;

class StoreResourceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'description' => ['nullable', 'string'],
            'timezone' => ['nullable', 'string', 'max:64', 'timezone'],
            'slot_duration_minutes' => ['nullable', 'integer', 'min:1', 'max:1440'],
            'capacity' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
