<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAsatidzRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_asatidz' => 'required|string|unique:asatidz,id_asatidz',
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'unit_ids' => 'required|array',
            'unit_ids.*' => 'exists:units,id',
            'position_ids' => 'nullable|array',
            'position_ids.*' => 'exists:positions,id'
        ];
    }
}
