<?php

namespace App\Http\Requests\Booking;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'starts_at' => ['sometimes', 'date'],
            'ends_at' => ['sometimes', 'date'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:2000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $starts = $this->input('starts_at');
            $ends = $this->input('ends_at');
            if ($starts !== null && $ends !== null && strtotime((string) $ends) <= strtotime((string) $starts)) {
                $validator->errors()->add('ends_at', 'The end time must be after the start time.');
            }
        });
    }
}
