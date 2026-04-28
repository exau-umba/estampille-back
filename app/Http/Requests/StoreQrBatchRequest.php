<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreQrBatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_name' => ['nullable', 'string', 'max:255'],
            'company_id' => ['required', 'exists:companies,id'],
            'product_id' => ['required', 'exists:products,id'],
            'certificate_id' => ['required', 'exists:certificates,id'],
            'quantity' => ['required', 'integer', 'min:1', 'max:1048576'],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ];
    }
}
