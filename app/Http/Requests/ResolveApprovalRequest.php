<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ResolveApprovalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => 'required|in:Approved,Rejected',
            'notes' => 'nullable|string',
            'asatidz_id' => 'nullable|exists:asatidz,id'
        ];
    }
}
