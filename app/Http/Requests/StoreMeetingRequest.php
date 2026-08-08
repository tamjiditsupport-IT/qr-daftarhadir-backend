<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMeetingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'meeting_type_id' => 'required|exists:meeting_types,id',
            'start_time' => 'required|date|after_or_equal:today',
            'late_minutes' => 'integer|min:0',
            'unit_ids' => 'required|array',
            'unit_ids.*' => 'exists:units,id'
        ];
    }
}
